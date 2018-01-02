<?php

namespace Firebase\Auth\Token;

use Firebase\Auth\Token\Domain\KeyStore;
use Lcobucci\JWT\Token;

/**
 * @deprecated 1.7.0 Use the Generator and Verifier directly instead
 * @codeCoverageIgnore
 */
final class Handler implements Domain\Generator, Domain\Verifier
{
    /**
     * @var Generator
     */
    private $generator;

    /**
     * @var Verifier
     */
    private $verifier;

    /**
     * @deprecated 1.7.0 Use the Generator and Verifier directly instead
     *
     * @param string $projectId
     * @param string $clientEmail
     * @param string $privateKey
     * @param KeyStore|null $keyStore
     */
    public function __construct(string $projectId, string $clientEmail, string $privateKey, KeyStore $keyStore = null)
    {
        $this->generator = new Generator($clientEmail, $privateKey);
        $this->verifier = new Verifier($projectId, $keyStore ?? new HttpKeyStore());
    }

    public function createCustomToken($uid, array $claims = [], \DateTimeInterface $expiresAt = null): Token
    {
        return $this->generator->createCustomToken($uid, $claims, $expiresAt);
    }

    public function verifyIdToken($token): Token
    {
        return $this->verifier->verifyIdToken($token);
    }
}
