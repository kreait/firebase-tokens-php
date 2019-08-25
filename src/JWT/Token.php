<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT;

final class Token implements Contract\Token
{
    /** @var string */
    private $encodedString;

    /** @var array */
    private $headers;

    /** @var array */
    private $payload;

    private function __construct()
    {
    }

    public static function withValues(string $encodedString, array $headers, array $payload): self
    {
        $token = new self();

        $token->encodedString = $encodedString;
        $token->headers = $headers;
        $token->payload = $payload;

        return $token;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function toString(): string
    {
        return $this->encodedString;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
