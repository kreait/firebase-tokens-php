<?php

declare(strict_types=1);

namespace Firebase\Auth\Token;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Firebase\Auth\Token\Cache\InMemoryCache;
use Firebase\Auth\Token\Domain\KeyStore;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use OutOfBoundsException;
use Psr\SimpleCache\CacheInterface;

/**
 * @see https://firebase.google.com/docs/auth/admin/verify-id-tokens#verify_id_tokens_using_a_third-party_jwt_library
 */
final class HttpKeyStore implements KeyStore
{
    /** @deprecated 1.15.0 */
    public const KEYS_URL = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';

    public const KEY_URLS = [
        'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com',
        'https://www.googleapis.com/oauth2/v1/certs',
    ];

    private ClientInterface $client;

    private CacheInterface $cache;

    public function __construct(ClientInterface $client = null, CacheInterface $cache = null)
    {
        $this->client = $client ?? new Client();
        $this->cache = $cache ?? new InMemoryCache();
    }

    public function get($keyId)
    {
        $keys = $this->fetchKeys();

        if (isset($keys[$keyId])) {
            return $keys[$keyId];
        }

        throw new OutOfBoundsException(\sprintf('Key with ID "%s" not found.', $keyId));
    }

    /**
     * @return array<string, string>
     */
    private function fetchKeys(): array
    {
        $cacheKey = \md5(__CLASS__).'_keys';

        $keys = $this->cache->get($cacheKey, null);

        if (\is_array($keys) && \count($keys) >= 1) {
            return $keys;
        }

        $keys = [];
        $ttls = [];
        foreach (self::KEY_URLS as $url) {
            $result = $this->fetchKeysFromUrl($url);
            $keys[] = $result['keys'];
            $ttls[] = $result['ttl'];
        }

        $keys = \array_merge(...$keys);
        $ttl = \min($ttls);

        $this->cache->set($cacheKey, $keys, $ttl);

        return $keys;
    }

    /**
     * @return array{
     *                keys: array<string, string>,
     *                ttl: int
     *                }
     */
    private function fetchKeysFromUrl(string $url): array
    {
        $response = $this->client->request(RequestMethod::METHOD_GET, $url);

        $ttl = \preg_match('/max-age=(\d+)/i', $response->getHeaderLine('Cache-Control'), $matches)
            ? (int) $matches[1]
            : 0;

        $keys = \json_decode((string) $response->getBody(), true);

        return [
            'keys' => $keys,
            'ttl' => $ttl,
        ];
    }
}
