<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Tests;

use Firebase\Auth\Token\Exception\InvalidToken;
use Firebase\Auth\Token\TenantAwareVerifier;
use Firebase\Auth\Token\Tests\Util\TestHelperClock;
use Firebase\Auth\Token\Verifier;
use Kreait\Clock\SystemClock;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Configuration;

/**
 * @internal
 */
class TenantAwareVerifierTest extends TestCase
{
    private TenantAwareVerifier $verifier;

    private Configuration $config;

    private string $projectId;

    private string $tenantId;

    private Builder $builder;

    protected function setUp(): void
    {
        $this->config = $this->createJwtConfiguration();
        $this->projectId = 'project-id';
        $this->tenantId = 'my-tenant';

        $clock = new TestHelperClock(new SystemClock());

        $this->builder = $this->config->builder()
            ->expiresAt($clock->minutesLater(30))
            ->withClaim('auth_time', $clock->minutesEarlier(30)->getTimestamp())
            ->issuedAt($clock->secondsEarlier(10))
            ->issuedBy('https://securetoken.google.com/'.$this->projectId)
            ->permittedFor($this->projectId)
            ->withHeader('kid', 'valid_key_id')
        ;

        $baseVerifier = new Verifier($this->projectId, $this->createKeyStore(), $this->config->signer());
        $this->verifier = new TenantAwareVerifier($this->tenantId, $baseVerifier);
    }

    public function testWithMatchingTenantId(): void
    {
        $token = $this->builder
            ->withClaim('firebase', (object) ['tenant' => $this->tenantId])
            ->getToken($this->config->signer(), $this->config->signingKey())
        ;

        $this->verifier->verifyIdToken($token);
        $this->addToAssertionCount(1);
    }

    public function testWithMismatchingTenantId(): void
    {
        $token = $this->builder
            ->withClaim('firebase', (object) ['tenant' => 'unknown-tenant'])
            ->getToken($this->config->signer(), $this->config->signingKey())
        ;

        $this->expectException(InvalidToken::class);
        $this->verifier->verifyIdToken($token);
    }

    public function testWithMissingTenantId(): void
    {
        $token = $this->builder
            ->getToken($this->config->signer(), $this->config->signingKey())
        ;

        $this->expectException(InvalidToken::class);
        $this->verifier->verifyIdToken($token);
    }
}
