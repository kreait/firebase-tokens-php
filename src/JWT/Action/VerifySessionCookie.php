<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action;

use InvalidArgumentException;

final class VerifySessionCookie
{
    /**
     * @param non-empty-string $sessionCookie
     * @param int<0, max> $leewayInSeconds
     * @param non-empty-string|null $expectedTenantId
     */
    private function __construct(
        private readonly string $sessionCookie,
        private readonly int $leewayInSeconds,
        private readonly ?string $expectedTenantId,
    ) {
    }

    /**
     * @param non-empty-string $sessionCookie
     */
    public static function withSessionCookie(string $sessionCookie): self
    {
        return new self($sessionCookie, 0, null);
    }

    /**
     * @param non-empty-string $tenantId
     */
    public function withExpectedTenantId(string $tenantId): self
    {
        return new self($this->sessionCookie, $this->leewayInSeconds, $tenantId);
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

        return new self($this->sessionCookie, $seconds, $this->expectedTenantId);
    }

    /**
     * @return non-empty-string
     */
    public function sessionCookie(): string
    {
        return $this->sessionCookie;
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
