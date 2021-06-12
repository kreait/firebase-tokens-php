<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests;

use Kreait\Firebase\JWT\Action\VerifyIdToken;
use Kreait\Firebase\JWT\Action\VerifyIdToken\Handler;
use Kreait\Firebase\JWT\Contract\Token;
use Kreait\Firebase\JWT\IdTokenVerifier;
use Kreait\Firebase\JWT\Token as TokenInstance;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @internal
 */
final class IdTokenVerifierTest extends TestCase
{
    private Handler $handler;

    private IdTokenVerifier $verifier;

    protected function setUp(): void
    {
        $this->handler = new class() implements Handler {
            public ?VerifyIdToken $action = null;

            public function handle(VerifyIdToken $action): Token
            {
                $this->action = $action;

                return TokenInstance::withValues('', [], []);
            }
        };

        $this->verifier = new IdTokenVerifier($this->handler);
    }

    public function testItCanBeCreatedWithAProjectId(): void
    {
        IdTokenVerifier::createWithProjectId('project-id');
        $this->addToAssertionCount(1);
    }

    public function testItCanBeCreatedWithAProjectIdAndCustomCache(): void
    {
        IdTokenVerifier::createWithProjectIdAndCache('project-id', $this->createMock(CacheItemPoolInterface::class));
        $this->addToAssertionCount(1);
    }

    public function testItVerifiesAToken(): void
    {
        $this->verifier->verifyIdToken('token');
        $this->assertSame('token', $this->handler->action->token());
        $this->assertSame(0, $this->handler->action->leewayInSeconds());
    }

    public function testItVerifiesATokenWithLeeway(): void
    {
        $this->verifier->verifyIdTokenWithLeeway('token', 1337);
        $this->assertSame('token', $this->handler->action->token());
        $this->assertSame(1337, $this->handler->action->leewayInSeconds());
    }

    public function testItVerifiesATokenWithAnExpectedTenantId(): void
    {
        $this->verifier->withExpectedTenantId('my-tenant')->verifyIdToken('token');
        $this->assertSame('my-tenant', $this->handler->action->expectedTenantId());
    }
}
