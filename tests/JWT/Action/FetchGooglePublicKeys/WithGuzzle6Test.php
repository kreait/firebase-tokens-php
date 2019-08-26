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
use Kreait\Firebase\JWT\Keys\StaticKeys;

/**
 * @internal
 */
final class WithGuzzle6Test extends TestCase
{
    /** @var MockHandler */
    private $mockHandler;

    public function setUp()
    {
        parent::setUp();

        $this->mockHandler = new MockHandler();
    }

    protected function createHandler(): Handler
    {
        return new WithGuzzle6(new Client(['handler' => $this->mockHandler]), $this->clock);
    }

    /** @test */
    public function it_returns_keys()
    {
        $this->mockHandler->append(new Response(200, ['Cache-Control' => 'max-age=1'], '{}'));

        parent::it_returns_keys();
    }

    /** @test */
    public function it_returns_expiring_keys()
    {
        $this->mockHandler->append(new Response(200, ['Cache-Control' => 'max-age=1'], '{}'));

        /** @var ExpiringKeys $keys */
        $keys = $this->createHandler()->handle($this->action);

        $this->assertInstanceOf(ExpiringKeys::class, $keys);
        $this->assertGreaterThan($this->clock->now(), $keys->expiresAt());
    }

    /** @test */
    public function it_returns_a_set_of_static_keys()
    {
        $this->mockHandler->append(new Response(200, [/* no cache-control header */], '{}'));

        /** @var StaticKeys $keys */
        $keys = $this->createHandler()->handle($this->action);

        $this->assertInstanceOf(StaticKeys::class, $keys);
    }

    /** @test */
    public function it_handles_non_success_responses()
    {
        $this->mockHandler->append(new Response(500));

        $this->expectException(FetchingGooglePublicKeysFailed::class);
        $this->createHandler()->handle($this->action);
    }

    /** @test */
    public function it_handles_connect_exceptions()
    {
        $error = new ConnectException('something went wrong', new Request('GET', 'bogus'));

        $this->mockHandler->append($error);

        $this->expectException(FetchingGooglePublicKeysFailed::class);
        $this->expectExceptionMessageRegExp('/something went wrong/');
        $this->createHandler()->handle($this->action);
    }
}
