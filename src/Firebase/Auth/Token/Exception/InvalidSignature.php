<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Exception;

use Lcobucci\JWT\Token;

class InvalidSignature extends InvalidToken
{
    public function __construct(Token $token, string $additionalMessage = null)
    {
        $message = 'The token has an invalid signature';
        $message .= $additionalMessage ? ': '.$additionalMessage : '';

        parent::__construct($token, $message);
    }
}
