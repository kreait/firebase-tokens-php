<?php

declare(strict_types=1);

namespace Firebase\Auth\Token;

use Firebase\Auth\Token\Domain\Verifier;
use Firebase\Auth\Token\Exception\InvalidToken;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Plain;

final class TenantAwareVerifier implements Verifier
{
    private string $tenantId;

    private Verifier $baseVerifier;

    public function __construct(string $tenantId, Verifier $baseVerifier)
    {
        $this->tenantId = $tenantId;
        $this->baseVerifier = $baseVerifier;
    }

    public function verifyIdToken($token): Token
    {
        $token = $this->baseVerifier->verifyIdToken($token);

        if (!($token instanceof Plain)) {
            throw new InvalidToken($token, 'The ID token could not be decrypted');
        }

        $claim = $token->claims()->get('firebase');

        $tenant = null;
        if (\is_object($claim) && \property_exists($claim, 'tenant')) {
            $tenant = $claim->tenant;
        } elseif (\is_array($claim)) {
            $tenant = $claim['tenant'] ?? null;
        }

        if (!\is_string($tenant)) {
            throw new InvalidToken($token, 'The ID token does not contain a tenant identifier');
        }

        if ($tenant !== $this->tenantId) {
            throw new InvalidToken($token, "The token's tenant ID did not match with the expected tenant ID");
        }

        return $token;
    }
}
