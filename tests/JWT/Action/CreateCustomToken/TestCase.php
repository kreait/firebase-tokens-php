<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action\CreateCustomToken;

use DateTimeImmutable;
use Kreait\Clock\FrozenClock;
use Kreait\Firebase\JWT\Action\CreateCustomToken;
use Kreait\Firebase\JWT\Action\CreateCustomToken\Handler;
use Kreait\Firebase\JWT\Error\CustomTokenCreationFailed;

/**
 * @internal
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    abstract protected static function createHandler(): Handler;

    abstract protected static function createHandlerWithInvalidPrivateKey(): Handler;

    protected static FrozenClock $clock;

    private Handler $handler;

    protected function setUp(): void
    {
        $now = new DateTimeImmutable();
        $now = $now->setTimestamp($now->getTimestamp()); // Trim microseconds, just to be sure

        self::$clock = new FrozenClock($now);
        $this->handler = static::createHandler();
    }

    public function testItCreatesAFullyCustomizedCustomToken(): void
    {
        $action = CreateCustomToken::forUid($uid = 'uid')
            ->withCustomClaims($claims = ['first_claim' => 'first_value', 'second_claim' => 'second_value'])
            ->withTimeToLive($expirationTime = 13)
        ;

        $token = $this->handler->handle($action);

        $headers = $token->headers();
        $payload = $token->payload();

        $this->assertArrayHasKey('alg', $headers);
        $this->assertArrayHasKey('typ', $headers);

        $this->assertSame($uid, $payload['uid']);
        $this->assertSame(self::$clock->now()->getTimestamp(), $payload['iat']);
        $this->assertSame(self::$clock->now()->modify('+'.$expirationTime.' seconds')->getTimestamp(), $payload['exp']);
        $this->assertEquals($claims, (array) $payload['claims']);
    }

    public function testItCreatesACustomTokenWithADefaultExpirationTimeOfOneHour(): void
    {
        $payload = $this->handler->handle(CreateCustomToken::forUid('uid'))->payload();

        $this->assertSame(self::$clock->now()->getTimestamp(), $payload['iat']);
        $this->assertSame(self::$clock->now()->modify('+1 hour')->getTimestamp(), $payload['exp']);
    }

    public function testItDoesNotAddCustomClaimsWhenNoneAreGiven(): void
    {
        $payload = $this->handler->handle(CreateCustomToken::forUid('uid'))->payload();

        $this->assertArrayNotHasKey('claims', $payload);
    }

    public function testItUsesATenantIdWhenGiven(): void
    {
        $action = CreateCustomToken::forUid('uid')->withTenantId($tenantId = 'my-tenant');

        $payload = $this->handler->handle($action)->payload();

        $this->assertArrayHasKey('tenant_id', $payload);
        $this->assertSame($payload['tenant_id'], $tenantId);
    }

    public function testItFailsWithAnInvalidPrivateKey(): void
    {
        $handler = static::createHandlerWithInvalidPrivateKey();

        $this->expectException(CustomTokenCreationFailed::class);
        $handler->handle(CreateCustomToken::forUid('uid'));
    }

    public function testItIsStringable(): void
    {
        $token = $this->handler->handle(CreateCustomToken::forUid('uid'));

        $tokenString = (string) $token;

        // lazy test, I know
        $this->assertSame(2, \mb_substr_count($tokenString, '.'));
    }
}
