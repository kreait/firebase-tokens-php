<?php

declare(strict_types=1);

namespace JWT\Action\VerifySessionCookie;

use Kreait\Firebase\JWT\Action\VerifySessionCookie;
use Kreait\Firebase\JWT\Action\VerifySessionCookie\Handler;
use Kreait\Firebase\JWT\Action\VerifySessionCookie\WithLcobucciJWT;
use Kreait\Firebase\JWT\InsecureToken;
use Kreait\Firebase\JWT\Tests\Action\VerifySessionCookie\TestCase;

/**
 * @internal
 *
 * @group emulator
 */
final class EmulatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->skipIfNotEmulated();
    }

    public function testItAcceptsAnUnsignedToken(): void
    {
        $token = $this->createHandler()->handle(
            VerifySessionCookie::withSessionCookie($this->token->withoutSignature()->sessionCookie()),
        );

        $this->assertInstanceOf(InsecureToken::class, $token);
    }

    public function testItAcceptsATokenWithoutAKeyId(): void
    {
        $sessionCookie = $this->createHandler()->handle(
            VerifySessionCookie::withSessionCookie($this->token->withoutSignature()->withoutHeader('kid')->sessionCookie()),
        );

        $this->assertInstanceOf(InsecureToken::class, $sessionCookie);
        $this->assertArrayNotHasKey('kid', $sessionCookie->headers());
    }

    public function testItAcceptsATokenWithANonMatchingKeyId(): void
    {
        $sessionCookie = $this->createHandler()->handle(
            VerifySessionCookie::withSessionCookie($this->token->withoutSignature()->withChangedHeader('kid', 'unknown')->sessionCookie()),
        );

        $this->assertInstanceOf(InsecureToken::class, $sessionCookie);
        $this->assertSame('unknown', $sessionCookie->headers()['kid']);
    }

    protected function createHandler(): Handler
    {
        return new WithLcobucciJWT($this->projectId, $this->keys, $this->clock);
    }
}
