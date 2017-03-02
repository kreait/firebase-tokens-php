<?php

namespace Firebase\Auth\Token\Exception;

use Lcobucci\JWT\Token;

class IssuedInTheFuture extends InvalidToken
{
    public function __construct(Token $token)
    {
        $iat = \DateTimeImmutable::createFromFormat('U', $token->getClaim('iat'));

        $message = sprintf(
            'This token has been issued in the future at %s, is your system time correct?',
            $iat->format(\DateTime::ATOM)
        );

        parent::__construct($token, $message);
    }
}
