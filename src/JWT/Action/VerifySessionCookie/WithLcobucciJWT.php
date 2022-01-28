<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action\VerifySessionCookie;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Kreait\Firebase\JWT\Action\VerifySessionCookie;
use Kreait\Firebase\JWT\Contract\Keys;
use Kreait\Firebase\JWT\Contract\Token;
use Kreait\Firebase\JWT\Error\SessionCookieVerificationFailed;
use Kreait\Firebase\JWT\Token as TokenInstance;
use Lcobucci\Clock\FrozenClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\ConstraintViolation;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Psr\Clock\ClockInterface;
use Throwable;

/**
 * @internal
 */
final class WithLcobucciJWT implements Handler
{
    private string $projectId;

    private Keys $keys;

    private ClockInterface $clock;

    private Configuration $config;

    public function __construct(string $projectId, Keys $keys, ClockInterface $clock)
    {
        $this->projectId = $projectId;
        $this->keys = $keys;
        $this->clock = $clock;

        $this->config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText(''));
    }

    public function handle(VerifySessionCookie $action): Token
    {
        $cookieString = $action->sessionCookie();

        try {
            $token = $this->config->parser()->parse($cookieString);
            \assert($token instanceof UnencryptedToken);
        } catch (Throwable $e) {
            throw SessionCookieVerificationFailed::withSessionCookieAndReasons($cookieString, ['The token is invalid', $e->getMessage()]);
        }

        $key = $this->getKey($token);
        $clock = new FrozenClock($this->clock->now());
        $leeway = new DateInterval('PT'.$action->leewayInSeconds().'S');
        $errors = [];

        try {
            $this->config->validator()->assert(
                $token,
                new LooseValidAt($clock, $leeway),
                new IssuedBy(...["https://session.firebase.google.com/{$this->projectId}"]),
                new PermittedFor($this->projectId),
                new SignedWith(
                    $this->config->signer(),
                    InMemory::plainText($key)
                )
            );

            $this->assertUserAuthedAt($token, $clock->now()->add($leeway));

            if ($tenantId = $action->expectedTenantId()) {
                $this->assertTenantId($token, $tenantId);
            }
        } catch (RequiredConstraintsViolated $e) {
            $errors = \array_map(
                static fn (ConstraintViolation $violation): string => '- '.$violation->getMessage(),
                $e->violations()
            );
        }

        if (!empty($errors)) {
            throw SessionCookieVerificationFailed::withSessionCookieAndReasons($cookieString, $errors);
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

        return TokenInstance::withValues($cookieString, $headers, $claims);
    }

    private function getKey(UnencryptedToken $token): string
    {
        if (empty($keys = $this->keys->all())) {
            throw SessionCookieVerificationFailed::withSessionCookieAndReasons($token->toString(), ["No keys are available to verify the token's signature."]);
        }

        $keyId = $token->headers()->get('kid');

        if ($key = $keys[$keyId] ?? null) {
            return $key;
        }

        throw SessionCookieVerificationFailed::withSessionCookieAndReasons($token->toString(), ["No public key matching the key ID '{$keyId}' was found to verify the signature of this session cookie."]);
    }

    private function assertUserAuthedAt(UnencryptedToken $token, DateTimeInterface $now): void
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

    private function assertTenantId(UnencryptedToken $token, string $tenantId): void
    {
        $claim = (array) $token->claims()->get('firebase', []);

        $tenant = $claim['tenant'] ?? null;

        if (!\is_string($tenant)) {
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
