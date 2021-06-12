<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Cache;

use DateInterval;
use DateTimeImmutable;
use Kreait\Clock\FrozenClock;
use Kreait\Firebase\JWT\Cache\InMemoryCache;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class InMemoryCacheTest extends TestCase
{
    private FrozenClock $clock;

    private InMemoryCache $cache;

    private int $ttl;

    protected function setUp(): void
    {
        $this->clock = new FrozenClock(new DateTimeImmutable());
        $this->cache = InMemoryCache::createEmpty()->withClock($this->clock);
        $this->ttl = 10;
    }

    public function testItSetsGetsAndDeletes(): void
    {
        $this->assertFalse($this->cache->has('foo'));
        $this->assertNull($this->cache->get('foo'));
        $this->cache->set('foo', 'bar', $this->ttl);
        $this->assertTrue($this->cache->has('foo'));
        $this->assertSame('bar', $this->cache->get('foo'));
        $this->cache->delete('foo');
        $this->assertFalse($this->cache->has('foo'));
        $this->assertNull($this->cache->get('foo'));
    }

    public function testItReturnsTheDefaultIfAnItemIsExpired(): void
    {
        $this->cache->set('expired', 'value', new DateInterval('PT1S'));
        $this->clock->setTo($this->clock->now()->modify('+2 seconds'));

        $this->assertSame('default', $this->cache->get('expired', 'default'));
    }

    /**
     * @dataProvider nullValues
     */
    public function testItDeletesKeysWhenNoTtlIsGiven(): void
    {
        $this->cache->set('foo', 'bar');
        $this->assertFalse($this->cache->has('foo'));
    }

    /**
     * @return array<string, array<int|null>>
     */
    public function nullValues(): array
    {
        return [
            'null' => [null],
            'zero' => [0],
            'negative' => [-1],
        ];
    }

    public function testItCanBeCleared(): void
    {
        $this->cache->set('foo', 'foo', $this->ttl);
        $this->cache->set('bar', 'bar', $this->ttl);

        $this->cache->clear();

        $this->assertNull($this->cache->get('foo'));
        $this->assertNull($this->cache->get('bar'));
    }

    public function testItReturnsMultipleItems(): void
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

    public function testItCanSetMultipleItems(): void
    {
        $values = ['foo' => 'foo', 'bar' => 'bar'];

        $this->cache->setMultiple($values, $this->ttl);

        $this->assertEquals($values, $this->cache->getMultiple(\array_keys($values)));
    }

    public function testItCanDeleteMultipleItems(): void
    {
        $this->cache->set('first', 'first', $this->ttl);
        $this->cache->set('second', 'second', $this->ttl);
        $this->cache->set('third', 'third', $this->ttl);

        $this->cache->deleteMultiple(['first', 'second']);

        $this->assertNull($this->cache->get('first'));
        $this->assertNull($this->cache->get('second'));
        $this->assertSame('third', $this->cache->get('third'));
    }
}
