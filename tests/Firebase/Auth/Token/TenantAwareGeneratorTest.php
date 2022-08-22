<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Tests;

use DateTimeImmutable;
use Firebase\Auth\Token\Domain;
use Firebase\Auth\Token\TenantAwareGenerator;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Plain;

/**
 * @internal
 */
class TenantAwareGeneratorTest extends TestCase
{
    /** @var TenantAwareGenerator */
    protected Domain\Generator $generator;

    protected string $tenantId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantId = 'my-tenant';
        $this->generator = new TenantAwareGenerator($this->tenantId, 'user@domain.tld', $this->onePrivateKey()->contents());
    }

    public function testGenerateWithTenantId(): void
    {
        $token = $this->generator->createCustomToken('uid');
        $this->assertInstanceOf(Plain::class, $token);

        $this->assertSame($this->tenantId, $token->claims()->get('tenant_id'));
    }

    public function testCreateCustomToken(): void
    {
        $token = $this->generator->createCustomToken('some-uid', ['some' => 'claim']);

        $this->assertInstanceOf(Token::class, $token);
    }

    public function testCreateCustomTokenWithEmptyClaims(): void
    {
        $token = $this->generator->createCustomToken('some-uid');
        $this->assertInstanceOf(Token\Plain::class, $token);

        $this->assertSame('some-uid', $token->claims()->get('uid'));
    }

    public function testCreateCustomTokenWithCustomExpiration(): void
    {
        $expiresAt = (new DateTimeImmutable())->modify(\random_int(1, 3600).' minutes');

        $token = $this->generator->createCustomToken('some-uid', [], $expiresAt);
        $this->assertInstanceOf(Token\Plain::class, $token);

        $this->assertSame($expiresAt->getTimestamp(), $token->claims()->get('exp')->getTimestamp());
    }

    public function testDontCarryStateBetweenCalls(): void
    {
        $token1 = $this->generator->createCustomToken('first', ['admin' => true]);
        $token2 = $this->generator->createCustomToken('second');

        $this->assertInstanceOf(Token\Plain::class, $token1);
        $this->assertInstanceOf(Token\Plain::class, $token2);

        $this->assertSame(['admin' => true], $token1->claims()->get('claims'));
        $this->assertSame([], $token2->claims()->get('claims', []));
    }
}
