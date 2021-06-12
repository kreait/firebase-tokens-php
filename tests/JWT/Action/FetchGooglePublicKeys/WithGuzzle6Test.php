<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action\FetchGooglePublicKeys;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys\Handler;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys\WithGuzzle6;
use Kreait\Firebase\JWT\Error\FetchingGooglePublicKeysFailed;
use Kreait\Firebase\JWT\Keys\ExpiringKeys;

/**
 * @internal
 */
final class WithGuzzle6Test extends TestCase
{
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockHandler = new MockHandler();
    }

    protected function createHandler(): Handler
    {
        return new WithGuzzle6(new Client(['handler' => $this->mockHandler]), $this->clock);
    }

    public function testItReturnsKeys(): void
    {
        $this->mockHandler->append(new Response(200, ['Cache-Control' => 'max-age=1'], '{}'));
        $this->mockHandler->append(new Response(200, ['Cache-Control' => 'max-age=1'], '{}'));

        parent::testItReturnsKeys();
    }

    public function testItReturnsExpiringKeys(): void
    {
        $this->mockHandler->append(new Response(200, ['Cache-Control' => 'max-age=1'], '{}'));
        $this->mockHandler->append(new Response(200, ['Cache-Control' => 'max-age=1'], '{}'));

        /** @var ExpiringKeys $keys */
        $keys = $this->createHandler()->handle($this->action);

        $this->assertInstanceOf(ExpiringKeys::class, $keys);
        $this->assertGreaterThan($this->clock->now(), $keys->expiresAt());
        $this->assertTrue($keys->isExpiredAt($this->clock->now()->modify('+2 seconds')));
    }

    public function testItHandlesNonSuccessResponses(): void
    {
        $this->mockHandler->append(new Response(500));

        $this->expectException(FetchingGooglePublicKeysFailed::class);
        $this->createHandler()->handle($this->action);
    }

    public function testItHandlesConnectExceptions(): void
    {
        $error = new ConnectException('something went wrong', new Request('GET', 'bogus'));

        $this->mockHandler->append($error);

        $this->expectException(FetchingGooglePublicKeysFailed::class);
        $this->createHandler()->handle($this->action);
    }
}
