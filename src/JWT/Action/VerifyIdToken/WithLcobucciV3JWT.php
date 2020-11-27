<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action\VerifyIdToken;

use Kreait\Clock;
use Kreait\Firebase\JWT\Action\VerifyIdToken;
use Kreait\Firebase\JWT\Contract\Keys;
use Kreait\Firebase\JWT\Contract\Token;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use Kreait\Firebase\JWT\Token as TokenInstance;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use stdClass;
use Throwable;

final class WithLcobucciV3JWT implements Handler
{
    /** @var string */
    private $projectId;

    /** @var Keys */
    private $keys;

    /** @var Clock */
    private $clock;

    /** @var Signer */
    private $signer;

    public function __construct(string $projectId, Keys $keys, Clock $clock)
    {
        $this->projectId = $projectId;
        $this->keys = $keys;
        $this->clock = $clock;
        $this->signer = new Sha256();
    }

    public function handle(VerifyIdToken $action): Token
    {
        $tokenString = $action->token();
        $leeway = $action->leewayInSeconds();

        if (empty($this->keys->all())) {
            throw IdTokenVerificationFailed::withTokenAndReasons($tokenString, ["No keys are available to verify the token's signature."]);
        }

        try {
            $token = (new Parser())->parse($tokenString);
        } catch (Throwable $e) {
            throw IdTokenVerificationFailed::withTokenAndReasons($tokenString, ['The token is invalid', $e->getMessage()]);
        }

        $now = $this->clock->now();
        $nowWithAddedLeeway = $now->add(new \DateInterval('PT'.$leeway.'S'));
        $nowWithSubtractedLeeway = $now->sub(new \DateInterval('PT'.$leeway.'S'));
        $errors = [];
        $claims = $token->claims();

        if ($token->isExpired($nowWithSubtractedLeeway)) {
            $errors[] = 'The token is expired.';
        }

        if (!$token->hasBeenIssuedBefore($nowWithAddedLeeway)) {
            $errors[] = 'The token has apparently been issued in the future.';
        }

        if (!$token->isMinimumTimeBefore($nowWithAddedLeeway)) {
            $errors[] = 'The token has been issued for future use.';
        }

        $authTime = $claims->get('auth_time', false);
        if ($authTime && ($authTime > $nowWithAddedLeeway->getTimestamp())) {
            $errors[] = "The token's 'auth_time' claim (the time when the user authenticated) must be present and in the past [sic!].";
        }

        if (!$token->isPermittedFor($this->projectId)) {
            $errors[] = "The token's audience doesn't match the current Firebase project.";
        }

        if (!$token->hasBeenIssuedBy($issuer = 'https://securetoken.google.com/'.$this->projectId)) {
            $errors[] = "The token has not been issued by {$issuer}.";
        }

        $expectedTenantId = $action->expectedTenantId();
        $firebaseClaims = $claims->get('firebase', new stdClass());
        $tenantId = $firebaseClaims->tenant ?? null;

        if ($expectedTenantId && !$tenantId) {
            $errors[] = 'The token was expected to have a firebase.tenant claim, but did not have it.';
        } elseif (!$expectedTenantId && $tenantId) {
            $errors[] = 'The token contains a firebase.tenant claim, but was not expected to have one';
        } elseif ($expectedTenantId && $tenantId && $expectedTenantId !== $tenantId) {
            $errors[] = "The token's tenant ID did not match with the expected tenant ID";
        }

        $kid = $token->headers()->get('kid', false);
        $key = null;
        if (!$kid) {
            $errors[] = "The token has no 'kid' header.";
        }

        if (!($key = $this->keys->all()[$kid] ?? null)) {
            $errors[] = "No public key matching the key ID '{$kid}' was found to verify the signature of this token.";
        }

        if ($key && !$token->signature()->verify($this->signer, $token->payload(), Signer\Key\InMemory::plainText($key))) {
            $errors[] = 'The token has an invalid signature';
        }

        if (!empty($errors)) {
            throw IdTokenVerificationFailed::withTokenAndReasons($tokenString, $errors);
        }

        $claims = $token->claims()->all();
        foreach ($claims as &$claim) {
            if ($claim instanceof \DateTimeInterface) {
                $claim = $claim->getTimestamp();
            }
        }
        unset($claim);

        $headers = $token->headers()->all();
        foreach ($headers as &$header) {
            if ($header instanceof \DateTimeInterface) {
                $header = $header->getTimestamp();
            }
        }
        unset($header);

        return TokenInstance::withValues((string) $token, $headers, $claims);
    }
}
