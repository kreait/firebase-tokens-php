<?php

namespace Firebase\Auth\Token;

use Firebase\Auth\Token\Domain\KeyStore;
use Firebase\Auth\Token\Exception\ExpiredToken;
use Firebase\Auth\Token\Exception\InvalidSignature;
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

    /**
     * @see https://github.com/firebase/firebase-admin-dotnet/pull/29
     *
     * @var int
     */
    private $leewayInSeconds = 300;

    /**
     * @deprecated 1.9.0
     * @see \Kreait\Firebase\JWT\IdTokenVerifier
     */
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

        $errorBeforeSignatureCheck = null;

        try {
            $this->verifyExpiry($token);
            $this->verifyAuthTime($token);
            $this->verifyIssuedAt($token);
            $this->verifyIssuer($token);
        } catch (\Throwable $e) {
            $errorBeforeSignatureCheck = $e;
        }

        $this->verifySignature($token, $this->getKey($token));

        if ($errorBeforeSignatureCheck) {
            throw $errorBeforeSignatureCheck;
        }

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

    private function verifyAuthTime(Token $token)
    {
        if (!$token->hasClaim('auth_time')) {
            throw new InvalidToken($token, 'The claim "auth_time" is missing.');
        }

        $authTimeWithLeeway = $token->getClaim('auth_time') - $this->leewayInSeconds;

        if ($authTimeWithLeeway > time()) {
            throw new InvalidToken($token, "The user's authentication time must be in the past");
        }
    }

    private function verifyIssuedAt(Token $token)
    {
        if (!$token->hasClaim('iat')) {
            throw new InvalidToken($token, 'The claim "iat" is missing.');
        }

        $iatWithLeeway = $token->getClaim('iat') - $this->leewayInSeconds;

        if ($iatWithLeeway > time()) {
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
            throw new UnknownKey($token, $keyId);
        }
    }

    private function verifySignature(Token $token, string $key)
    {
        try {
            $isVerified = $token->verify($this->signer, $key);
        } catch (\Throwable $e) {
            throw new InvalidSignature($token, $e->getMessage());
        }

        if (!$isVerified) {
            throw new InvalidSignature($token);
        }
    }
}
