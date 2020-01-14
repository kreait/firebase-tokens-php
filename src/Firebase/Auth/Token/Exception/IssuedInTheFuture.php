<?php

namespace Firebase\Auth\Token\Exception;

use Lcobucci\JWT\Token;

class IssuedInTheFuture extends InvalidToken
{
    public function __construct(Token $token)
    {
        if ($iat = \DateTimeImmutable::createFromFormat('U', $token->getClaim('iat'))) {
            $message = "This token has been issued in the future at {$iat->format(\DateTime::ATOM)}, is your system time correct?";
        } else {
            $message = 'This token has been issued in the future, is your system time correct?';
        }

        parent::__construct($token, $message);
    }
}
