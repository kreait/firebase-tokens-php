<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action\VerifyIdToken;

use Kreait\Clock;
use Kreait\Firebase\JWT\Action\VerifyIdToken;
use Kreait\Firebase\JWT\Contract\Keys;
use Kreait\Firebase\JWT\Contract\Token;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use Kreait\Firebase\JWT\Token as TokenInstance;
use Lcobucci\JWT\Claim;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Rsa\Sha256;
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
            throw IdTokenVerificationFailed::withTokenAndReasons($tokenString, [
                'The token is invalid',
                $e->getMessage(),
            ]);
        }

        $timestamp = $this->clock->now()->getTimestamp();
        $errors = [];

        $exp = $token->getClaim('exp', false);
        if ($exp && ($exp < ($timestamp - $leeway))) {
            $errors[] = 'The token is expired.';
        }

        $iat = $token->getClaim('iat', false);
        if ($iat && ($iat > ($timestamp + $leeway))) {
            $errors[] = 'The token has apparently been issued in the future.';
        }

        $nbf = $token->getClaim('nbf', false);
        if ($nbf && ($nbf > ($timestamp + $leeway))) {
            $errors[] = 'The token has been issued for future use.';
        }

        $authTime = $token->getClaim('auth_time', false);
        if ($authTime && ($authTime > ($timestamp + $leeway))) {
            $errors[] = "The token's 'auth_time' claim (the time when the user authenticated) must be present and be in the past.";
        }

        $audience = $token->getClaim('aud', false);
        if (!$audience || ($audience !== $this->projectId)) {
            $errors[] = "The token's audience doesn't match the current Firebase project. Expected '{$this->projectId}', got '{$audience}'.";
        }

        $issuer = $token->getClaim('iss', false);
        $expectedIssuer = 'https://securetoken.google.com/'.$this->projectId;
        if (!$issuer || ($issuer !== $expectedIssuer)) {
            $errors[] = "The token was issued by the wrong principal. Expected '{$expectedIssuer}', got '{$issuer}'";
        }

        $subject = $token->getClaim('sub', false);
        if (!$subject || !is_string($subject) || trim($subject) === '') {
            $errors[] = "The token's 'sub' claim must be a non-empty string. Got: '{$subject}' (".gettype($subject).')';
        }

        $kid = $token->getHeader('kid', false);
        $key = null;
        if (!$kid) {
            $errors[] = "The token has no 'kid' header.";
        }

        if (!($key = $this->keys->all()[$kid] ?? null)) {
            $errors[] = "No public key matching the key ID '{$kid}' was found to verify the signature of this token.";
        }

        if ($key) {
            try {
                $token->verify($this->signer, $key);
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            throw IdTokenVerificationFailed::withTokenAndReasons($tokenString, $errors);
        }

        /** @var Claim[] $claims */
        $claims = $token->getClaims();

        $payload = [];
        foreach ($claims as $claim) {
            $payload[$claim->getName()] = $claim->getValue();
        }

        return TokenInstance::withValues((string) $token, $token->getHeaders(), $payload);
    }
}
