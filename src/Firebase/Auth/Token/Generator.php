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
     * @var Signer\Key
     */
    private $privateKey;

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
        $this->privateKey = new Signer\Key($privateKey);
        $this->signer = $signer ?: new Sha256();
    }

    /**
     * Returns a token for the given user and claims.
     *
     * @param mixed $uid
     * @param \DateTimeInterface $expiresAt
     *
     * @throws \BadMethodCallException when a claim is invalid
     */
    public function createCustomToken($uid, array $claims = [], \DateTimeInterface $expiresAt = null): Token
    {
        $now = new \DateTimeImmutable();

        if (!$expiresAt) {
            $expiresAt = $now->add(new \DateInterval('PT1H'));
        }

        $builder = (new Builder())
            ->issuedBy($this->clientEmail)
            ->relatedTo($this->clientEmail)
            ->permittedFor('https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit')
            ->withClaim('uid', (string) $uid)
            ->issuedAt($now)
            ->expiresAt($expiresAt);

        if (!empty($claims)) {
            $builder->withClaim('claims', $claims);
        }

        return $builder->getToken($this->signer, $this->privateKey);
    }
}
