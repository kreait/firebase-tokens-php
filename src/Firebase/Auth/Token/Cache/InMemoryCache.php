<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Cache;

use DateInterval;
use DateTimeImmutable;
use Psr\SimpleCache\CacheInterface;

final class InMemoryCache implements CacheInterface
{
    /** @var array<string, mixed> */
    private $items;

    /**
     * @param array<string, mixed> $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $now = new DateTimeImmutable();

        if ($item = $this->items[$key] ?? null) {
            list($expiresAt, $value) = $item;

            if ($now < $expiresAt) {
                return $value;
            }

            $this->delete($key);
        }

        return $default;
    }

    public function set($key, $value, $ttl = null)
    {
        $now = new DateTimeImmutable();
        $expires = null;

        if (\is_int($ttl) && $ttl > 0) {
            $expires = $now->modify("+{$ttl} seconds");
        } elseif ($ttl instanceof DateInterval) {
            $expires = $now->add($ttl);
        }

        if (!$expires) {
            $this->delete($key);

            return true;
        }

        $this->items[$key] = [$expires, $value];

        return true;
    }

    public function delete($key)
    {
        unset($this->items[$key]);

        return true;
    }

    public function clear()
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
     *
     * @return bool
     */
    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * @param iterable<string> $keys
     *
     * @return bool
     */
    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has($key)
    {
        if ($item = $this->items[$key] ?? null) {
            $expiresAt = $item[0];

            if (new DateTimeImmutable() < $expiresAt) {
                return true;
            }
        }

        return false;
    }
}
