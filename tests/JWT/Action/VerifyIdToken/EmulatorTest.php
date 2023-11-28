<?php

declare(strict_types=1);

namespace JWT\Action\VerifyIdToken;

use Kreait\Firebase\JWT\Action\VerifyIdToken;
use Kreait\Firebase\JWT\Action\VerifyIdToken\Handler;
use Kreait\Firebase\JWT\Action\VerifyIdToken\WithLcobucciJWT;
use Kreait\Firebase\JWT\InsecureToken;
use Kreait\Firebase\JWT\Keys\StaticKeys;
use Kreait\Firebase\JWT\Tests\Action\VerifyIdToken\TestCase;

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
        $token = $this->createHandler()->handle(VerifyIdToken::withToken($this->token->withoutSignature()->idToken()));

        $this->assertInstanceOf(InsecureToken::class, $token);
    }

    public function testItAcceptsATokenWithoutKidHeader(): void
    {
        $token = $this->createHandler()->handle(VerifyIdToken::withToken($this->token->withoutHeader('kid')->idToken()));

        $this->assertInstanceOf(InsecureToken::class, $token);
    }

    public function testItAcceptsEmptyKeys(): void
    {
        $this->keys = StaticKeys::empty();

        $token = $this->createHandler()->handle(VerifyIdToken::withToken($this->token->idToken()));
        $this->assertInstanceOf(InsecureToken::class, $token);
    }

    protected function createHandler(): Handler
    {
        return new WithLcobucciJWT($this->projectId, $this->keys, $this->clock);
    }
}
