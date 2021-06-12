<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Exception;

use DateTime;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Plain;

class ExpiredToken extends InvalidToken
{
    public function __construct(Token $token)
    {
        if ($token instanceof Plain && $expiredSince = $token->claims()->get('exp')) {
            $message = "This token is expired since {$expiredSince->format(DateTime::ATOM)}.";
        } else {
            $message = 'This token is expired.';
        }

        parent::__construct($token, $message);
    }
}
