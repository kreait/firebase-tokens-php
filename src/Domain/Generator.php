<?php

namespace Firebase\Auth\Token\Domain;

use Lcobucci\JWT\Token;

interface Generator
{
    public function createCustomToken($uid, array $claims = []): Token;
}
