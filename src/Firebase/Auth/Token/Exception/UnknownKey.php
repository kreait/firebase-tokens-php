<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Exception;

use Lcobucci\JWT\Token;

class UnknownKey extends InvalidToken
{
    private string $keyId;

    public function __construct(Token $token, string $keyId)
    {
        parent::__construct($token, \sprintf('A key with ID "%s" could not be found.', $keyId));

        $this->keyId = $keyId;
    }

    public function getKeyId(): string
    {
        return $this->keyId;
    }
}
