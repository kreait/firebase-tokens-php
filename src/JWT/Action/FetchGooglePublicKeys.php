<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action;

use DateInterval;

final class FetchGooglePublicKeys
{
    const DEFAULT_URL = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';
    const DEFAULT_FALLBACK_CACHE_DURATION = 'PT1H';

    private $url = self::DEFAULT_URL;
    private $fallbackCacheDuration;

    private function __construct()
    {
        $this->fallbackCacheDuration = new DateInterval(self::DEFAULT_FALLBACK_CACHE_DURATION);
    }

    public static function fromGoogle(): self
    {
        return new self();
    }

    /**
     * Use this method only if Google has changed the default URL and the library hasn't been updated yet.
     */
    public static function fromUrl(string $url): self
    {
        $action = new self();
        $action->url = $url;

        return $action;
    }

    public function ifKeysDoNotExpireCacheFor(DateInterval $duration): self
    {
        $action = new self();
        $action->fallbackCacheDuration = $duration;

        return $action;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function getFallbackCacheDuration(): DateInterval
    {
        return $this->fallbackCacheDuration;
    }
}
