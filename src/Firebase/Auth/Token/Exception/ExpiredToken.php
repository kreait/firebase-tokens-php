<?php

namespace Firebase\Auth\Token\Exception;

use Lcobucci\JWT\Token;

class ExpiredToken extends InvalidToken
{
    public function __construct(Token $token)
    {
        if ($expiredSince = \DateTimeImmutable::createFromFormat('U', $token->getClaim('exp'))) {
            $message = "This token is expired since{$expiredSince->format(\DateTime::ATOM)}.";
        } else {
            $message = 'This token is expired.';
        }

        parent::__construct($token, $message);
    }
}
