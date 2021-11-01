<?php

declare(strict_types=1);

namespace Firebase\Auth\Token;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

trait ConvertsDates
{
    protected function convertExpiryDate(DateTimeInterface $date): DateTimeImmutable
    {
        if ($date instanceof DateTime) {
            $date = DateTimeImmutable::createFromMutable($date);
        }

        return $date;
    }
}
