<?php

namespace Firebase\Auth\Token\Tests;

use Firebase\Auth\Token\HttpKeyStore;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Psr\SimpleCache\CacheInterface;

class HttpKeyStoreTest extends TestCase
{
    /**
     * @var HttpKeyStore
     */
    private $store;

    /**
     * @var array
     */
    private static $liveKeys;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var CacheInterface
     */
    private $cache;

    public static function setUpBeforeClass()
    {
        self::$liveKeys = json_decode(file_get_contents(HttpKeyStore::KEYS_URL), true);
    }

    protected function setUp()
    {
        $this->client = $this->createMock(ClientInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->store = new HttpKeyStore($this->client, $this->cache);
    }

    public function testGetKey()
    {
        $this->client->expects($this->once())
            ->method('request')
            ->willReturn(new Response(200, [], '{"foo":"bar"}'));

        $this->assertEquals('bar', $this->store->get('foo'));
    }

    public function testGetKeyFromGoogle()
    {
        $keyId = array_rand(self::$liveKeys);
        $key = self::$liveKeys[$keyId];

        $store = new HttpKeyStore();

        $this->assertEquals($key, $store->get($keyId));
    }

    public function testGetNonExistingKey()
    {
        $this->client->expects($this->once())
            ->method('request')
            ->willReturn(new Response(200, [], '[]'));

        $this->expectException(\OutOfBoundsException::class);

        $this->store->get('foo');
    }

    public function testGetKeyFromCache()
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn('bar');

        $this->client->expects($this->never())
            ->method('request');

        $this->assertSame('bar', $this->store->get('foo'));
    }
}
