<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Contract;

use DateTimeImmutable;
use DateTimeInterface;

trait ExpirableTrait
{
    /** @var DateTimeImmutable */
    private $expirationTime;

    /**
     * @return static
     */
    public function withExpirationTime(DateTimeImmutable $expirationTime)
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
