<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Exception;

use InvalidArgumentException;
use Lcobucci\JWT\Token;
use Throwable;

class InvalidToken extends InvalidArgumentException
{
    /** @var Token */
    private $token;

    /**
     * @param string $message
     * @param int $code
     */
    public function __construct(Token $token, $message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->token = $token;
    }

    public function getToken(): Token
    {
        return $this->token;
    }
}
