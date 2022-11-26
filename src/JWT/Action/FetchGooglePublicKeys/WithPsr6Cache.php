<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;

use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;
use Kreait\Firebase\JWT\Contract\Expirable;
use Kreait\Firebase\JWT\Contract\Keys;
use Kreait\Firebase\JWT\Error\FetchingGooglePublicKeysFailed;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Clock\ClockInterface;

/**
 * @internal
 */
final class WithPsr6Cache implements Handler
{
    private readonly CacheItemPoolInterface $cache;
    private readonly ClockInterface $clock;

    public function __construct(private readonly Handler $handler, CacheItemPoolInterface $cache, ClockInterface $clock)
    {
        $this->cache = $cache;
        $this->clock = $clock;
    }

    public function handle(FetchGooglePublicKeys $action): Keys
    {
        $now = $this->clock->now();
        $cacheKey = md5($action::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        $cacheItem = $this->cache->getItem($cacheKey);

        /** @var Keys|Expirable|null $keys */
        $keys = $cacheItem->get();

        // We deliberately don't care if the cache item is expired here, as long as the keys
        // themselves are not expired
        if ($keys instanceof Keys && $keys instanceof Expirable && !$keys->isExpiredAt($now)) {
            return $keys;
        }

        // Non-expiring keys coming from a cache hit can be returned as well
        if ($keys instanceof Keys && !($keys instanceof Expirable) && $cacheItem->isHit()) {
            return $keys;
        }

        // At this point, we have to re-fetch the keys, because either the cache item is a miss
        // or the value in the cache item is not a Keys object

        // We need fresh keys
        try {
            $keys = $this->handler->handle($action);
        } catch (FetchingGooglePublicKeysFailed $e) {
            $reason = sprintf(
                'The inner handler of %s (%s) failed in fetching keys: %s',
                self::class,
                $this->handler::class,
                $e->getMessage(),
            );

            throw FetchingGooglePublicKeysFailed::because($reason, $e->getCode(), $e);
        }

        $cacheItem->set($keys);

        if ($keys instanceof Expirable) {
            $cacheItem->expiresAt($keys->expiresAt());
        } else {
            $cacheItem->expiresAfter($action->getFallbackCacheDuration()->value());
        }

        $this->cache->save($cacheItem);

        return $keys;
    }
}
