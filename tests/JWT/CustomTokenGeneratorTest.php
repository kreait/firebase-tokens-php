<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests;

use Kreait\Firebase\JWT\Action\CreateCustomToken;
use Kreait\Firebase\JWT\Action\CreateCustomToken\Handler;
use Kreait\Firebase\JWT\Contract\Token;
use Kreait\Firebase\JWT\CustomTokenGenerator;
use Kreait\Firebase\JWT\Token as TokenInstance;
use Kreait\Firebase\JWT\Value\Duration;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CustomTokenGeneratorTest extends TestCase
{
    private Handler $handler;

    private CustomTokenGenerator $generator;

    protected function setUp(): void
    {
        $this->handler = new class() implements Handler {
            public ?CreateCustomToken $action = null;

            public function handle(CreateCustomToken $action): Token
            {
                $this->action = $action;

                return TokenInstance::withValues('', [], []);
            }
        };

        $this->generator = new CustomTokenGenerator($this->handler);
    }

    public function testItCanBeCreatedWithCredentials(): void
    {
        CustomTokenGenerator::withClientEmailAndPrivateKey('email@domain.tld', 'some-private-key');
        $this->addToAssertionCount(1);
    }

    public function testItDelegatesASimpleAction(): void
    {
        $this->generator->createCustomToken('uid');
        $this->assertSame('uid', $this->handler->action->uid());
        $this->assertEmpty($this->handler->action->customClaims());
        $this->assertTrue(Duration::fromDateIntervalSpec(CreateCustomToken::DEFAULT_TTL)->equals($this->handler->action->timeToLive()));
    }

    public function testItDelegatesAnActionWithCustomClaims(): void
    {
        $customClaims = ['first' => 'first', 'true' => true, 'false' => false, 'null' => null];
        $this->generator->createCustomToken('uid', $customClaims);

        $this->assertEquals($customClaims, $this->handler->action->customClaims());
    }

    public function testItDelegatesAnActionWithACustomTokenExpiration(): void
    {
        $this->generator->createCustomToken('uid', [], 1337);

        $this->assertTrue(Duration::inSeconds(1337)->equals($this->handler->action->timeToLive()));
    }

    public function testItUsesATenantIdWhenGiven(): void
    {
        $this->generator->withTenantId('my-tenant')->createCustomToken('uid');

        $this->assertSame('my-tenant', $this->handler->action->tenantId());
    }
}
