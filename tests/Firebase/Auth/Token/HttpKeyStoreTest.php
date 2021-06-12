<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Tests;

use Firebase\Auth\Token\HttpKeyStore;
use OutOfBoundsException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\SimpleCache\CacheInterface;

/**
 * @internal
 */
class HttpKeyStoreTest extends TestCase
{
    private HttpKeyStore $store;

    /** @var array<string, string> */
    private static array $liveKeys;

    /** @var CacheInterface|MockObject */
    private $cache;

    public static function setUpBeforeClass(): void
    {
        $keys = [];

        foreach (HttpKeyStore::KEY_URLS as $url) {
            foreach ((array) \json_decode((string) \file_get_contents($url), true) as $keyId => $key) {
                $keys[$keyId] = $key;
            }
        }

        self::$liveKeys = $keys;
    }

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);

        $this->store = new HttpKeyStore(null, $this->cache);
    }

    public function testGetKeyFromGoogle(): void
    {
        $keyId = \array_rand(self::$liveKeys);
        $key = self::$liveKeys[$keyId];

        $this->assertEquals($key, $this->store->get($keyId));
    }

    public function testGetNonExistingKey(): void
    {
        $this->expectException(OutOfBoundsException::class);

        $this->store->get('non_existing');
    }

    public function testGetKeyFromCache(): void
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->with($this->anything())
            ->willReturn(['foo' => 'bar'])
        ;

        $this->assertSame('bar', $this->store->get('foo'));
    }
}
