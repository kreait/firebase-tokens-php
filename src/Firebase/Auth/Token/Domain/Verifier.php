<?php

namespace Firebase\Auth\Token\Domain;

use Lcobucci\JWT\Token;

interface Verifier
{
    public function verifyIdToken($token): Token;
}
