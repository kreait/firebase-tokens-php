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

    /** @var int */
    private $ttl;

    protected function setUp()
    {
        $this->clock = new FrozenClock(new DateTimeImmutable());
        $this->cache = InMemoryCache::createEmpty()->withClock($this->clock);
        $this->ttl = 10;
    }

    /** @test */
    public function it_sets_gets_and_deletes()
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

    /** @test */
    public function it_returns_the_default_if_an_item_is_expired()
    {
        $this->cache->set('expired', 'value', new DateInterval('PT1S'));
        $this->clock->setTo($this->clock->now()->modify('+2 seconds'));

        $this->assertSame('default', $this->cache->get('expired', 'default'));
    }

    /**
     * @test
     * @dataProvider nullValues
     */
    public function it_deletes_keys_when_no_ttl_is_given()
    {
        $this->cache->set('foo', 'bar');
        $this->assertFalse($this->cache->has('foo'));
    }

    public function nullValues(): array
    {
        return [
            'null' => [null],
            'zero' => [0],
            'negative' => [-1],
        ];
    }

    /** @test */
    public function it_can_be_cleared()
    {
        $this->cache->set('foo', 'foo', $this->ttl);
        $this->cache->set('bar', 'bar', $this->ttl);

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
            $this->cache->set($key, $value, $this->ttl);
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

        $this->cache->setMultiple($values, $this->ttl);

        $this->assertEquals($values, $this->cache->getMultiple(array_keys($values)));
    }

    /** @test */
    public function it_can_delete_multiple_items()
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
