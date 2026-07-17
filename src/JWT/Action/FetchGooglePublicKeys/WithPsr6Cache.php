<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;

use DateTimeImmutable;
use DateTimeInterface;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;
use Kreait\Firebase\JWT\Contract\Expirable;
use Kreait\Firebase\JWT\Contract\Keys;
use Kreait\Firebase\JWT\Error\FetchingGooglePublicKeysFailed;
use Kreait\Firebase\JWT\Keys\ExpiringKeys;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Clock\ClockInterface;

/**
 * @internal
 */
final readonly class WithPsr6Cache implements Handler
{
    private const int CACHE_PAYLOAD_VERSION = 1;

    public function __construct(
        private Handler $handler,
        private CacheItemPoolInterface $cache,
        private ClockInterface $clock,
    ) {
    }

    public function handle(FetchGooglePublicKeys $action): Keys
    {
        $now = $this->clock->now();
        $cacheKey = md5($action::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        $cacheItem = $this->cache->getItem($cacheKey);

        $keys = $this->keysFromCachedValue($cacheItem->get());

        // We deliberately don't care if the cache item is expired here, as long as the keys
        // themselves are not expired
        if ($keys instanceof Expirable && !$keys->isExpiredAt($now)) {
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

        $cacheItem->set($this->cachedValueFromKeys($keys, $action));

        if ($keys instanceof Expirable) {
            $cacheItem->expiresAt($keys->expiresAt());
        } else {
            $cacheItem->expiresAfter($action->getFallbackCacheDuration()->value());
        }

        $this->cache->save($cacheItem);

        return $keys;
    }

    private function keysFromCachedValue(mixed $cached): ?Keys
    {
        if ($cached === null) {
            return null;
        }

        // Keep accepting object values written by previous library versions.
        if ($cached instanceof Keys) {
            return $cached;
        }

        if (!is_array($cached)) {
            return null;
        }

        // Only restore payloads written by this version.
        if (($cached['version'] ?? null) !== self::CACHE_PAYLOAD_VERSION) {
            return null;
        }

        $values = $cached['values'] ?? null;
        $expiresAt = $cached['expiresAt'] ?? null;

        if (!is_string($expiresAt)) {
            return null;
        }

        $values = $this->keyValuesFromCachedValue($values);

        if ($values === null) {
            return null;
        }

        try {
            $expirationTime = new DateTimeImmutable($expiresAt);
        } catch (\Exception) {
            return null;
        }

        return ExpiringKeys::withValuesAndExpirationTime($values, $expirationTime);
    }

    /**
     * @return array<non-empty-string, non-empty-string>|null
     */
    private function keyValuesFromCachedValue(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        foreach ($value as $keyId => $key) {
            if (!is_string($keyId) || $keyId === '' || !is_string($key) || $key === '') {
                return null;
            }
        }

        return $value;
    }

    /**
     * @return array{
     *     version: non-negative-int,
     *     values: array<non-empty-string, non-empty-string>,
     *     expiresAt: non-empty-string
     * }
     */
    private function cachedValueFromKeys(Keys $keys, FetchGooglePublicKeys $action): array
    {
        $expiresAt = $keys instanceof Expirable
            ? $keys->expiresAt()
            : $this->clock->now()->add($action->getFallbackCacheDuration()->value());

        return [
            'version' => self::CACHE_PAYLOAD_VERSION,
            'values' => $keys->all(),
            'expiresAt' => $expiresAt->format(DateTimeInterface::ATOM),
        ];
    }
}
