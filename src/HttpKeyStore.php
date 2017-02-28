<?php

namespace Firebase\Auth\Token;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Firebase\Auth\Token\Domain\KeyStore;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

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

    public function __construct(ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
    }

    public function get($keyId)
    {
        $response = $this->client->request(RequestMethod::METHOD_GET, self::KEYS_URL);
        $keys = new ArrayKeyStore(json_decode((string) $response->getBody(), true));

        return $keys->get($keyId);
    }
}
