<?php

declare(strict_types=1);

namespace Firebase\Auth\Token;

use BadMethodCallException;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Token;

final class Generator implements Domain\Generator
{
    use ConvertsDates;

    /** @var string */
    private $clientEmail;

    /** @var Configuration */
    private $config;

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

        $this->config = Configuration::forSymmetricSigner(
            $signer ?: new Signer\Rsa\Sha256(),
            Signer\Key\InMemory::plainText($privateKey)
        );
    }

    /**
     * Returns a token for the given user and claims.
     *
     * @param mixed $uid
     * @param DateTimeInterface $expiresAt
     *
     * @throws BadMethodCallException when a claim is invalid
     */
    public function createCustomToken($uid, array $claims = [], DateTimeInterface $expiresAt = null): Token
    {
        $now = new DateTimeImmutable();

        $expiresAt = $expiresAt
            ? $this->convertExpiryDate($expiresAt)
            : $now->add(new DateInterval('PT1H'));

        $builder = $this->config->builder()
            ->issuedBy($this->clientEmail)
            ->relatedTo($this->clientEmail)
            ->permittedFor('https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit')
            ->withClaim('uid', (string) $uid)
            ->issuedAt($now)
            ->expiresAt($expiresAt);

        if (!empty($claims)) {
            $builder->withClaim('claims', $claims);
        }

        return $builder->getToken($this->config->signer(), $this->config->signingKey());
    }
}
