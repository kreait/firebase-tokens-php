<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Exception;

use DateTime;
use Lcobucci\JWT\Token;

class IssuedInTheFuture extends InvalidToken
{
    public function __construct(Token $token)
    {
        if ($token instanceof Token\Plain && $iat = $token->claims()->get('iat')) {
            $message = "This token has been issued in the future at {$iat->format(DateTime::ATOM)}, is your system time correct?";
        } else {
            $message = 'This token has been issued in the future, is your system time correct?';
        }

        parent::__construct($token, $message);
    }
}
