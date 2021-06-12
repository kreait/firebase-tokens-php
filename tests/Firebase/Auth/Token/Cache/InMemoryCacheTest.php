<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Tests\Cache;

use DateInterval;
use Firebase\Auth\Token\Cache\InMemoryCache;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class InMemoryCacheTest extends TestCase
{
    private InMemoryCache $cache;

    private int $ttl = 10;

    protected function setUp(): void
    {
        $this->cache = new InMemoryCache();
    }

    public function testSetAndGetAndDelete(): void
    {
        $this->assertNull($this->cache->get('foo'));
        $this->cache->set('foo', 'bar', $this->ttl);
        $this->assertSame('bar', $this->cache->get('foo'));
        $this->cache->delete('foo');
        $this->assertNull($this->cache->get('foo'));
    }

    public function testExpiredItemResultsInDefault(): void
    {
        $this->cache->set('expired', 'value', 0);

        $this->assertSame('default', $this->cache->get('expired', 'default'));
    }

    public function testSetWithDateInterval(): void
    {
        $this->cache->set('foo', 'bar', new DateInterval('PT10S'));

        $this->assertSame('bar', $this->cache->get('foo'));
    }

    public function setWithTtlBeingNull(): void
    {
        $this->cache->set('foo', 'bar');
        $this->assertFalse($this->cache->has('foo'));
    }

    public function setWithTtlBeingZero(): void
    {
        $this->cache->set('foo', 'bar', 0);
        $this->assertFalse($this->cache->has('foo'));
    }

    public function testClear(): void
    {
        $this->cache->set('foo', 'foo', $this->ttl);
        $this->cache->set('bar', 'bar', $this->ttl);

        $this->cache->clear();

        $this->assertNull($this->cache->get('foo'));
        $this->assertNull($this->cache->get('bar'));
    }

    public function testGetMultiple(): void
    {
        $values = ['foo' => 'foo', 'bar' => 'bar'];
        $expected = $values + ['default' => 'default'];

        foreach ($values as $key => $value) {
            $this->cache->set($key, $value, $this->ttl);
        }

        $this->assertEquals(
            $expected,
            $this->cache->getMultiple(\array_keys($expected), 'default')
        );
    }

    public function testSetMultiple(): void
    {
        $values = ['foo' => 'foo', 'bar' => 'bar'];

        $this->cache->setMultiple($values, $this->ttl);

        $this->assertEquals($values, $this->cache->getMultiple(\array_keys($values)));
    }

    public function testDeleteMultiple(): void
    {
        $this->cache->set('first', 'first', $this->ttl);
        $this->cache->set('second', 'second', $this->ttl);
        $this->cache->set('third', 'third', $this->ttl);

        $this->cache->deleteMultiple(['first', 'second']);

        $this->assertNull($this->cache->get('first'));
        $this->assertNull($this->cache->get('second'));
        $this->assertSame('third', $this->cache->get('third'));
    }

    public function testHas(): void
    {
        $this->assertFalse($this->cache->has('key'));

        $this->cache->set('key', 'value', $this->ttl);

        $this->assertTrue($this->cache->has('key'));
    }
}
