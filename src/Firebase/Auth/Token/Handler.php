<?php

declare(strict_types=1);

namespace Firebase\Auth\Token;

use DateTimeInterface;
use Firebase\Auth\Token\Domain\KeyStore;
use Lcobucci\JWT\Token;

/**
 * @deprecated 1.9.0
 * @see \Kreait\Firebase\JWT\IdTokenVerifier
 * @see \Kreait\Firebase\JWT\CustomTokenGenerator
 *
 * @codeCoverageIgnore
 */
final class Handler implements Domain\Generator, Domain\Verifier
{
    private Generator $generator;

    private Verifier $verifier;

    /**
     * @deprecated 1.7.0 Use the Generator and Verifier directly instead
     */
    public function __construct(string $projectId, string $clientEmail, string $privateKey, KeyStore $keyStore = null)
    {
        $this->generator = new Generator($clientEmail, $privateKey);
        $this->verifier = new Verifier($projectId, $keyStore ?? new HttpKeyStore());
    }

    public function createCustomToken($uid, array $claims = [], DateTimeInterface $expiresAt = null): Token
    {
        return $this->generator->createCustomToken($uid, $claims, $expiresAt);
    }

    public function verifyIdToken($token): Token
    {
        return $this->verifier->verifyIdToken($token);
    }
}
