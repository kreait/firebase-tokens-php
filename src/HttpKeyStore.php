<?php

namespace Firebase\Auth\Token;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Firebase\Auth\Token\Cache\InMemoryCache;
use Firebase\Auth\Token\Domain\KeyStore;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * @see https://firebase.google.com/docs/auth/admin/verify-id-tokens#verify_id_tokens_using_a_third-party_jwt_library
 */
final class HttpKeyStore implements KeyStore
{
    const KEYS_URL = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var CacheInterface
     */
    private $cache;

    public function __construct(ClientInterface $client = null, CacheInterface $cache = null)
    {
        $this->client = $client ?? new Client();
        $this->cache = $cache ?? new InMemoryCache();
    }

    public function get($keyId)
    {
        if ($key = $this->cache->get($keyId)) {
            return $key;
        }

        $response = $this->client->request(RequestMethod::METHOD_GET, self::KEYS_URL);
        $keys = json_decode((string) $response->getBody(), true);

        if (!($key = $keys[$keyId] ?? null)) {
            throw new \OutOfBoundsException(sprintf('Key with ID "%s" not found.', $keyId));
        }

        $ttl = preg_match('/max-age=(\d+)/i', $response->getHeaderLine('Cache-Control') ?? '', $matches)
            ? (int) $matches[1]
            : null;

        $this->cache->set($keyId, $key, $ttl);

        return $key;
    }
}
