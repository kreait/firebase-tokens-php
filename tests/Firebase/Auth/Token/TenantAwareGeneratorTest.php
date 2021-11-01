<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Tests;

use Firebase\Auth\Token\TenantAwareGenerator;
use Lcobucci\JWT\Token\Plain;

/**
 * @internal
 *
 * @property TenantAwareGenerator $generator
 */
class TenantAwareGeneratorTest extends GeneratorTestCase
{
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
}
