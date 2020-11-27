<?php

namespace Firebase\Auth\Token\Tests;

use Firebase\Auth\Token\Domain\Verifier;
use Firebase\Auth\Token\Exception\InvalidToken;
use Firebase\Auth\Token\TenantAwareVerifier;
use Firebase\Auth\Token\Tests\Util\TestHelperClock;
use Kreait\Clock\SystemClock;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token;

class TenantAwareVerifierTest extends TestCase
{
    /**
     * @var TenantAwareVerifier
     */
    private $verifier;

    /**
     * @var Verifier|\PHPUnit_Framework_MockObject_MockObject
     */
    private $baseVerifier;

    /**
     * @var string
     */
    protected $tenantId;

    protected function setUp()
    {
        parent::setUp();

        $this->tenantId = 'my-tenant';
        $this->baseVerifier = $this->createMock(Verifier::class);
        $this->verifier = new TenantAwareVerifier($this->tenantId, $this->baseVerifier);
    }

    public function testWithMatchingTenantId()
    {
        $token = $this->tokenWithTenantId($this->tenantId);

        $this->baseVerifier->method('verifyIdToken')->with($token)->willReturn($token);

        $this->verifier->verifyIdToken($token);
        $this->addToAssertionCount(1);
    }

    public function testWithMismatchingTenantId()
    {
        $token = $this->tokenWithTenantId('my-other-tenant');

        $this->baseVerifier->method('verifyIdToken')->with($token)->willReturn($token);

        $this->expectException(InvalidToken::class);
        $this->verifier->verifyIdToken($token);
    }

    public function testWithMissingTenantId()
    {
        $token = $this->tokenWithoutTenantId();

        $this->baseVerifier->method('verifyIdToken')->with($token)->willReturn($token);

        $this->expectException(InvalidToken::class);
        $this->verifier->verifyIdToken($token);
    }

    private function tokenWithTenantId($tenantId): Token
    {
        $clock = new TestHelperClock(new SystemClock());

        return (new Builder())
            ->expiresAt($clock->minutesLater(30))
            ->withClaim('auth_time', $clock->minutesEarlier(30)->getTimestamp())
            ->issuedAt($clock->secondsEarlier(10))
            ->issuedBy('https://securetoken.google.com/project-id')
            ->withHeader('kid', 'valid_key_id')
            ->withClaim('firebase', (object) ['tenant' => $tenantId])
            ->getToken($this->createMockSigner(), InMemory::plainText('valid_key'));
    }

    private function tokenWithoutTenantId(): Token
    {
        $clock = new TestHelperClock(new SystemClock());

        return (new Builder())
            ->expiresAt($clock->minutesLater(30))
            ->withClaim('auth_time', $clock->minutesEarlier(30)->getTimestamp())
            ->issuedAt($clock->secondsEarlier(10))
            ->issuedBy('https://securetoken.google.com/project-id')
            ->withHeader('kid', 'valid_key_id')
            ->getToken($this->createMockSigner(), InMemory::plainText('valid_key'));
    }
}
