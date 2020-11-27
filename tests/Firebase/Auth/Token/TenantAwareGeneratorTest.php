<?php

namespace Firebase\Auth\Token\Tests;

use Firebase\Auth\Token\TenantAwareGenerator;

class TenantAwareGeneratorTest extends GeneratorTest
{
    /**
     * @var TenantAwareGenerator
     */
    protected $generator;

    /**
     * @var string
     */
    protected $tenantId;

    protected function setUp()
    {
        $this->tenantId = 'my-tenant';
        $this->generator = new TenantAwareGenerator($this->tenantId, 'user@domain.tld', 'some-key', $this->createMockSigner());
    }

    public function testGenerateWithTenantId()
    {
        $token = $this->generator->createCustomToken('uid');

        $this->assertSame($this->tenantId, $token->claims()->get('tenant_id'));
    }
}
