<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT;

use Kreait\Firebase\JWT\Contract\Token;
use Stringable;

final class InsecureToken implements Token, Stringable
{
    /**
     * @param non-empty-string $encodedString
     * @param array<non-empty-string, mixed> $headers
     * @param array<non-empty-string, mixed> $payload
     */
    private function __construct(
        private readonly string $encodedString,
        private readonly array $headers,
        private readonly array $payload,
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return $this->encodedString;
    }

    /**
     * @param non-empty-string $encodedString
     * @param array<non-empty-string, mixed> $headers
     * @param array<non-empty-string, mixed> $payload
     */
    public static function withValues(string $encodedString, array $headers, array $payload): self
    {
        return new self($encodedString, $headers, $payload);
    }

    /**
     * @return array<non-empty-string, mixed>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @return array<non-empty-string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->encodedString;
    }
}
