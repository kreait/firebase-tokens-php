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

    /** @var FrozenClock */
    protected static $clock;

    /** @var Handler */
    private $handler;

    public function setUp()
    {
        $now = new DateTimeImmutable();
        $now = $now->setTimestamp($now->getTimestamp()); // Trim microseconds, just to be sure

        self::$clock = new FrozenClock($now);
        $this->handler = static::createHandler();
    }

    /** @test */
    public function it_creates_a_fully_customized_custom_token()
    {
        $action = CreateCustomToken::forUid($uid = 'uid')
            ->withCustomClaims($claims = ['first_claim' => 'first_value', 'second_claim' => 'second_value'])
            ->withTimeToLive($expirationTime = 13);

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

    /** @test */
    public function it_creates_a_custom_token_with_a_default_expiration_time_of_one_hour()
    {
        $payload = $this->handler->handle(CreateCustomToken::forUid('uid'))->payload();

        $this->assertSame(self::$clock->now()->getTimestamp(), $payload['iat']);
        $this->assertSame(self::$clock->now()->modify('+1 hour')->getTimestamp(), $payload['exp']);
    }

    /** @test */
    public function it_does_not_add_custom_claims_when_none_are_given()
    {
        $payload = $this->handler->handle(CreateCustomToken::forUid('uid'))->payload();

        $this->assertArrayNotHasKey('claims', $payload);
    }

    /** @test */
    public function it_fails_with_an_invalid_private_key()
    {
        $handler = static::createHandlerWithInvalidPrivateKey();

        $this->expectException(CustomTokenCreationFailed::class);
        $handler->handle(CreateCustomToken::forUid('uid'));
    }

    /** @test */
    public function it_is_stringable()
    {
        $token = $this->handler->handle(CreateCustomToken::forUid('uid'));

        $tokenString = (string) $token;

        // lazy test, I know
        $this->assertSame(2, substr_count($tokenString, '.'));
    }
}
