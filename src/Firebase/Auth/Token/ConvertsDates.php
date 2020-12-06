<?php

declare(strict_types=1);

namespace Firebase\Auth\Token;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;

trait ConvertsDates
{
    protected function convertExpiryDate(DateTimeInterface $date): DateTimeImmutable
    {
        if ($date instanceof DateTimeImmutable) {
            return $date;
        }

        if ($date instanceof \DateTime) {
            return DateTimeImmutable::createFromMutable($date);
        }

        if ($result = DateTimeImmutable::createFromFormat('U.u', $date->format('U.u'))) {
            return $result;
        }

        return (new DateTimeImmutable())->add(new DateInterval('PT1H'));
    }
}
