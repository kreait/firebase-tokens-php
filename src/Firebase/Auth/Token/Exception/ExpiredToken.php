<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Exception;

use DateTime;
use Lcobucci\JWT\Token;

class ExpiredToken extends InvalidToken
{
    public function __construct(Token $token)
    {
        if ($expiredSince = $token->claims()->get('exp')) {
            $message = "This token is expired since {$expiredSince->format(DateTime::ATOM)}.";
        } else {
            $message = 'This token is expired.';
        }

        parent::__construct($token, $message);
    }
}
