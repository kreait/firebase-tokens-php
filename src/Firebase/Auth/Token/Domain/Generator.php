<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Domain;

use Lcobucci\JWT\Token;

interface Generator
{
    /**
     * @param mixed $uid
     * @param array<string, mixed> $claims
     */
    public function createCustomToken($uid, array $claims = []): Token;
}
