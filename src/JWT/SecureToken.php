<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT;

use Stringable;

final class SecureToken implements Contract\Token, Stringable
{
    /**
     * @param array<string, mixed> $headers
     * @param array<string, mixed> $payload
     */
    private function __construct(private readonly string $encodedString, private readonly array $headers, private readonly array $payload)
    {
    }

    public function __toString(): string
    {
        return $this->toString();
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
}
