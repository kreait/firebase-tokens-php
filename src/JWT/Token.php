<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT;

final class Token implements Contract\Token
{
    private string $encodedString;

    /** @var array<string, mixed> */
    private array $headers;

    /** @var array<string, mixed> */
    private array $payload;

    /**
     * @param array<string, mixed> $headers
     * @param array<string, mixed> $payload
     */
    private function __construct(string $encodedString, array $headers, array $payload)
    {
        $this->encodedString = $encodedString;
        $this->headers = $headers;
        $this->payload = $payload;
    }

    /**
     * @param array<string, mixed> $headers
     * @param array<string, mixed> $payload
     */
    public static function withValues(string $encodedString, array $headers, array $payload): self
    {
        return new self($encodedString, $headers, $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @return array<string, mixed>
     */
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
