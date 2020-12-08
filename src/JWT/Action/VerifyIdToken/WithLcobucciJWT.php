<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action\VerifyIdToken;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Kreait\Clock;
use Kreait\Firebase\JWT\Action\VerifyIdToken;
use Kreait\Firebase\JWT\Contract\Keys;
use Kreait\Firebase\JWT\Contract\Token;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use Kreait\Firebase\JWT\Token as TokenInstance;
use Lcobucci\Clock\FrozenClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token as JWT;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\ValidAt;
use Lcobucci\JWT\Validation\ConstraintViolation;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Throwable;

final class WithLcobucciJWT implements Handler
{
    /** @var string */
    private $projectId;

    /** @var Keys */
    private $keys;

    /** @var Clock */
    private $clock;

    /** @var Signer */
    private $signer;

    /** @var Configuration */
    private $config;

    public function __construct(string $projectId, Keys $keys, Clock $clock)
    {
        $this->projectId = $projectId;
        $this->keys = $keys;
        $this->clock = $clock;

        $this->config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText(''));
    }

    public function handle(VerifyIdToken $action): Token
    {
        $tokenString = $action->token();

        try {
            $token = $this->config->parser()->parse($tokenString);
        } catch (Throwable $e) {
            throw IdTokenVerificationFailed::withTokenAndReasons($tokenString, ['The token is invalid', $e->getMessage()]);
        }

        if (!($token instanceof JWT\Plain)) {
            throw IdTokenVerificationFailed::withTokenAndReasons($tokenString, ['The token could not be decrypted']);
        }

        $key = $this->getKey($token);
        $clock = new FrozenClock($this->clock->now());
        $leeway = new DateInterval('PT'.$action->leewayInSeconds().'S');
        $errors = [];

        try {
            $this->config->validator()->assert($token, ...[
                new ValidAt($clock, $leeway),
                new IssuedBy(...["https://securetoken.google.com/{$this->projectId}"]),
                new PermittedFor($this->projectId),
                new SignedWith($this->config->signer(), InMemory::plainText($key)),
            ]);

            $this->assertUserAuthedAt($token, $clock->now()->add($leeway));
            if ($tenantId = $action->expectedTenantId()) {
                $this->assertTenantId($token, $tenantId);
            }
        } catch (RequiredConstraintsViolated $e) {
            $errors = \array_map(
                static function (ConstraintViolation $violation): string {
                    return '- '.$violation->getMessage();
                },
                $e->violations()
            );
        }

        if (!empty($errors)) {
            throw IdTokenVerificationFailed::withTokenAndReasons($tokenString, $errors);
        }

        if (!($token instanceof JWT\Plain)) {
            throw IdTokenVerificationFailed::withTokenAndReasons($tokenString, ['The token could not be decrypted']);
        }

        $claims = $token->claims()->all();

        foreach ($claims as &$claim) {
            if ($claim instanceof DateTimeInterface) {
                $claim = $claim->getTimestamp();
            }
        }
        unset($claim);

        $headers = $token->headers()->all();
        foreach ($headers as &$header) {
            if ($header instanceof DateTimeInterface) {
                $header = $header->getTimestamp();
            }
        }
        unset($header);

        return TokenInstance::withValues($tokenString, $headers, $claims);
    }

    private function getKey(JWT $token): string
    {
        if (empty($keys = $this->keys->all())) {
            throw IdTokenVerificationFailed::withTokenAndReasons($token->toString(), ["No keys are available to verify the token's signature."]);
        }

        $keyId = $token->headers()->get('kid');

        if ($key = $keys[$keyId] ?? null) {
            return $key;
        }

        throw IdTokenVerificationFailed::withTokenAndReasons($token->toString(), ["No public key matching the key ID '{$keyId}' was found to verify the signature of this token."]);
    }

    /**
     * @return void
     */
    private function assertUserAuthedAt(JWT\Plain $token, DateTimeInterface $now)
    {
        /** @var int|DateTimeImmutable $authTime */
        $authTime = $token->claims()->get('auth_time');

        if (!$authTime) {
            throw RequiredConstraintsViolated::fromViolations(
                new ConstraintViolation('The token is missing the "auth_time" claim.')
            );
        }

        if (\is_numeric($authTime)) {
            $authTime = new DateTimeImmutable('@'.((int) $authTime));
        }

        if ($now < $authTime) {
            throw RequiredConstraintsViolated::fromViolations(
                new ConstraintViolation("The token's user must have authenticated in the past")
            );
        }
    }

    /**
     * @return void
     */
    private function assertTenantId(JWT\Plain $token, string $tenantId)
    {
        $claim = $token->claims()->get('firebase');

        $tenant = \is_object($claim)
            ? ($claim->tenant ?? null)
            : ($claim['tenant'] ?? null);

        if (!$tenant) {
            throw RequiredConstraintsViolated::fromViolations(
                new ConstraintViolation('The ID token does not contain a tenant identifier')
            );
        }

        if ($tenant !== $tenantId) {
            throw RequiredConstraintsViolated::fromViolations(
                new ConstraintViolation("The token's tenant ID did not match with the expected tenant ID")
            );
        }
    }
}
