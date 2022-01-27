<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action\VerifySessionCookie;

use Beste\Clock\FrozenClock;
use DateTimeImmutable;
use Kreait\Firebase\JWT\Action\VerifySessionCookie;
use Kreait\Firebase\JWT\Action\VerifySessionCookie\Handler;
use Kreait\Firebase\JWT\Error\SessionCookieVerificationFailed;
use Kreait\Firebase\JWT\Keys\StaticKeys;
use Kreait\Firebase\JWT\Tests\Util\KeyPair;
use Kreait\Firebase\JWT\Tests\Util\SessionCookie;

/**
 * @internal
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected string $projectId = 'project-id';

    protected StaticKeys $keys;

    protected FrozenClock $clock;

    private SessionCookie $sessionCookie;

    abstract protected function createHandler(): Handler;

    final protected function setUp(): void
    {
        $now = new DateTimeImmutable();
        $now = $now->setTimestamp($now->getTimestamp()); // Trim microseconds, just to be sure

        $this->clock = FrozenClock::at($now);

        $this->keys = StaticKeys::withValues(['kid' => KeyPair::publicKey(), 'invalid' => 'invalid']);
        $this->sessionCookie = new SessionCookie($this->clock);
    }

    public function testItWorksWhenEverythingIsFine(): void
    {
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($this->sessionCookie->build()));
        $this->addToAssertionCount(1);
    }

    public function testItFailsWithEmptyKeys(): void
    {
        $this->keys = StaticKeys::empty();

        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($this->sessionCookie->build()));
    }

    public function testItRejectsAMalformedToken(): void
    {
        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie('x'.$this->sessionCookie->build()));
    }

    public function testItRejectsAnUnsignedToken(): void
    {
        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($this->sessionCookie->withoutSignature()->build()));
    }

    public function testItRejectsATokenWithoutAKeyId(): void
    {
        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($this->sessionCookie->withoutHeader('kid')->build()));
    }

    public function testItRejectsATokenWithANonMatchingKeyId(): void
    {
        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($this->sessionCookie->withChangedHeader('kid', 'unknown')->build()));
    }

    public function testItRejectsAnExpiredToken(): void
    {
        $sessionCookie = $this->sessionCookie
            ->withClaim('exp', $this->clock->now()->getTimestamp() - 1)
            ->build()
        ;

        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($sessionCookie));
    }

    public function testItAcceptsAnExpiredTokenWithLeeway(): void
    {
        $sessionCookie = $this->sessionCookie
            ->withClaim('exp', $this->clock->now()->getTimestamp() - 1)
            ->build()
        ;

        $action = VerifySessionCookie::withSessionCookie($sessionCookie)->withLeewayInSeconds(2);

        $this->createHandler()->handle($action);
        $this->addToAssertionCount(1);
    }

    public function testItRejectsATokenThatWasIssuedInTheFuture(): void
    {
        $sessionCookie = $this->sessionCookie
            ->withClaim('iat', $this->clock->now()->getTimestamp() + 10)
            ->build()
        ;

        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($sessionCookie));
    }

    public function testItRejectsATokenThatIsToBeUsedInTheFuture(): void
    {
        $sessionCookie = $this->sessionCookie
            ->withClaim('nbf', $this->clock->now()->getTimestamp() + 1)
            ->build()
        ;

        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($sessionCookie));
    }

    public function testItRejectsATokenWithoutAnAuthTime(): void
    {
        $sessionCookie = $this->sessionCookie
            ->withoutClaim('auth_time')
            ->build()
        ;

        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($sessionCookie));
    }

    public function testItRejectsATokenWithAFutureAuthTime(): void
    {
        $sessionCookie = $this->sessionCookie
            ->withClaim('auth_time', $this->clock->now()->getTimestamp() + 1)
            ->build()
        ;

        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($sessionCookie));
    }

    public function testItRejectsATokenWithTheWrongAudience(): void
    {
        $sessionCookie = $this->sessionCookie->withClaim('aud', 'wrong-project-id')->build();

        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($sessionCookie));
    }

    public function testItRejectsATokenWithTheWrongIssuer(): void
    {
        $sessionCookie = $this->sessionCookie->withClaim('iss', 'wrong')->build();

        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($sessionCookie));
    }

    public function testItVerifiesTheNbfClaimIfAvailable(): void
    {
        $extra = [
            'nbf' => $this->clock->now()->getTimestamp() + 10,
        ];

        $this->expectException(SessionCookieVerificationFailed::class);
        $this->expectExceptionMessageMatches('/yet/');
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($this->sessionCookie->build($extra)));
    }
}
