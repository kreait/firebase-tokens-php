<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action\VerifyIdToken;

use Beste\Clock\FrozenClock;
use Kreait\Firebase\JWT\Action\VerifyIdToken;
use Kreait\Firebase\JWT\Action\VerifyIdToken\Handler;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use Kreait\Firebase\JWT\Keys\StaticKeys;
use Kreait\Firebase\JWT\Tests\Util\KeyPair;
use Kreait\Firebase\JWT\Tests\Util\Token;
use Kreait\Firebase\JWT\Util;
use stdClass;

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
        $this->clock = FrozenClock::fromUTC();

        $this->keys = StaticKeys::withValues(['kid' => KeyPair::publicKey(), 'invalid' => 'invalid']);
        $this->token = new Token($this->clock);
    }

    public function testItWorksWhenEverythingIsFine(): void
    {
        $this->createHandler()->handle(VerifyIdToken::withToken($this->token->idToken()));
        $this->addToAssertionCount(1);
    }

    public function testItRejectsAMalformedToken(): void
    {
        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken('x' . $this->token->idToken()));
    }

    public function testItRejectsAnExpiredToken(): void
    {
        $idToken = $this->token
            ->withClaim('exp', $this->clock->now()->modify('-1 second'))
            ->idToken();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken));
    }

    public function testItAcceptsAnExpiredTokenWithLeeway(): void
    {
        $idToken = $this->token
            ->withClaim('exp', $this->clock->now()->modify('-1 second'))
            ->idToken();

        $action = VerifyIdToken::withToken($idToken)->withLeewayInSeconds(2);

        $this->createHandler()->handle($action);
        $this->addToAssertionCount(1);
    }

    public function testItRejectsATokenThatWasIssuedInTheFuture(): void
    {
        $idToken = $this->token
            ->withClaim('iat', $this->clock->now()->modify('+10 seconds'))
            ->idToken();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken));
    }

    public function testItRejectsATokenThatIsToBeUsedInTheFuture(): void
    {
        $idToken = $this->token
            ->withClaim('nbf', $this->clock->now()->modify('+1 second'))
            ->idToken();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken));
    }

    public function testItRejectsATokenWithoutAnAuthTime(): void
    {
        $idToken = $this->token
            ->withoutClaim('auth_time')
            ->idToken();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken));
    }

    public function testItRejectsATokenWithAFutureAuthTime(): void
    {
        $idToken = $this->token
            ->withClaim('auth_time', $this->clock->now()->modify('+1 second'))
            ->idToken();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken));
    }

    public function testItRejectsATokenWithTheWrongAudience(): void
    {
        $idToken = $this->token->withClaim('aud', 'wrong-project-id')->idToken();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken));
    }

    public function testItRejectsATokenWithTheWrongIssuer(): void
    {
        $idToken = $this->token->sessionCookie();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken));
    }

    public function testItVerifiesATokenWithAnExpectedTenantId(): void
    {
        $firebaseClaim = new stdClass();
        $firebaseClaim->tenant = 'my-tenant';
        $idToken = $this->token->withClaim('firebase', $firebaseClaim)->idToken();

        $this->createHandler()->handle(VerifyIdToken::withToken($idToken)->withExpectedTenantId($firebaseClaim->tenant));
        $this->addToAssertionCount(1);
    }

    public function testItVerifiesATokenWithAMismatchingTenantId(): void
    {
        $firebaseClaim = new stdClass();
        $firebaseClaim->tenant = 'unexpected-tenant';
        $idToken = $this->token->withClaim('firebase', $firebaseClaim)->idToken();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken)->withExpectedTenantId('expected-tenant'));
    }

    public function testItVerifiesATokenWithoutATenantIdWhenItExpectsOne(): void
    {
        $firebaseClaim = new stdClass();
        $idToken = $this->token->withClaim('firebase', $firebaseClaim)->idToken();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken)->withExpectedTenantId('a-tenant'));
    }

    public function testItVerifiesTheNbfClaimIfAvailable(): void
    {
        $extra = [
            'nbf' => $this->clock->now()->modify('+10 seconds'),
        ];

        $this->expectException(IdTokenVerificationFailed::class);
        $this->expectExceptionMessageMatches('/yet/');
        $this->createHandler()->handle(VerifyIdToken::withToken($this->token->idToken($extra)));
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
