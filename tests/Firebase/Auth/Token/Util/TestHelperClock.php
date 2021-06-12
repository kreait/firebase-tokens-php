<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Tests\Util;

use DateInterval;
use DateTimeImmutable;
use Kreait\Clock;

final class TestHelperClock implements Clock
{
    private Clock $clock;

    public function __construct(Clock $clock)
    {
        $this->clock = $clock;
    }

    public function now(): DateTimeImmutable
    {
        return $this->clock->now();
    }

    public function minutesEarlier(int $minutes): DateTimeImmutable
    {
        return $this->now()->sub(new DateInterval('PT'.$minutes.'M'));
    }

    public function minutesLater(int $minutes): DateTimeImmutable
    {
        return $this->now()->add(new DateInterval('PT'.$minutes.'M'));
    }

    public function secondsEarlier(int $seconds): DateTimeImmutable
    {
        return $this->now()->sub(new DateInterval('PT'.$seconds.'S'));
    }

    public function secondsLater(int $seconds): DateTimeImmutable
    {
        return $this->now()->add(new DateInterval('PT'.$seconds.'S'));
    }
}
