<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;
use Kreait\Firebase\JWT\Contract\Keys;
use Kreait\Firebase\JWT\Error\FetchingGooglePublicKeysFailed;
use Kreait\Firebase\JWT\Keys\ExpiringKeys;
use StellaMaris\Clock\ClockInterface;

/**
 * @internal
 */
final class WithGuzzle implements Handler
{
    private ClientInterface $client;

    private ClockInterface $clock;

    public function __construct(ClientInterface $client, ClockInterface $clock)
    {
        $this->client = $client;
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
     *     keys: array<string, string>,
     *     ttl: int
     * }
     */
    private function fetchKeysFromUrl(string $url): array
    {
        try {
            $response = $this->client->request(RequestMethod::METHOD_GET, $url, [
                'http_errors' => false,
                'headers' => [
                    'Content-Type' => 'Content-Type: application/json; charset=UTF-8',
                ],
            ]);
        } catch (GuzzleException $e) {
            throw FetchingGooglePublicKeysFailed::because("The connection to {$url} failed: ".$e->getMessage(), $e->getCode(), $e);
        }

        if (($statusCode = $response->getStatusCode()) !== 200) {
            throw FetchingGooglePublicKeysFailed::because(
                "Unexpected status code {$statusCode} when trying to fetch public keys from {$url}"
            );
        }

        $ttl = \preg_match('/max-age=(\d+)/i', $response->getHeaderLine('Cache-Control'), $matches)
            ? (int) $matches[1]
            : 0;

        try {
            $keys = \json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw FetchingGooglePublicKeysFailed::because('Unexpected response: '.$e->getMessage());
        }

        return [
            'keys' => $keys,
            'ttl' => $ttl,
        ];
    }
}
