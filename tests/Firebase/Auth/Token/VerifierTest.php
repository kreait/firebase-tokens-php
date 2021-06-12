<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Tests;

use DateInterval;
use DateTimeImmutable;
use Firebase\Auth\Token\Exception\ExpiredToken;
use Firebase\Auth\Token\Exception\InvalidSignature;
use Firebase\Auth\Token\Exception\InvalidToken;
use Firebase\Auth\Token\Exception\IssuedInTheFuture;
use Firebase\Auth\Token\Exception\UnknownKey;
use Firebase\Auth\Token\Tests\Util\TestHelperClock;
use Firebase\Auth\Token\Verifier;
use Kreait\Clock\SystemClock;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\LocalFileReference;

/**
 * @internal
 */
class VerifierTest extends TestCase
{
    private Verifier $verifier;

    private string $projectId;

    private Builder $builder;

    private Configuration $config;

    protected function setUp(): void
    {
        $this->config = $this->createJwtConfiguration();
        $this->projectId = 'project-id';

        $clock = new TestHelperClock(new SystemClock());

        $this->builder = $this->config->builder()
            ->expiresAt($clock->minutesLater(30))
            ->withClaim('auth_time', $clock->minutesEarlier(30)->getTimestamp())
            ->issuedAt($clock->secondsEarlier(10))
            ->issuedBy('https://securetoken.google.com/'.$this->projectId)
            ->permittedFor($this->projectId)
            ->withHeader('kid', 'valid_key_id')
        ;

        $this->verifier = new Verifier($this->projectId, $this->createKeyStore(), $this->config->signer());
    }

    public function testItVerifiesAValidToken(): void
    {
        $token = $this->builder->getToken($this->config->signer(), $this->config->signingKey());

        $this->verifier->verifyIdToken($token);
        $this->addToAssertionCount(1);
    }

    public function testItVerifiesAValidTokenString(): void
    {
        $token = $this->builder->getToken($this->config->signer(), $this->config->signingKey())->toString();

        $this->verifier->verifyIdToken($token);
        $this->addToAssertionCount(1);
    }

    public function testItAppliesALeewayOf5MinutesWhenCheckingTheIssueTime(): void
    {
        $token = $this->builder
            ->issuedAt((new DateTimeImmutable())->add(new DateInterval('PT295S')))
            ->getToken($this->config->signer(), $this->config->signingKey())
        ;

        $this->verifier->verifyIdToken($token);
        $this->addToAssertionCount(1);
    }

    public function testItAppliesALeewayOf5MinutesWhenCheckingTheAuthTime(): void
    {
        $token = $this->builder
            ->withClaim('auth_time', (new DateTimeImmutable())->add(new DateInterval('PT295S')))
            ->getToken($this->config->signer(), $this->config->signingKey())
        ;

        $this->verifier->verifyIdToken($token);
        $this->addToAssertionCount(1);
    }

    public function testItRejectsATokenOfAUserThatHasNotYetAuthenticated(): void
    {
        $token = $this->builder
            ->withClaim('auth_time', (new DateTimeImmutable())->add(new DateInterval('PT2H')))
            ->getToken($this->config->signer(), $this->config->signingKey())
        ;

        $this->expectException(InvalidToken::class);
        $this->verifier->verifyIdToken($token);
    }

    public function testItRejectsATokenWithNoAuthTime(): void
    {
        $token = $this->builder
            ->withClaim('auth_time', null)
            ->getToken($this->config->signer(), $this->config->signingKey())
        ;

        $this->expectException(InvalidToken::class);
        $this->verifier->verifyIdToken($token);
    }

    public function testItNeedsToFindAPublicKey(): void
    {
        $token = $this->builder
            ->withHeader('kid', 'other')
            ->getToken($this->config->signer(), $this->config->signingKey())
        ;

        $this->expectException(UnknownKey::class);
        $this->verifier->verifyIdToken($token);
    }

    public function testItRejectsAnUnknownSignature(): void
    {
        $other = LocalFileReference::file(__DIR__.'/../../../_fixtures/other.key');
        $token = $this->builder->getToken($this->config->signer(), $other);

        $this->expectException(InvalidSignature::class);
        $this->verifier->verifyIdToken($token);
    }

    public function testItRejectsAnExpiredToken(): void
    {
        $token = $this->builder
            ->expiresAt((new DateTimeImmutable())->modify('-10 minutes'))
            ->getToken($this->config->signer(), $this->config->signingKey())
        ;

        $this->expectException(ExpiredToken::class);
        $this->verifier->verifyIdToken($token);
    }

    public function testItRejectsANotYetIssuedToken(): void
    {
        $token = $this->builder
            ->issuedAt((new DateTimeImmutable())->modify('+10 minutes'))
            ->getToken($this->config->signer(), $this->config->signingKey())
        ;

        $this->expectException(IssuedInTheFuture::class);
        $this->verifier->verifyIdToken($token);
    }

    public function testItRejectsAnUnknownIssuer(): void
    {
        $token = $this->builder
            ->issuedBy('unknown')
            ->getToken($this->config->signer(), $this->config->signingKey())
        ;

        $this->expectException(InvalidToken::class);
        $this->verifier->verifyIdToken($token);
    }
}
