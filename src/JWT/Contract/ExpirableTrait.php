<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Contract;

use DateTimeImmutable;
use DateTimeInterface;

trait ExpirableTrait
{
    private DateTimeImmutable $expirationTime;

    public function withExpirationTime(DateTimeImmutable $expirationTime): self
    {
        $expirable = clone $this;
        $expirable->expirationTime = $expirationTime;

        return $expirable;
    }

    public function isExpiredAt(DateTimeInterface $now): bool
    {
        return $this->expirationTime < $now;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expirationTime;
    }
}
