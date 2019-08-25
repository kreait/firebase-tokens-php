<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;

use Kreait\Clock;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;
use Kreait\Firebase\JWT\Contract\Keys;
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
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Content-Type: application/json; charset=UTF-8',
            ],
        ]);

        $stream = fopen($action->url(), 'rb', false, $context);

        $metadata = stream_get_meta_data($stream);
        $headers = $metadata['wrapper_data'] ?? [];

        $expiresAt = null;

        foreach ($headers as $header) {
            if (stripos($header, 'cache-control') === false) {
                continue;
            }

            if (((int) preg_match('/max-age=(\d+)/i', $header, $matches)) === 1) {
                $maxAge = (int) $matches[1];
                $now = $this->clock->now();
                $expiresAt = $now->setTimestamp($now->getTimestamp() + $maxAge);
                break;
            }
        }

        $contents = stream_get_contents($stream);

        fclose($stream);

        $keys = json_decode($contents, true);

        if ($expiresAt) {
            return ExpiringKeys::withValuesAndExpirationTime($keys, $expiresAt);
        }

        return StaticKeys::withValues($keys);
    }
}
