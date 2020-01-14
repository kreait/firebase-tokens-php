<?php

namespace Firebase\Auth\Token\Domain;

use Firebase\Auth\Token\Exception\ExpiredToken;
use Firebase\Auth\Token\Exception\InvalidSignature;
use Firebase\Auth\Token\Exception\InvalidToken;
use Firebase\Auth\Token\Exception\IssuedInTheFuture;
use Firebase\Auth\Token\Exception\UnknownKey;
use InvalidArgumentException;
use Lcobucci\JWT\Token;

/**
 * @deprecated 1.9.0
 * @see \Kreait\Firebase\JWT\IdTokenVerifier
 */
interface Verifier
{
    /**
     * @param Token|string $token
     *
     * @throws InvalidArgumentException if the token could not be parsed
     * @throws InvalidToken if the token could be parsed, but is invalid for any one of the following reasons
     * @throws InvalidSignature if the signature doesn't match
     * @throws ExpiredToken if the token is expired
     * @throws IssuedInTheFuture if the token is issued in the future
     * @throws UnknownKey if the token's kid header doesnt' contain a known key
     */
    public function verifyIdToken($token): Token;
}
