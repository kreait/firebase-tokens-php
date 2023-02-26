<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action;

use InvalidArgumentException;

final class VerifyIdToken
{
    /**
     * @param non-empty-string $token
     * @param int<0, max> $leewayInSeconds
     * @param non-empty-string|null $expectedTenantId
     */
    private function __construct(
        private string $token,
        private int $leewayInSeconds,
        private ?string $expectedTenantId,
    ) {
    }

    /**
     * @param non-empty-string $token
     */
    public static function withToken(string $token): self
    {
        return new self($token, 0, null);
    }

    /**
     * @param non-empty-string $tenantId
     */
    public function withExpectedTenantId(string $tenantId): self
    {
        return new self($this->token, $this->leewayInSeconds, $tenantId);
    }

    /**
     * @param int<0, max> $seconds
     */
    public function withLeewayInSeconds(int $seconds): self
    {
        // @phpstan-ignore-next-line
        if ($seconds < 0) {
            throw new InvalidArgumentException('Leeway must not be negative');
        }

        return new self($this->token, $seconds, $this->expectedTenantId);
    }

    /**
     * @return non-empty-string
     */
    public function token(): string
    {
        return $this->token;
    }

    /**
     * @return non-empty-string|null
     */
    public function expectedTenantId(): ?string
    {
        return $this->expectedTenantId;
    }

    /**
     * @return int<0, max>
     */
    public function leewayInSeconds(): int
    {
        return $this->leewayInSeconds;
    }
}
