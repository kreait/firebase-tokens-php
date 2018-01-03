<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Cache;

use DateInterval;
use DateTimeImmutable;
use Psr\SimpleCache\CacheInterface;

final class InMemoryCache implements CacheInterface
{
    /**
     * @var array
     */
    private $items;

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function get($key, $default = null)
    {
        if ($item = $this->items[$key] ?? null) {
            list($expiresAt, $value) = $item;

            if (!$expiresAt || (new \DateTime() < $expiresAt)) {
                return $value;
            }

            $this->delete($key);
        }

        return $default;
    }

    public function set($key, $value, $ttl = null)
    {
        $expires = null;

        if (ctype_digit((string) $ttl)) {
            $ttl = new DateInterval(sprintf('PT%dS', $ttl));
        }

        if ($ttl) {
            $expires = (new DateTimeImmutable())->add($ttl);
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

    public function getMultiple($keys, $default = null)
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

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

            if (!$expiresAt || (new \DateTime() < $expiresAt)) {
                return true;
            }
        }

        return false;
    }
}
