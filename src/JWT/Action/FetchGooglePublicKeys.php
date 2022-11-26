<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action;

use DateInterval;
use Kreait\Firebase\JWT\Value\Duration;

final class FetchGooglePublicKeys
{
    public const DEFAULT_URLS = [
        'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com',
        'https://www.googleapis.com/oauth2/v1/certs',
        'https://www.googleapis.com/identitytoolkit/v3/relyingparty/publicKeys',
    ];
    public const DEFAULT_FALLBACK_CACHE_DURATION = 'PT1H';

    /** @var array<int, string> */
    private readonly array $urls;

    /**
     * @param array<array-key, string> $urls
     */
    private function __construct(array $urls, private Duration $fallbackCacheDuration)
    {
        $this->urls = array_values($urls);
    }

    public static function fromGoogle(): self
    {
        return new self(self::DEFAULT_URLS, Duration::fromDateIntervalSpec(self::DEFAULT_FALLBACK_CACHE_DURATION));
    }

    /**
     * Use this method only if Google has changed the default URL and the library hasn't been updated yet.
     */
    public static function fromUrl(string $url): self
    {
        return new self([$url], Duration::fromDateIntervalSpec(self::DEFAULT_FALLBACK_CACHE_DURATION));
    }

    /**
     * A response from the Google APIs should have a cache control header that determines when the keys expire.
     * If it doesn't have one, fall back to this value.
     */
    public function ifKeysDoNotExpireCacheFor(Duration|DateInterval|string|int $duration): self
    {
        $duration = Duration::make($duration);

        $action = clone $this;
        $action->fallbackCacheDuration = $duration;

        return $action;
    }

    /**
     * @return array<int, string>
     */
    public function urls(): array
    {
        return $this->urls;
    }

    public function getFallbackCacheDuration(): Duration
    {
        return $this->fallbackCacheDuration;
    }
}
