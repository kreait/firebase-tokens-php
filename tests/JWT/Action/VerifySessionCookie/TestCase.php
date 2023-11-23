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
use Kreait\Firebase\JWT\Tests\Util\Token;
use Kreait\Firebase\JWT\Util;

/**
 * @internal
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var non-empty-string
     */
    protected string $projectId = 'project-id';
    protected StaticKeys $keys;
    protected FrozenClock $clock;
    protected Token $token;

    protected function setUp(): void
    {
        $now = new DateTimeImmutable();
        $now = $now->setTimestamp($now->getTimestamp()); // Trim microseconds, just to be sure

        $this->clock = FrozenClock::at($now);

        $this->keys = StaticKeys::withValues(['kid' => KeyPair::publicKey(), 'invalid' => 'invalid']);
        $this->token = new Token($this->clock);
    }

    public function testItWorksWhenEverythingIsFine(): void
    {
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($this->token->sessionCookie()));
        $this->addToAssertionCount(1);
    }

    public function testItFailsWithEmptyKeys(): void
    {
        $this->keys = StaticKeys::empty();

        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($this->token->sessionCookie()));
    }

    public function testItRejectsAMalformedToken(): void
    {
        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie('x' . $this->token->sessionCookie()));
    }

    public function testItRejectsAnExpiredToken(): void
    {
        $sessionCookie = $this->token
            ->withClaim('exp', $this->clock->now()->modify('-1 second'))
            ->sessionCookie();

        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($sessionCookie));
    }

    public function testItAcceptsAnExpiredTokenWithLeeway(): void
    {
        $sessionCookie = $this->token
            ->withClaim('exp', $this->clock->now()->modify('-1 second'))
            ->sessionCookie();

        $action = VerifySessionCookie::withSessionCookie($sessionCookie)->withLeewayInSeconds(2);

        $this->createHandler()->handle($action);
        $this->addToAssertionCount(1);
    }

    public function testItRejectsATokenThatWasIssuedInTheFuture(): void
    {
        $sessionCookie = $this->token
            ->withClaim('iat', $this->clock->now()->modify('+10 seconds'))
            ->sessionCookie();

        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($sessionCookie));
    }

    public function testItRejectsATokenThatIsToBeUsedInTheFuture(): void
    {
        $sessionCookie = $this->token
            ->withClaim('nbf', $this->clock->now()->modify('+1 second'))
            ->sessionCookie();

        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($sessionCookie));
    }

    public function testItRejectsATokenWithoutAnAuthTime(): void
    {
        $sessionCookie = $this->token
            ->withoutClaim('auth_time')
            ->sessionCookie();

        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($sessionCookie));
    }

    public function testItRejectsATokenWithAFutureAuthTime(): void
    {
        $sessionCookie = $this->token
            ->withClaim('auth_time', $this->clock->now()->modify('+1 second'))
            ->sessionCookie();

        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($sessionCookie));
    }

    public function testItRejectsATokenWithTheWrongAudience(): void
    {
        $sessionCookie = $this->token->withClaim('aud', 'wrong-project-id')->sessionCookie();

        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($sessionCookie));
    }

    public function testItRejectsATokenWithTheWrongIssuer(): void
    {
        $sessionCookie = $this->token->idToken();

        $this->expectException(SessionCookieVerificationFailed::class);
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($sessionCookie));
    }

    public function testItVerifiesTheNbfClaimIfAvailable(): void
    {
        $extra = [
            'nbf' => $this->clock->now()->modify('+10 seconds'),
        ];

        $this->expectException(SessionCookieVerificationFailed::class);
        $this->expectExceptionMessageMatches('/yet/');
        $this->createHandler()->handle(VerifySessionCookie::withSessionCookie($this->token->sessionCookie($extra)));
    }

    abstract protected function createHandler(): Handler;

    final protected static function isEmulated(): bool
    {
        return Util::authEmulatorHost() !== '';
    }

    final protected function skipIfEmulated(?string $reason = null): void
    {
        if (self::isEmulated()) {
            $this->markTestSkipped($reason ?? 'Environment is emulated');
        }
    }

    final protected function skipIfNotEmulated(?string $reason = null): void
    {
        if (!self::isEmulated()) {
            $this->markTestSkipped($reason ?? 'Environment is not emulated');
        }
    }
}
