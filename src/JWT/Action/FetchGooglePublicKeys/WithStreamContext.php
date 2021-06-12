<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;

use Kreait\Clock;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;
use Kreait\Firebase\JWT\Contract\Keys;
use Kreait\Firebase\JWT\Error\FetchingGooglePublicKeysFailed;
use Kreait\Firebase\JWT\Keys\ExpiringKeys;

final class WithStreamContext implements Handler
{
    private Clock $clock;

    public function __construct(Clock $clock)
    {
        $this->clock = $clock;
    }

    public function handle(FetchGooglePublicKeys $action): Keys
    {
        $keys = [];
        $ttls = [];

        foreach ($action->urls() as $url) {
            $result = $this->fetchKeysFromUrl($url);

            $keys[] = $result['keys'];
            $ttls[] = $result['ttl'];
        }

        $keys = \array_merge(...$keys);
        $ttl = \min($ttls);
        $now = $this->clock->now();

        $expiresAt = $ttl > 0
            ? $now->setTimestamp($now->getTimestamp() + $ttl)
            : $now->add($action->getFallbackCacheDuration()->value());

        return ExpiringKeys::withValuesAndExpirationTime($keys, $expiresAt);
    }

    /**
     * @return array{
     *                keys: array<string, string>,
     *                ttl: int
     *                }
     */
    private function fetchKeysFromUrl(string $url): array
    {
        $context = \stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Content-Type: application/json; charset=UTF-8',
            ],
        ]);

        $stream = \fopen($url, 'rb', false, $context);

        if (!\is_resource($stream)) {
            throw FetchingGooglePublicKeysFailed::because("{$url} could not be opened");
        }

        $metadata = \stream_get_meta_data($stream);
        $headers = $metadata['wrapper_data'] ?? [];

        $ttl = 0;

        foreach ($headers as $header) {
            if (\mb_stripos($header, 'cache-control') === false) {
                continue;
            }

            if (((int) \preg_match('/max-age=(\d+)/i', $header, $matches)) === 1) {
                $ttl = (int) $matches[1];
            }
        }

        $contents = \stream_get_contents($stream);

        \fclose($stream);

        if (!\is_string($contents)) {
            throw FetchingGooglePublicKeysFailed::because("{$url} returned no contents.");
        }

        $keys = \json_decode($contents, true);

        return [
            'keys' => $keys,
            'ttl' => $ttl,
        ];
    }
}
