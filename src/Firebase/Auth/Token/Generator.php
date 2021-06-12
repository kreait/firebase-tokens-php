<?php

declare(strict_types=1);

namespace Firebase\Auth\Token;

use BadMethodCallException;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;

final class Generator implements Domain\Generator
{
    use ConvertsDates;

    private string $clientEmail;

    private Configuration $config;

    public function __construct(
        string $clientEmail,
        string $privateKey,
        Signer $signer = null
    ) {
        $this->clientEmail = $clientEmail;

        $this->config = Configuration::forSymmetricSigner(
            $signer ?: new Sha256(),
            InMemory::plainText($privateKey)
        );
    }

    /**
     * Returns a token for the given user and claims.
     *
     * @param mixed $uid
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
            ->expiresAt($expiresAt)
        ;

        if (!empty($claims)) {
            $builder->withClaim('claims', $claims);
        }

        return $builder->getToken($this->config->signer(), $this->config->signingKey());
    }
}
