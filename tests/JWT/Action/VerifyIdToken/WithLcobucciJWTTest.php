<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action\VerifyIdToken;

use Kreait\Firebase\JWT\Action\VerifyIdToken;
use Kreait\Firebase\JWT\Action\VerifyIdToken\Handler;
use Kreait\Firebase\JWT\Action\VerifyIdToken\WithLcobucciJWT;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use Kreait\Firebase\JWT\Keys\StaticKeys;

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
        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($this->token->withoutSignature()->idToken()));
    }

    public function testItRejectsATokenWithoutAKeyId(): void
    {
        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($this->token->withoutHeader('kid')->idToken()));
    }

    public function testItRejectsATokenWithANonMatchingKeyId(): void
    {
        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($this->token->withChangedHeader('kid', 'unknown')->idToken()));
    }

    public function testItFailsWithEmptyKeys(): void
    {
        $this->keys = StaticKeys::empty();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($this->token->idToken()));
    }

    protected function createHandler(): Handler
    {
        return new WithLcobucciJWT($this->projectId, $this->keys, $this->clock);
    }
}
