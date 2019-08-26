<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;

use Kreait\Clock;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;
use Kreait\Firebase\JWT\Contract\Expirable;
use Kreait\Firebase\JWT\Contract\Keys;
use Kreait\Firebase\JWT\Error\FetchingGooglePublicKeysFailed;
use Psr\SimpleCache\CacheInterface;

final class WithPsr16SimpleCache implements Handler
{
    /** @var Handler */
    private $handler;

    /** @var CacheInterface */
    private $cache;

    /** @var Clock */
    private $clock;

    public function __construct(Handler $handler, CacheInterface $cache, Clock $clock)
    {
        $this->handler = $handler;
        $this->cache = $cache;
        $this->clock = $clock;
    }

    public function handle(FetchGooglePublicKeys $action): Keys
    {
        $now = $this->clock->now();

        $cacheKey = md5(get_class($action));

        /** @noinspection PhpUnhandledExceptionInspection */
        /** @var Keys|null $keys */
        $keys = $this->cache->get($cacheKey);

        if ($keys instanceof Keys && $keys instanceof Expirable && !$keys->isExpiredAt($now)) {
            return $keys;
        }

        if ($keys instanceof Keys && !($keys instanceof Expirable)) {
            return $keys;
        }

        try {
            $keys = $this->handler->handle($action);
        } catch (FetchingGooglePublicKeysFailed $e) {
            $reason = sprintf(
                'The inner handler of %s (%s) failed in fetching keys: %s',
                __CLASS__, get_class($this->handler), $e->getMessage()
            );

            throw FetchingGooglePublicKeysFailed::because($reason, $e->getCode(), $e);
        }

        $ttl = ($keys instanceof Expirable)
            ? $keys->expiresAt()->getTimestamp() - $now->getTimestamp()
            : $now->add($action->getFallbackCacheDuration()->value());

        /* @noinspection PhpUnhandledExceptionInspection */
        $this->cache->set($cacheKey, $keys, $ttl);

        return $keys;
    }
}
