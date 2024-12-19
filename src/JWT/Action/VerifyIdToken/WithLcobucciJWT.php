<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action\VerifyIdToken;

use Beste\Clock\FrozenClock;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Kreait\Firebase\JWT\Action\VerifyIdToken;
use Kreait\Firebase\JWT\Contract\Keys;
use Kreait\Firebase\JWT\Contract\Token;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use Kreait\Firebase\JWT\InsecureToken;
use Kreait\Firebase\JWT\SecureToken;
use Kreait\Firebase\JWT\Signer\None;
use Kreait\Firebase\JWT\Token\Parser;
use Kreait\Firebase\JWT\Util;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\ConstraintViolation;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Lcobucci\JWT\Validation\Validator;
use Psr\Clock\ClockInterface;
use Throwable;

use function assert;
use function is_string;

/**
 * @internal
 */
final class WithLcobucciJWT implements Handler
{
    private readonly Parser $parser;

    private readonly Signer $signer;

    private readonly Validator $validator;

    private readonly bool $isRunOnEmulator;

    /**
     * @param non-empty-string $projectId
     */
    public function __construct(
        private readonly string $projectId,
        private readonly Keys $keys,
        private readonly ClockInterface $clock,
    ) {
        $this->parser = new Parser(new JoseEncoder());

        $this->isRunOnEmulator = Util::authEmulatorHost() !== '';

        $this->signer = $this->isRunOnEmulator ? new None() : new Sha256();
        $this->validator = new Validator();
    }

    public function handle(VerifyIdToken $action): Token
    {
        $tokenString = $action->token();

        try {
            $token = $this->parser->parse($tokenString);
            assert($token instanceof UnencryptedToken);
        } catch (Throwable $e) {
            throw IdTokenVerificationFailed::withTokenAndReasons($tokenString, ['The token is invalid'.$e->getMessage()]);
        }

        $key = $this->getKey($token);
        $clock = FrozenClock::at($this->clock->now());
        $leeway = new DateInterval('PT'.$action->leewayInSeconds().'S');
        $errors = [];

        $constraints = [
            new LooseValidAt($clock, $leeway),
            new IssuedBy(...["https://securetoken.google.com/{$this->projectId}"]),
            new PermittedFor($this->projectId),
        ];

        if ($key !== '' && !$this->isRunOnEmulator) {
            $constraints[] = new SignedWith($this->signer, InMemory::plainText($key));
        }

        try {
            $this->validator->assert($token, ...$constraints);

            $this->assertUserAuthedAt($token, $clock->now()->add($leeway));

            if ($tenantId = $action->expectedTenantId()) {
                $this->assertTenantId($token, $tenantId);
            }
        } catch (RequiredConstraintsViolated $e) {
            $errors = array_filter(array_map(
                static fn(ConstraintViolation $violation): string => $violation->getMessage(),
                $e->violations(),
            ));
        }

        if (!empty($errors)) {
            throw IdTokenVerificationFailed::withTokenAndReasons($tokenString, $errors);
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

        if ($this->isRunOnEmulator) {
            return InsecureToken::withValues($tokenString, $headers, $claims);
        }

        return SecureToken::withValues($tokenString, $headers, $claims);
    }

    private function getKey(UnencryptedToken $token): string
    {
        if ($this->isRunOnEmulator && ($this->signer instanceof None)) {
            return '';
        }

        $keyId = $token->headers()->get('kid');
        $keys = $this->keys->all();
        $key = $keys[$keyId] ?? null;

        if ($key !== null) {
            return $key;
        }

        if ($this->isRunOnEmulator) {
            return '';
        }

        if ($keys === []) {
            throw IdTokenVerificationFailed::withTokenAndReasons($token->toString(), ['No keys are available to verify the tokens signature.']);
        }

        if (!is_string($keyId) || $keyId === '') {
            throw IdTokenVerificationFailed::withTokenAndReasons($token->toString(), ['No key ID was found to verify the signature of this token.']);
        }

        throw IdTokenVerificationFailed::withTokenAndReasons($token->toString(), ["No public key matching the key ID '{$keyId}' was found to verify the signature of this token."]);
    }

    private function assertUserAuthedAt(UnencryptedToken $token, DateTimeInterface $now): void
    {
        /** @var int|DateTimeImmutable $authTime */
        $authTime = $token->claims()->get('auth_time');

        if (!$authTime) {
            throw RequiredConstraintsViolated::fromViolations(
                new ConstraintViolation('The token is missing the "auth_time" claim.'),
            );
        }

        if (is_numeric($authTime)) {
            $authTime = new DateTimeImmutable('@'.((int) $authTime));
        }

        if ($now < $authTime) {
            throw RequiredConstraintsViolated::fromViolations(
                new ConstraintViolation("The token's user must have authenticated in the past"),
            );
        }
    }

    private function assertTenantId(UnencryptedToken $token, string $tenantId): void
    {
        $claim = (array) $token->claims()->get('firebase', []);

        $tenant = $claim['tenant'] ?? null;

        if (!is_string($tenant)) {
            throw RequiredConstraintsViolated::fromViolations(
                new ConstraintViolation('The ID token does not contain a tenant identifier'),
            );
        }

        if ($tenant !== $tenantId) {
            throw RequiredConstraintsViolated::fromViolations(
                new ConstraintViolation("The token's tenant ID did not match with the expected tenant ID"),
            );
        }
    }
}
