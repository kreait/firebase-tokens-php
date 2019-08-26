<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Cache;

use DateInterval;
use DateTimeImmutable;
use Kreait\Clock\FrozenClock;
use Kreait\Firebase\JWT\Cache\InMemoryCache;
use PHPUnit\Framework\TestCase;

final class InMemoryCacheTest extends TestCase
{
    /** @var FrozenClock */
    private $clock;

    /** @var InMemoryCache */
    private $cache;

    protected function setUp()
    {
        $this->clock = new FrozenClock(new DateTimeImmutable());
        $this->cache = InMemoryCache::createEmpty()->withClock($this->clock);
    }

    /** @test */
    public function it_sets_gets_and_deletes()
    {
        $this->assertFalse($this->cache->has('foo'));
        $this->assertNull($this->cache->get('foo'));
        $this->cache->set('foo', 'bar', 60);
        $this->assertTrue($this->cache->has('foo'));
        $this->assertSame('bar', $this->cache->get('foo'));
        $this->cache->delete('foo');
        $this->assertFalse($this->cache->has('foo'));
        $this->assertNull($this->cache->get('foo'));
    }

    /** @test */
    public function it_returns_the_default_if_an_item_is_expired()
    {
        $this->cache->set('expired', 'value', new DateInterval('PT1S'));
        $this->clock->setTo($this->clock->now()->modify('+2 seconds'));

        $this->assertSame('default', $this->cache->get('expired', 'default'));
    }

    /** @test */
    public function it_can_be_cleared()
    {
        $this->cache->set('foo', 'foo');
        $this->cache->set('bar', 'bar');

        $this->cache->clear();

        $this->assertNull($this->cache->get('foo'));
        $this->assertNull($this->cache->get('bar'));
    }

    /** @test */
    public function it_returns_multiple_items()
    {
        $values = ['foo' => 'foo', 'bar' => 'bar'];
        $expected = $values + ['default' => 'default'];

        foreach ($values as $key => $value) {
            $this->cache->set($key, $value);
        }

        $this->assertEquals(
            $expected,
            $this->cache->getMultiple(array_keys($expected), 'default')
        );
    }

    /** @test */
    public function it_can_set_multiple_items()
    {
        $values = ['foo' => 'foo', 'bar' => 'bar'];

        $this->cache->setMultiple($values);

        $this->assertEquals($values, $this->cache->getMultiple(array_keys($values)));
    }

    /** @test */
    public function it_can_delete_multiple_items()
    {
        $this->cache->set('first', 'first');
        $this->cache->set('second', 'second');
        $this->cache->set('third', 'third');

        $this->cache->deleteMultiple(['first', 'second']);

        $this->assertNull($this->cache->get('first'));
        $this->assertNull($this->cache->get('second'));
        $this->assertSame('third', $this->cache->get('third'));
    }
}
