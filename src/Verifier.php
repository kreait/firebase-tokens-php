<?php

namespace Firebase\Auth\Token;

use Firebase\Auth\Token\Domain\KeyStore;
use Firebase\Auth\Token\Exception\InvalidToken;
use Firebase\Auth\Token\Exception\UnknownKey;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;

final class Verifier implements Domain\Verifier
{
    /**
     * @var string
     */
    private $projectId;

    /**
     * @var KeyStore
     */
    private $keys;

    /**
     * @var Signer
     */
    private $signer;

    public function __construct(string $projectId, KeyStore $keys = null, Signer $signer = null)
    {
        $this->projectId = $projectId;
        $this->keys = $keys ?? new HttpKeyStore();
        $this->signer = $signer ?? new Sha256();
    }

    public function verifyIdToken($token): Token
    {
        if (!($token instanceof Token)) {
            $token = (new Parser())->parse($token);
        }

        $now = time();

        if ($token->isExpired()) {
            throw new InvalidToken($token, 'This token is expired.');
        }

        if ($token->getClaim('iat') > $now) {
            throw new InvalidToken($token, 'This token has been issued in the future.');
        }

        $validIssuer = sprintf('https://securetoken.google.com/%s', $this->projectId);

        if ($token->getClaim('iss') !== $validIssuer) {
            throw new InvalidToken($token, 'This token has an invalid issuer.');
        }

        try {
            $keyId = $token->getHeader('kid');
        } catch (\OutOfBoundsException $e) {
            throw new InvalidToken($token, 'This token misses the "kid" header.');
        }

        try {
            $key = $this->keys->get($keyId);
        } catch (\OutOfBoundsException $e) {
            throw new UnknownKey('The token with ID "%s" is unknown.');
        }

        try {
            $isVerified = $token->verify($this->signer, $key);
        } catch (\Throwable $e) {
            throw new InvalidToken($token, $e->getMessage());
        }

        if (!$isVerified) {
            throw new InvalidToken($token, 'This token has an invalid signature.');
        }

        return $token;
    }
}
