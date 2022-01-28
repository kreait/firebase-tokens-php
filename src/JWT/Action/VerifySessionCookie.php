<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action;

use InvalidArgumentException;

final class VerifySessionCookie
{
    private string $sessionCookie = '';

    private int $leewayInSeconds = 0;

    private ?string $expectedTenantId = null;

    private function __construct()
    {
    }

    public static function withSessionCookie(string $sessionCookie): self
    {
        $action = new self();
        $action->sessionCookie = $sessionCookie;

        return $action;
    }

    public function withExpectedTenantId(string $tenantId): self
    {
        $action = clone $this;
        $action->expectedTenantId = $tenantId;

        return $action;
    }

    public function withLeewayInSeconds(int $seconds): self
    {
        if ($seconds < 0) {
            throw new InvalidArgumentException('Leeway must not be negative');
        }

        $action = clone $this;
        $action->leewayInSeconds = $seconds;

        return $action;
    }

    public function sessionCookie(): string
    {
        return $this->sessionCookie;
    }

    public function expectedTenantId(): ?string
    {
        return $this->expectedTenantId;
    }

    public function leewayInSeconds(): int
    {
        return $this->leewayInSeconds;
    }
}
