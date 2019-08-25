<?php

namespace Firebase\Auth\Token\Domain;

use Lcobucci\JWT\Token;

/**
 * @deprecated 1.9.0
 * @see \Kreait\Firebase\JWT\CustomTokenGenerator
 */
interface Generator
{
    public function createCustomToken($uid, array $claims = []): Token;
}
