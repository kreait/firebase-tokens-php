<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action\FetchGooglePublicKeys;

use Beste\Cache\InMemoryCache;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys\Handler;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys\WithPsr6Cache;
use Kreait\Firebase\JWT\Contract\Keys;
use Kreait\Firebase\JWT\Error\FetchingGooglePublicKeysFailed;
use Kreait\Firebase\JWT\Keys\ExpiringKeys;
use Kreait\Firebase\JWT\Keys\StaticKeys;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Cache\CacheItemPoolInterface;
use stdClass;

/**
 * @internal
 */
final class WithPsr6CacheTest extends TestCase
{
    private CacheItemPoolInterface $cache;

    private Handler&MockObject $inner;

    private ExpiringKeys $expiringKeys;

    private ExpiringKeys $expiredKeys;

    private StaticKeys $nonExpiringKeys;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new InMemoryCache($this->clock);
        $this->inner = $this->createMock(Handler::class);

        $this->expiringKeys = ExpiringKeys::withValuesAndExpirationTime(['ir' => 'relevant'], $this->clock->now()->modify('+1 hour'));
        $this->expiredKeys = $this->expiringKeys->withExpirationTime($this->clock->now()->modify('-1 hour'));
        $this->nonExpiringKeys = StaticKeys::withValues(['ir' => 'relevant']);
    }

    public function testItCachesFreshKeys(): void
    {
        $this->inner->expects($this->once())->method('handle')->willReturn($this->expiringKeys);

        $this->assertSame($this->expiringKeys, $this->createHandler()->handle($this->action));

        $payload = $this->cachedValue();

        $this->assertNotInstanceOf(Keys::class, $payload);
        $this->assertIsArray($payload);
        $this->assertSame(['ir' => 'relevant'], $payload['values'] ?? null);
        $this->assertSame($this->expiringKeys->expiresAt()->format(DATE_ATOM), $payload['expiresAt'] ?? null);
    }

    public function testItCachesNonExpiringKeysWithFallbackExpiration(): void
    {
        $this->inner->expects($this->once())->method('handle')->willReturn($this->nonExpiringKeys);

        $this->assertSame($this->nonExpiringKeys, $this->createHandler()->handle($this->action));

        $payload = $this->cachedValue();

        $this->assertNotInstanceOf(Keys::class, $payload);
        $this->assertIsArray($payload);
        $this->assertSame(['ir' => 'relevant'], $payload['values'] ?? null);
        $this->assertSame($this->clock->now()->modify('+1 hour')->format(DATE_ATOM), $payload['expiresAt'] ?? null);

        $this->clock->setTo($this->clock->now()->modify('+59 minutes'));
        $this->assertTrue($this->cache->getItem($this->cacheKey())->isHit());

        $this->clock->setTo($this->clock->now()->modify('+2 minutes'));
        $this->assertFalse($this->cache->getItem($this->cacheKey())->isHit());
    }

    public function testItReturnsCachedNonExpiredKeys(): void
    {
        $this->storeCachedValue($this->expiringKeys);

        $this->inner->expects($this->never())->method($this->anything());

        $this->assertSame($this->expiringKeys, $this->createHandler()->handle($this->action));
    }

    public function testItReturnsCachedNonExpiredKeysFromArrayPayload(): void
    {
        $this->storeCachedValue([
            'version' => 1,
            'values' => ['ir' => 'relevant'],
            'expiresAt' => $this->expiringKeys->expiresAt()->format(DATE_ATOM),
        ]);
        $this->inner->expects($this->never())->method($this->anything());

        $keys = $this->createHandler()->handle($this->action);

        $this->assertInstanceOf(ExpiringKeys::class, $keys);
        $this->assertSame(['ir' => 'relevant'], $keys->all());
        $this->assertEquals($this->expiringKeys->expiresAt(), $keys->expiresAt());
    }

    public function testItReturnsCachedNonExpiringKeys(): void
    {
        $this->storeCachedValue($this->nonExpiringKeys);

        $this->inner->expects($this->never())->method($this->anything());

        $this->assertSame($this->nonExpiringKeys, $this->createHandler()->handle($this->action));
    }

    public function testItRefreshesExpiredKeys(): void
    {
        $this->storeCachedValue($this->expiredKeys);

        $this->inner->expects($this->once())->method('handle')->willReturn($this->expiringKeys);

        $this->assertSame($this->expiringKeys, $this->createHandler()->handle($this->action));
    }

    public function testItRefreshesExpiredKeysFromArrayPayload(): void
    {
        $this->storeCachedValue([
            'version' => 1,
            'values' => ['ir' => 'relevant'],
            'expiresAt' => $this->expiredKeys->expiresAt()->format(DATE_ATOM),
        ]);
        $this->inner->expects($this->once())->method('handle')->willReturn($this->expiringKeys);

        $this->assertSame($this->expiringKeys, $this->createHandler()->handle($this->action));
    }

    public function testItHandlesInvalidCacheContents(): void
    {
        $this->storeCachedValue(new stdClass());

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

    protected function createHandler(): Handler
    {
        return new WithPsr6Cache($this->inner, $this->cache, $this->clock);
    }

    private function cachedValue(): mixed
    {
        return $this->cache->getItem($this->cacheKey())->get();
    }

    private function storeCachedValue(mixed $value): void
    {
        $item = $this->cache->getItem($this->cacheKey());
        $item->set($value);

        $this->cache->save($item);
    }

    private function cacheKey(): string
    {
        return md5($this->action::class);
    }
}
