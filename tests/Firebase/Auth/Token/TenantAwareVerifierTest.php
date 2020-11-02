<?php

namespace Firebase\Auth\Token\Tests;

use Firebase\Auth\Token\Domain\Verifier;
use Firebase\Auth\Token\Exception\InvalidToken;
use Firebase\Auth\Token\TenantAwareVerifier;
use Lcobucci\JWT\Builder;
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
        return (new Builder())
            ->setExpiration(time() + 1800)
            ->set('auth_time', time() - 1800)
            ->setIssuedAt(time() - 10)
            ->setIssuer('https://securetoken.google.com/project-id')
            ->setHeader('kid', 'valid_key_id')
            ->set('firebase', (object) ['tenant' => $tenantId])
            ->sign($this->createMockSigner(), 'valid_key')
            ->getToken();
    }

    private function tokenWithoutTenantId(): Token
    {
        return (new Builder())
            ->setExpiration(time() + 1800)
            ->set('auth_time', time() - 1800)
            ->setIssuedAt(time() - 10)
            ->setIssuer('https://securetoken.google.com/project-id')
            ->setHeader('kid', 'valid_key_id')
            ->sign($this->createMockSigner(), 'valid_key')
            ->getToken();
    }
}
