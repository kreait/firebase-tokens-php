<?php

namespace Firebase\Auth\Token\Exception;

use Lcobucci\JWT\Token;

class ExpiredToken extends InvalidToken
{
    public function __construct(Token $token)
    {
        $expiredSince = \DateTimeImmutable::createFromFormat('U', $token->getClaim('exp'));

        $message = sprintf('This token is expired since %s', $expiredSince->format(\DateTime::ATOM));

        parent::__construct($token, $message);
    }
}
