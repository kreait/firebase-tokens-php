<?php

namespace Firebase\Auth\Token\Exception;

use Exception;
use Lcobucci\JWT\Token;

class InvalidToken extends \InvalidArgumentException
{
    /**
     * @var Token
     */
    private $token;

    public function __construct(Token $token, $message = '', $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->token = $token;
    }

    public function getToken()
    {
        return $this->token;
    }
}
