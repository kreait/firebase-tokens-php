<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Tests;

use Firebase\Auth\Token\HttpKeyStore;
use OutOfBoundsException;
use Psr\SimpleCache\CacheInterface;

/**
 * @internal
 */
class HttpKeyStoreTest extends TestCase
{
    /** @var HttpKeyStore */
    private $store;

    /** @var array */
    private static $liveKeys;

    /** @var CacheInterface */
    private $cache;

    public static function setUpBeforeClass(): void
    {
        self::$liveKeys = (static function () {
            $keys = [];

            foreach (HttpKeyStore::KEY_URLS as $url) {
                $keys[] = \json_decode(\file_get_contents($url), true);
            }

            return \array_merge(...$keys);
        })();
    }

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);

        $this->store = new HttpKeyStore(null, $this->cache);
    }

    public function testGetKeyFromGoogle()
    {
        $keyId = \array_rand(self::$liveKeys);
        $key = self::$liveKeys[$keyId];

        $this->assertEquals($key, $this->store->get($keyId));
    }

    public function testGetNonExistingKey()
    {
        $this->expectException(OutOfBoundsException::class);

        $this->store->get('non_existing');
    }

    public function testGetKeyFromCache()
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->with($this->anything())
            ->willReturn(['foo' => 'bar']);

        $this->assertSame('bar', $this->store->get('foo'));
    }
}
