<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests;

use Kreait\Firebase\JWT\Action\VerifySessionCookie;
use Kreait\Firebase\JWT\Action\VerifySessionCookie\Handler;
use Kreait\Firebase\JWT\Contract\Token;
use Kreait\Firebase\JWT\SecureToken;
use Kreait\Firebase\JWT\SessionCookieVerifier;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @internal
 */
final class SessionCookieVerifierTest extends TestCase
{
    private Handler $handler;

    private SessionCookieVerifier $verifier;

    protected function setUp(): void
    {
        $this->handler = new class implements Handler {
            public ?VerifySessionCookie $action = null;

            public function handle(VerifySessionCookie $action): Token
            {
                $this->action = $action;

                return SecureToken::withValues('', [], []);
            }
        };

        $this->verifier = new SessionCookieVerifier($this->handler);
    }

    #[DoesNotPerformAssertions]
    public function testItCanBeCreatedWithAProjectId(): void
    {
        SessionCookieVerifier::createWithProjectId('project-id');
    }

    #[DoesNotPerformAssertions]
    public function testItCanBeCreatedWithAProjectIdAndCustomCache(): void
    {
        SessionCookieVerifier::createWithProjectIdAndCache('project-id', $this->createMock(CacheItemPoolInterface::class));
    }

    public function testItVerifiesAToken(): void
    {
        $this->verifier->verifySessionCookie('cookie');
        $this->assertSame('cookie', $this->handler->action->sessionCookie());
        $this->assertSame(0, $this->handler->action->leewayInSeconds());
    }

    public function testItVerifiesATokenWithLeeway(): void
    {
        $this->verifier->verifySessionCookieWithLeeway('cookie', 1337);
        $this->assertSame('cookie', $this->handler->action->sessionCookie());
        $this->assertSame(1337, $this->handler->action->leewayInSeconds());
    }

    public function testItVerifiesATokenWithAnExpectedTenantId(): void
    {
        $this->verifier->withExpectedTenantId('my-tenant')->verifySessionCookie('cookie');
        $this->assertSame('my-tenant', $this->handler->action->expectedTenantId());
    }
}
