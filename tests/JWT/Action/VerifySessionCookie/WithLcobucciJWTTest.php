<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action\VerifySessionCookie;

use Kreait\Firebase\JWT\Action\VerifySessionCookie;
use Kreait\Firebase\JWT\Action\VerifySessionCookie\Handler;
use Kreait\Firebase\JWT\Action\VerifySessionCookie\WithLcobucciJWT;
use Kreait\Firebase\JWT\Error\SessionCookieVerificationFailed;

/**
 * @internal
 */
final class WithLcobucciJWTTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->skipIfEmulated();
    }

    public function testItRejectsAnUnsignedToken(): void
    {
        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($this->token->withoutSignature()->sessionCookie()));
    }

    public function testItRejectsATokenWithoutAKeyId(): void
    {
        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($this->token->withoutHeader('kid')->sessionCookie()));
    }

    public function testItRejectsATokenWithANonMatchingKeyId(): void
    {
        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($this->token->withChangedHeader('kid', 'unknown')->sessionCookie()));
    }

    protected function createHandler(): Handler
    {
        return new WithLcobucciJWT($this->projectId, $this->keys, $this->clock);
    }
}
