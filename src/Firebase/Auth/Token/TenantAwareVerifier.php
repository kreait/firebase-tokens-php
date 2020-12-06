<?php

declare(strict_types=1);

namespace Firebase\Auth\Token;

use Firebase\Auth\Token\Exception\InvalidToken;
use Lcobucci\JWT\Token;

final class TenantAwareVerifier implements Domain\Verifier
{
    /** @var string */
    private $tenantId;

    /** @var Domain\Verifier */
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

        if (!($token instanceof Token\Plain)) {
            throw new InvalidToken($token, 'The ID token could not be decrypted');
        }

        $claim = $token->claims()->get('firebase');

        $tenant = \is_object($claim)
            ? ($claim->tenant ?? null)
            : ($claim['tenant'] ?? null);

        if (!$tenant) {
            throw new InvalidToken($token, 'The ID token does not contain a tenant identifier');
        }

        if ($tenant !== $this->tenantId) {
            throw new InvalidToken($token, "The token's tenant ID did not match with the expected tenant ID");
        }

        return $token;
    }
}
