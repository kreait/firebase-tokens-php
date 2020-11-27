<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action;

use InvalidArgumentException;

final class VerifyIdToken
{
    /** @var string */
    private $token = '';

    /** @var int */
    private $leewayInSeconds = 0;

    /** @var string|null */
    private $expectedTenantId;

    private function __construct()
    {
    }

    public static function withToken(string $token): self
    {
        $action = new self();
        $action->token = $token;

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

    public function token(): string
    {
        return $this->token;
    }

    /**
     * @return string|null
     */
    public function expectedTenantId()
    {
        return $this->expectedTenantId;
    }

    public function leewayInSeconds(): int
    {
        return $this->leewayInSeconds;
    }
}
