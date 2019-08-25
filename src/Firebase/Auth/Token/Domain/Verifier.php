<?php

namespace Firebase\Auth\Token\Domain;

use Lcobucci\JWT\Token;

/**
 * @deprecated 1.9.0
 * @see \Kreait\Firebase\JWT\IdTokenVerifier
 */
interface Verifier
{
    public function verifyIdToken($token): Token;
}
