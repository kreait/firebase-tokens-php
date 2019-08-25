<?php

namespace Firebase\Auth\Token;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;

final class Generator implements Domain\Generator
{
    /**
     * @var string
     */
    private $clientEmail;

    /**
     * @var string
     */
    private $privateKey;

    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var Signer
     */
    private $signer;

    /**
     * @deprecated 1.9.0
     * @see \Kreait\Firebase\JWT\CustomTokenGenerator
     */
    public function __construct(
        string $clientEmail,
        string $privateKey,
        Signer $signer = null
    ) {
        $this->clientEmail = $clientEmail;
        $this->privateKey = $privateKey;

        $this->builder = $this->createBuilder();
        $this->signer = $signer ?: new Sha256();
    }

    /**
     * Returns a token for the given user and claims.
     *
     * @param mixed $uid
     * @param array $claims
     * @param \DateTimeInterface $expiresAt
     *
     * @throws \BadMethodCallException when a claim is invalid
     *
     * @return Token
     */
    public function createCustomToken($uid, array $claims = [], \DateTimeInterface $expiresAt = null): Token
    {
        if (count($claims)) {
            $this->builder->set('claims', $claims);
        }

        $this->builder->set('uid', (string) $uid);

        $now = time();
        $expiration = $expiresAt ? $expiresAt->getTimestamp() : $now + (60 * 60);

        $token = $this->builder
            ->setIssuedAt($now)
            ->setExpiration($expiration)
            ->sign($this->signer, $this->privateKey)
            ->getToken();

        $this->builder->unsign();

        return $token;
    }

    private function createBuilder(): Builder
    {
        return (new Builder())
            ->setIssuer($this->clientEmail)
            ->setSubject($this->clientEmail)
            ->setAudience('https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit');
    }
}
