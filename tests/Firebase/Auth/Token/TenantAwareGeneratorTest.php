<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Tests;

use Firebase\Auth\Token\TenantAwareGenerator;

/**
 * @internal
 */
class TenantAwareGeneratorTest extends GeneratorTest
{
    /** @var TenantAwareGenerator */
    protected $generator;

    /** @var string */
    protected $tenantId;

    protected function setUp(): void
    {
        $this->tenantId = 'my-tenant';
        $this->generator = new TenantAwareGenerator($this->tenantId, 'user@domain.tld', $this->onePrivateKey()->contents());
    }

    public function testGenerateWithTenantId()
    {
        $token = $this->generator->createCustomToken('uid');

        $this->assertSame($this->tenantId, $token->claims()->get('tenant_id'));
    }
}
