<?php

declare(strict_types=1);

namespace Firebase\Auth\Token;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Firebase\Auth\Token\Domain\KeyStore;
use Firebase\Auth\Token\Exception\ExpiredToken;
use Firebase\Auth\Token\Exception\InvalidSignature;
use Firebase\Auth\Token\Exception\InvalidToken;
use Firebase\Auth\Token\Exception\IssuedInTheFuture;
use Firebase\Auth\Token\Exception\UnknownKey;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\ValidAt;
use Lcobucci\JWT\Validation\ConstraintViolation;
use OutOfBoundsException;
use Throwable;

final class Verifier implements Domain\Verifier
{
    private string $projectId;

    private KeyStore $keys;

    private Configuration $config;

    /**
     * @see https://github.com/firebase/firebase-admin-dotnet/pull/29
     */
    private int $leewayInSeconds = 300;

    public function __construct(string $projectId, KeyStore $keys = null, Signer $signer = null)
    {
        $this->projectId = $projectId;
        $this->keys = $keys ?? new HttpKeyStore();
        $this->config = Configuration::forSymmetricSigner($signer ?? new Sha256(), InMemory::plainText(''));
    }

    public function verifyIdToken($token): Token
    {
        if (!($token instanceof Token)) {
            $token = $this->config->parser()->parse($token);
        }

        $key = $this->getKey($token);

        $clock = SystemClock::fromSystemTimezone();
        $leeway = new DateInterval('PT'.$this->leewayInSeconds.'S');

        try {
            $this->config->validator()->assert($token, ...[
                new ValidAt($clock, $leeway),
                new PermittedFor($this->projectId),
                new IssuedBy(...["https://securetoken.google.com/{$this->projectId}"]),
                new SignedWith($this->config->signer(), InMemory::plainText($key)),
            ]);

            $this->assertUserAuthedAt($token, $clock->now()->add($leeway));
        } catch (Throwable $e) {
            $message = $e->getMessage();

            if (\mb_stripos($message, 'signature mismatch') !== false) {
                throw new InvalidSignature($token, $message);
            }

            if (\mb_stripos($message, 'expired') !== false) {
                throw new ExpiredToken($token);
            }

            if (\mb_stripos($message, 'future') !== false) {
                throw new IssuedInTheFuture($token);
            }

            throw new InvalidToken($token, $e->getMessage());
        }

        return $token;
    }

    private function getKey(Token $token): string
    {
        $keyId = $token->headers()->get('kid', '');

        try {
            return $this->keys->get($keyId);
        } catch (OutOfBoundsException $e) {
            throw new UnknownKey($token, $keyId);
        }
    }

    private function assertUserAuthedAt(Token $token, DateTimeInterface $now): void
    {
        if (!($token instanceof Plain)) {
            throw new ConstraintViolation('The token could not be decrypted');
        }

        /** @var int|DateTimeImmutable $authTime */
        $authTime = $token->claims()->get('auth_time');

        if (!$authTime) {
            throw new ConstraintViolation('The token is missing the "auth_time" claim.');
        }

        if (\is_numeric($authTime)) {
            $authTime = new DateTimeImmutable('@'.((int) $authTime));
        }

        if ($now < $authTime) {
            throw new ConstraintViolation("The token's user must have authenticated in the past");
        }
    }
}
