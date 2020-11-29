<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Cache;

use DateInterval;
use Kreait\Clock;
use Kreait\Clock\SystemClock;
use Psr\SimpleCache\CacheInterface;

final class InMemoryCache implements CacheInterface
{
    /** @var array<string, mixed> */
    private $items = [];

    /** @var Clock */
    private $clock;

    private function __construct()
    {
        $this->clock = new SystemClock();
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    public function withClock(Clock $clock): self
    {
        $cache = new self();
        $cache->clock = $clock;

        return $cache;
    }

    public function get($key, $default = null)
    {
        $now = $this->clock->now();

        if ($item = $this->items[$key] ?? null) {
            list($expiresAt, $value) = $item;

            if (!$expiresAt || $expiresAt > $now) {
                return $value;
            }

            $this->delete($key);
        }

        return $default;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $now = $this->clock->now();
        $expires = null;

        if ($ttl instanceof DateInterval) {
            $expires = $now->add($ttl);
        }

        if (\is_int($ttl) && $ttl > 0) {
            $expires = $now->modify("+{$ttl} seconds");
        }

        if (!$expires) {
            $this->delete($key);

            return true;
        }

        $this->items[$key] = [$expires, $value];

        return true;
    }

    public function delete($key): bool
    {
        unset($this->items[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->items = [];

        return true;
    }

    /**
     * @param iterable<string> $keys
     * @param mixed $default
     *
     * @return array<string, mixed>
     */
    public function getMultiple($keys, $default = null)
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * @param iterable<mixed> $values
     * @param int|DateInterval|null $ttl
     */
    public function setMultiple($values, $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * @param iterable<string> $keys
     */
    public function deleteMultiple($keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has($key): bool
    {
        $now = $this->clock->now();

        if ($item = $this->items[$key] ?? null) {
            $expiresAt = $item[0];

            if ($now < $expiresAt) {
                return true;
            }
        }

        return false;
    }
}
