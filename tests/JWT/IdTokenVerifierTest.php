<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests;

use InvalidArgumentException;
use Kreait\Firebase\JWT\Action\VerifyIdToken;
use Kreait\Firebase\JWT\Action\VerifyIdToken\Handler;
use Kreait\Firebase\JWT\Contract\Token;
use Kreait\Firebase\JWT\IdTokenVerifier;
use Kreait\Firebase\JWT\Token as TokenInstance;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use stdClass;

/**
 * @internal
 */
final class IdTokenVerifierTest extends TestCase
{
    private $handler;

    /** @var IdTokenVerifier */
    private $verifier;

    protected function setUp()
    {
        $this->handler = new class() implements Handler {
            public $action;

            public function handle(VerifyIdToken $action): Token
            {
                $this->action = $action;

                return TokenInstance::withValues('', [], []);
            }
        };

        $this->verifier = new IdTokenVerifier($this->handler);
    }

    /** @test */
    public function it_can_be_created_with_a_project_id()
    {
        IdTokenVerifier::createWithProjectId('project-id');
        $this->addToAssertionCount(1);
    }

    /** @test */
    public function it_can_be_created_with_a_project_id_and_custom_cache()
    {
        IdTokenVerifier::createWithProjectIdAndCache('project-id', $this->createMock(CacheItemPoolInterface::class));
        $this->addToAssertionCount(1);
    }

    /** @test */
    public function it_rejects_an_unsupported_kind_of_custom_cache()
    {
        $this->expectException(InvalidArgumentException::class);
        IdTokenVerifier::createWithProjectIdAndCache('project-id', new stdClass());
    }

    /** @test */
    public function it_verifies_a_token()
    {
        $this->verifier->verifyIdToken('token');
        $this->assertSame('token', $this->handler->action->token());
        $this->assertSame(0, $this->handler->action->leewayInSeconds());
    }

    /** @test */
    public function it_verifies_a_token_with_leeway()
    {
        $this->verifier->verifyIdTokenWithLeeway('token', 1337);
        $this->assertSame('token', $this->handler->action->token());
        $this->assertSame(1337, $this->handler->action->leewayInSeconds());
    }
}
