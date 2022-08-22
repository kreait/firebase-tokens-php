<?php

declare(strict_types=1);

namespace Firebase\Auth\Token;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

trait ConvertsDates
{
    /**
     * @param DateTime|DateTimeImmutable $date
     */
    protected function convertExpiryDate(DateTimeInterface $date): DateTimeImmutable
    {
        if ($date instanceof DateTimeImmutable) {
            return $date;
        }

        if ($date instanceof DateTime) {
            return DateTimeImmutable::createFromMutable($date);
        }
    }
}
