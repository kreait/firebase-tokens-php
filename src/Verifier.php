<?php

namespace Firebase\Auth\Token;

use Firebase\Auth\Token\Domain\KeyStore;
use Firebase\Auth\Token\Exception\ExpiredToken;
use Firebase\Auth\Token\Exception\InvalidToken;
use Firebase\Auth\Token\Exception\IssuedInTheFuture;
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

        $this->verifyExpiry($token);
        $this->verifyIssuedAt($token);
        $this->verifyIssuer($token);
        $this->verifySignature($token, $this->getKey($token));

        return $token;
    }

    private function verifyExpiry(Token $token)
    {
        if (!$token->hasClaim('exp')) {
            throw new InvalidToken($token, 'The claim "exp" is missing.');
        }

        if ($token->isExpired()) {
            throw new ExpiredToken($token);
        }
    }

    private function verifyIssuedAt(Token $token)
    {
        if (!$token->hasClaim('iat')) {
            throw new InvalidToken($token, 'The claim "iat" is missing.');
        }

        if ($token->getClaim('iat') > time()) {
            throw new IssuedInTheFuture($token);
        }
    }

    private function verifyIssuer(Token $token)
    {
        if (!$token->hasClaim('iss')) {
            throw new InvalidToken($token, 'The claim "iss" is missing.');
        }

        if ($token->getClaim('iss') !== sprintf('https://securetoken.google.com/%s', $this->projectId)) {
            throw new InvalidToken($token, 'This token has an invalid issuer.');
        }
    }

    private function getKey(Token $token): string
    {
        if (!$token->hasHeader('kid')) {
            throw new InvalidToken($token, 'The header "kid" is missing.');
        }

        $keyId = $token->getHeader('kid');

        try {
            return $this->keys->get($keyId);
        } catch (\OutOfBoundsException $e) {
            throw new UnknownKey($keyId);
        }
    }

    private function verifySignature(Token $token, string $key)
    {
        try {
            $isVerified = $token->verify($this->signer, $key);
        } catch (\Throwable $e) {
            throw new InvalidToken($token, $e->getMessage());
        }

        if (!$isVerified) {
            throw new InvalidToken($token, 'This token has an invalid signature.');
        }
    }
}
