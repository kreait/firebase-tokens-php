<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Contract;

use DateTimeImmutable;
use DateTimeInterface;

interface Expirable
{
    /**
     * @return self
     */
    public function withExpirationTime(DateTimeImmutable $time);

    public function isExpiredAt(DateTimeInterface $now): bool;

    public function expiresAt(): DateTimeImmutable;
}
