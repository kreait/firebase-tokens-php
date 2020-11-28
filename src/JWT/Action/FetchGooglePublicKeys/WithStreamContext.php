<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;

use Kreait\Clock;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;
use Kreait\Firebase\JWT\Contract\Keys;
use Kreait\Firebase\JWT\Error\FetchingGooglePublicKeysFailed;
use Kreait\Firebase\JWT\Keys\ExpiringKeys;
use Kreait\Firebase\JWT\Keys\StaticKeys;

final class WithStreamContext implements Handler
{
    /** @var Clock */
    private $clock;

    public function __construct(Clock $clock)
    {
        $this->clock = $clock;
    }

    public function handle(FetchGooglePublicKeys $action): Keys
    {
        $context = \stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Content-Type: application/json; charset=UTF-8',
            ],
        ]);

        $stream = \fopen($action->url(), 'rb', false, $context);

        if (!\is_resource($stream)) {
            throw FetchingGooglePublicKeysFailed::because("{$action->url()} could not be opened");
        }

        $metadata = \stream_get_meta_data($stream);
        $headers = $metadata['wrapper_data'] ?? [];

        $expiresAt = null;

        foreach ($headers as $header) {
            if (\mb_stripos($header, 'cache-control') === false) {
                continue;
            }

            if (((int) \preg_match('/max-age=(\d+)/i', $header, $matches)) === 1) {
                $maxAge = (int) $matches[1];
                $now = $this->clock->now();
                $expiresAt = $now->setTimestamp($now->getTimestamp() + $maxAge);
                break;
            }
        }

        $contents = \stream_get_contents($stream);

        \fclose($stream);

        if (!\is_string($contents)) {
            throw FetchingGooglePublicKeysFailed::because("{$action->url()} returned no contents.");
        }

        $keys = \json_decode($contents, true);

        if ($expiresAt) {
            return ExpiringKeys::withValuesAndExpirationTime($keys, $expiresAt);
        }

        return StaticKeys::withValues($keys);
    }
}
