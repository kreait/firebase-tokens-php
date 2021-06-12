<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action\VerifyIdToken;

use DateTimeImmutable;
use Kreait\Clock\FrozenClock;
use Kreait\Firebase\JWT\Action\VerifyIdToken;
use Kreait\Firebase\JWT\Action\VerifyIdToken\Handler;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use Kreait\Firebase\JWT\Keys\StaticKeys;
use Kreait\Firebase\JWT\Tests\Util\IdToken;
use Kreait\Firebase\JWT\Tests\Util\KeyPair;
use stdClass;

/**
 * @internal
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected string $projectId = 'project-id';

    protected StaticKeys $keys;

    protected FrozenClock $clock;

    private IdToken $idToken;

    abstract protected function createHandler(): Handler;

    final protected function setUp(): void
    {
        $now = new DateTimeImmutable();
        $now = $now->setTimestamp($now->getTimestamp()); // Trim microseconds, just to be sure

        $this->clock = new FrozenClock($now);

        $this->keys = StaticKeys::withValues(['kid' => KeyPair::publicKey(), 'invalid' => 'invalid']);
        $this->idToken = new IdToken($this->clock);
    }

    public function testItWorksWhenEverythingIsFine(): void
    {
        $this->createHandler()->handle(VerifyIdToken::withToken($this->idToken->build()));
        $this->addToAssertionCount(1);
    }

    public function testItFailsWithEmptyKeys(): void
    {
        $this->keys = StaticKeys::empty();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($this->idToken->build()));
    }

    public function testItRejectsAMalformedToken(): void
    {
        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken('x'.$this->idToken->build()));
    }

    public function testItRejectsAnUnsignedToken(): void
    {
        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($this->idToken->withoutSignature()->build()));
    }

    public function testItRejectsATokenWithoutAKeyId(): void
    {
        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($this->idToken->withoutHeader('kid')->build()));
    }

    public function testItRejectsATokenWithANonMatchingKeyId(): void
    {
        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($this->idToken->withChangedHeader('kid', 'unknown')->build()));
    }

    public function testItRejectsAnExpiredToken(): void
    {
        $idToken = $this->idToken
            ->withClaim('exp', $this->clock->now()->getTimestamp() - 1)
            ->build()
        ;

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken));
    }

    public function testItAcceptsAnExpiredTokenWithLeeway(): void
    {
        $idToken = $this->idToken
            ->withClaim('exp', $this->clock->now()->getTimestamp() - 1)
            ->build()
        ;

        $action = VerifyIdToken::withToken($idToken)->withLeewayInSeconds(2);

        $this->createHandler()->handle($action);
        $this->addToAssertionCount(1);
    }

    public function testItRejectsATokenThatWasIssuedInTheFuture(): void
    {
        $idToken = $this->idToken
            ->withClaim('iat', $this->clock->now()->getTimestamp() + 10)
            ->build()
        ;

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken));
    }

    public function testItRejectsATokenThatIsToBeUsedInTheFuture(): void
    {
        $idToken = $this->idToken
            ->withClaim('nbf', $this->clock->now()->getTimestamp() + 1)
            ->build()
        ;

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken));
    }

    public function testItRejectsATokenWithoutAnAuthTime(): void
    {
        $idToken = $this->idToken
            ->withoutClaim('auth_time')
            ->build()
        ;

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken));
    }

    public function testItRejectsATokenWithAFutureAuthTime(): void
    {
        $idToken = $this->idToken
            ->withClaim('auth_time', $this->clock->now()->getTimestamp() + 1)
            ->build()
        ;

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken));
    }

    public function testItRejectsATokenWithTheWrongAudience(): void
    {
        $idToken = $this->idToken->withClaim('aud', 'wrong-project-id')->build();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken));
    }

    public function testItRejectsATokenWithTheWrongIssuer(): void
    {
        $idToken = $this->idToken->withClaim('iss', 'wrong')->build();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken));
    }

    public function testItVerifiesATokenWithAnExpectedTenantId(): void
    {
        $firebaseClaim = new stdClass();
        $firebaseClaim->tenant = 'my-tenant';
        $idToken = $this->idToken->withClaim('firebase', $firebaseClaim)->build();

        $this->createHandler()->handle(VerifyIdToken::withToken($idToken)->withExpectedTenantId($firebaseClaim->tenant));
        $this->addToAssertionCount(1);
    }

    public function testItVerifiesATokenWithAMismatchingTenantId(): void
    {
        $firebaseClaim = new stdClass();
        $firebaseClaim->tenant = 'unexpected-tenant';
        $idToken = $this->idToken->withClaim('firebase', $firebaseClaim)->build();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken)->withExpectedTenantId('expected-tenant'));
    }

    public function testItVerifiesATokenWithoutATenantIdWhenItExpectsOne(): void
    {
        $firebaseClaim = new stdClass();
        $idToken = $this->idToken->withClaim('firebase', $firebaseClaim)->build();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken)->withExpectedTenantId('a-tenant'));
    }
}
