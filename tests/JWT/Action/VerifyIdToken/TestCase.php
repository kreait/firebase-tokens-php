<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action\VerifyIdToken;

use DateTimeImmutable;
use Kreait\Clock\FrozenClock;
use Kreait\Firebase\JWT\Action\VerifyIdToken;
use Kreait\Firebase\JWT\Action\VerifyIdToken\Handler;
use Kreait\Firebase\JWT\Contract\Keys;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use Kreait\Firebase\JWT\Keys\StaticKeys;
use Kreait\Firebase\JWT\Tests\Util\IdToken;
use Kreait\Firebase\JWT\Tests\Util\KeyPair;

/**
 * @internal
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /** @var string */
    protected $projectId = 'project-id';

    /** @var Keys */
    protected $keys;

    /** @var FrozenClock */
    protected $clock;

    /** @var IdToken */
    private $idToken;

    abstract protected function createHandler(): Handler;

    final protected function setUp(): void
    {
        $now = new DateTimeImmutable();
        $now = $now->setTimestamp($now->getTimestamp()); // Trim microseconds, just to be sure

        $this->clock = new FrozenClock($now);

        $this->keys = StaticKeys::withValues(['kid' => KeyPair::publicKey(), 'invalid' => 'invalid']);
        $this->idToken = new IdToken($this->clock);
    }

    /**
     * @test
     */
    public function it_works_when_everything_is_fine()
    {
        $this->createHandler()->handle(VerifyIdToken::withToken($this->idToken->build()));
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function it_fails_with_empty_keys()
    {
        $this->keys = StaticKeys::empty();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($this->idToken->build()));
    }

    /**
     * @test
     */
    public function it_rejects_a_malformed_token()
    {
        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken('x'.$this->idToken->build()));
    }

    /**
     * @test
     */
    public function it_rejects_an_unsigned_token()
    {
        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($this->idToken->withoutSignature()->build()));
    }

    /**
     * @test
     */
    public function it_rejects_a_token_without_a_key_id()
    {
        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($this->idToken->withoutHeader('kid')->build()));
    }

    /**
     * @test
     */
    public function it_rejects_a_token_with_a_non_matching_key_id()
    {
        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($this->idToken->withChangedHeader('kid', 'unknown')->build()));
    }

    /**
     * @test
     */
    public function it_rejects_an_expired_token()
    {
        $idToken = $this->idToken
            ->withClaim('exp', $this->clock->now()->getTimestamp() - 1)
            ->build();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken));
    }

    /**
     * @test
     */
    public function it_accepts_an_expired_token_with_leeway()
    {
        $idToken = $this->idToken
            ->withClaim('exp', $this->clock->now()->getTimestamp() - 1)
            ->build();

        $action = VerifyIdToken::withToken($idToken)->withLeewayInSeconds(2);

        $this->createHandler()->handle($action);
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function it_rejects_a_token_that_was_issued_in_the_future()
    {
        $idToken = $this->idToken
            ->withClaim('iat', $this->clock->now()->getTimestamp() + 10)
            ->build();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken));
    }

    /**
     * @test
     */
    public function it_rejects_a_token_that_is_to_be_used_in_the_future()
    {
        $idToken = $this->idToken
            ->withClaim('nbf', $this->clock->now()->getTimestamp() + 1)
            ->build();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken));
    }

    /**
     * @test
     */
    public function it_rejects_a_token_without_an_auth_time()
    {
        $idToken = $this->idToken
            ->withoutClaim('auth_time')
            ->build();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken));
    }

    /**
     * @test
     */
    public function it_rejects_a_token_with_a_future_auth_time()
    {
        $idToken = $this->idToken
            ->withClaim('auth_time', $this->clock->now()->getTimestamp() + 1)
            ->build();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken));
    }

    /**
     * @test
     */
    public function it_rejects_a_token_with_the_wrong_audience()
    {
        $idToken = $this->idToken->withClaim('aud', 'wrong-project-id')->build();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken));
    }

    /**
     * @test
     */
    public function it_rejects_a_token_with_the_wrong_issuer()
    {
        $idToken = $this->idToken->withClaim('iss', 'wrong')->build();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken));
    }

    /**
     * @test
     */
    public function it_verifies_a_token_with_an_expected_tenant_id()
    {
        $firebaseClaim = new \stdClass();
        $firebaseClaim->tenant = 'my-tenant';
        $idToken = $this->idToken->withClaim('firebase', $firebaseClaim)->build();

        $this->createHandler()->handle(VerifyIdToken::withToken($idToken)->withExpectedTenantId($firebaseClaim->tenant));
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function it_verifies_a_token_with_a_mismatching_tenant_id()
    {
        $firebaseClaim = new \stdClass();
        $firebaseClaim->tenant = 'unexpected-tenant';
        $idToken = $this->idToken->withClaim('firebase', $firebaseClaim)->build();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken)->withExpectedTenantId('expected-tenant'));
    }

    /**
     * @test
     */
    public function it_verifies_a_token_without_a_tenant_id_when_it_expects_one()
    {
        $firebaseClaim = new \stdClass();
        $idToken = $this->idToken->withClaim('firebase', $firebaseClaim)->build();

        $this->expectException(IdTokenVerificationFailed::class);
        $this->createHandler()->handle(VerifyIdToken::withToken($idToken)->withExpectedTenantId('a-tenant'));
    }
}
