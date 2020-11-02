<?php

namespace Firebase\Auth\Token;

use Firebase\Auth\Token\Exception\InvalidToken;
use Lcobucci\JWT\Token;

final class TenantAwareVerifier implements Domain\Verifier
{
    /**
     * @var string
     */
    private $tenantId;

    /**
     * @var Domain\Verifier
     */
    private $baseVerifier;

    /**
     * @deprecated 1.12.0
     * @see \Kreait\Firebase\JWT\IdTokenVerifier
     */
    public function __construct(string $tenantId, Domain\Verifier $baseVerifier)
    {
        $this->tenantId = $tenantId;
        $this->baseVerifier = $baseVerifier;
    }

    public function verifyIdToken($token): Token
    {
        $token = $this->baseVerifier->verifyIdToken($token);

        $firebaseClaims = $token->getClaim('firebase', new \stdClass());

        if (!($tenant = $firebaseClaims->tenant ?? null)) {
            throw new InvalidToken($token, 'The ID token does not contain a tenant identifier');
        }

        if ($tenant !== $this->tenantId) {
            throw new InvalidToken($token, 'User tenant ID does not match with the current tenant ID.');
        }

        return $token;
    }
}
