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
    private $handler;

    /** @var CustomTokenGenerator */
    private $generator;

    protected function setUp()
    {
        $this->handler = new class() implements Handler {
            public $action;

            public function handle(CreateCustomToken $action): Token
            {
                $this->action = $action;

                return TokenInstance::withValues('', [], []);
            }
        };

        $this->generator = new CustomTokenGenerator($this->handler);
    }

    /** @test */
    public function it_can_be_created_with_credentials()
    {
        CustomTokenGenerator::withClientEmailAndPrivateKey('email@domain.tld', 'some-private-key');
        $this->addToAssertionCount(1);
    }

    /** @test */
    public function it_delegates_a_simple_action()
    {
        $this->generator->createCustomToken('uid');
        $this->assertSame('uid', $this->handler->action->uid());
        $this->assertEmpty($this->handler->action->customClaims());
        $this->assertTrue(Duration::fromDateIntervalSpec(CreateCustomToken::DEFAULT_TTL)->equals($this->handler->action->timeToLive()));
    }

    /** @test */
    public function it_delegates_an_action_with_custom_claims()
    {
        $customClaims = ['first' => 'first', 'true' => true, 'false' => false, 'null' => null];
        $this->generator->createCustomToken('uid', $customClaims);

        $this->assertEquals($customClaims, $this->handler->action->customClaims());
    }

    /** @test */
    public function it_delegates_an_action_with_a_custom_token_expiration()
    {
        $this->generator->createCustomToken('uid', [], 1337);

        $this->assertTrue(Duration::inSeconds(1337)->equals($this->handler->action->timeToLive()));
    }
}
