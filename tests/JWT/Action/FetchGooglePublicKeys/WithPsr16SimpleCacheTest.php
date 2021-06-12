<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action\FetchGooglePublicKeys;

use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys\Handler;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys\WithPsr16SimpleCache;
use Kreait\Firebase\JWT\Error\FetchingGooglePublicKeysFailed;
use Kreait\Firebase\JWT\Keys\ExpiringKeys;
use Kreait\Firebase\JWT\Keys\StaticKeys;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\SimpleCache\CacheInterface;
use stdClass;

/**
 * @internal
 */
final class WithPsr16SimpleCacheTest extends TestCase
{
    /** @var CacheInterface|MockObject */
    private $cache;

    /** @var Handler|MockObject */
    private $inner;

    private ExpiringKeys $expiringKeys;

    private ExpiringKeys $expiredKeys;

    private StaticKeys $nonExpiringKeys;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inner = $this->createMock(Handler::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->expiringKeys = ExpiringKeys::withValuesAndExpirationTime(['ir' => 'relevant'], $this->clock->now()->modify('+1 hour'));
        $this->expiredKeys = $this->expiringKeys->withExpirationTime($this->clock->now()->modify('-1 hour'));
        $this->nonExpiringKeys = StaticKeys::withValues(['ir' => 'relevant']);
    }

    protected function createHandler(): Handler
    {
        return new WithPsr16SimpleCache($this->inner, $this->cache, $this->clock);
    }

    public function testItCachesFreshKeys(): void
    {
        $this->cache->method('get')->willReturn(null);
        $this->inner->expects($this->once())->method('handle')->willReturn($this->expiringKeys);
        $this->cache->expects($this->once())->method('set');

        $this->assertSame($this->expiringKeys, $this->createHandler()->handle($this->action));
    }

    public function testItReturnsCachedNonExpiredKeys(): void
    {
        $this->cache->method('get')->willReturn($this->expiringKeys);
        $this->inner->expects($this->never())->method($this->anything());

        $this->assertSame($this->expiringKeys, $this->createHandler()->handle($this->action));
    }

    public function testItReturnsCachedNonExpiringKeys(): void
    {
        $this->cache->method('get')->willReturn($this->nonExpiringKeys);
        $this->inner->expects($this->never())->method($this->anything());

        $this->assertSame($this->nonExpiringKeys, $this->createHandler()->handle($this->action));
    }

    public function testItRefreshesExpiredKeys(): void
    {
        $this->cache->method('get')->willReturn($this->expiredKeys);
        $this->inner->expects($this->once())->method('handle')->willReturn($this->expiringKeys);

        $this->assertSame($this->expiringKeys, $this->createHandler()->handle($this->action));
    }

    public function testItHandlesInvalidCacheContents(): void
    {
        $this->cache->method('get')->willReturn(new stdClass());
        $this->inner->expects($this->once())->method('handle')->willReturn($this->expiringKeys);

        $this->assertSame($this->expiringKeys, $this->createHandler()->handle($this->action));
    }

    public function testItCatchesErrorsCausedByTheInnerHandler(): void
    {
        $innerError = FetchingGooglePublicKeysFailed::because('reason');
        $this->inner->method($this->anything())->willThrowException($innerError);

        try {
            $this->createHandler()->handle($this->action);
            $this->fail('An error should have been thrown');
        } catch (FetchingGooglePublicKeysFailed $e) {
            $this->assertNotSame($innerError, $e);
            $this->assertSame($innerError, $e->getPrevious());
        }
    }
}
