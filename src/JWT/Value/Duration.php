<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Value;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use Throwable;

/**
 * Adapted duration class from gamez/duration.
 *
 * @see https://github.com/jeromegamez/duration-php
 */
final class Duration
{
    public const NONE = 'PT0S';

    private DateInterval $value;

    private function __construct(DateInterval $value)
    {
        $this->value = $value;
    }

    /**
     * @param self|DateInterval|int|string $value
     */
    public static function make($value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if ($value instanceof DateInterval) {
            return self::fromDateInterval($value);
        }

        if (\is_int($value)) {
            return self::inSeconds($value);
        }

        if (\mb_strpos($value, 'P') === 0) {
            return self::fromDateIntervalSpec($value);
        }

        try {
            $interval = DateInterval::createFromDateString($value);
        } catch (Throwable $e) {
            throw new InvalidArgumentException("Unable to determine a duration from '{$value}'");
        }

        $duration = self::fromDateInterval($interval);

        // If the string doesn't contain a zero, but the result equals to zero
        // the value must be invalid.
        if (\mb_strpos($value, '0') === false && $duration->equals(self::none())) {
            throw new InvalidArgumentException("Unable to determine a duration from '{$value}'");
        }

        return $duration;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function inSeconds(int $seconds): self
    {
        if ($seconds < 0) {
            throw new InvalidArgumentException('A duration can not be negative');
        }

        return self::fromDateIntervalSpec('PT'.$seconds.'S');
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromDateIntervalSpec(string $spec): self
    {
        try {
            $interval = new DateInterval($spec);
        } catch (Throwable $e) {
            throw new InvalidArgumentException("'{$spec}' is not a valid DateInterval specification");
        }

        return self::fromDateInterval($interval);
    }

    public static function fromDateInterval(DateInterval $interval): self
    {
        $now = new DateTimeImmutable();
        $then = $now->add($interval);

        if ($then < $now) {
            throw new InvalidArgumentException('A duration can not be negative');
        }

        return new self($interval);
    }

    public static function none(): self
    {
        return self::fromDateIntervalSpec(self::NONE);
    }

    public function value(): DateInterval
    {
        return $this->value;
    }

    /**
     * @param self|DateInterval|int|string $other
     */
    public function isLargerThan($other): bool
    {
        return 1 === $this->compareTo($other);
    }

    /**
     * @param self|DateInterval|int|string $other
     */
    public function equals($other): bool
    {
        return 0 === $this->compareTo($other);
    }

    /**
     * @param self|DateInterval|int|string $other
     */
    public function isSmallerThan($other): bool
    {
        return -1 === $this->compareTo($other);
    }

    /**
     * @param self|DateInterval|int|string $other
     */
    public function compareTo($other): int
    {
        $other = self::make($other);

        $now = self::now();

        return $now->add($this->value) <=> $now->add($other->value);
    }

    public function toString(): string
    {
        return self::toDateIntervalSpec(self::normalizeInterval($this->value));
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    private static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('@'.\time());
    }

    private static function normalizeInterval(DateInterval $value): DateInterval
    {
        $now = self::now();
        $then = $now->add($value);

        return $now->diff($then);
    }

    private static function toDateIntervalSpec(DateInterval $value): string
    {
        $spec = 'P';
        $spec .= 0 !== $value->y ? $value->y.'Y' : '';
        $spec .= 0 !== $value->m ? $value->m.'M' : '';
        $spec .= 0 !== $value->d ? $value->d.'D' : '';

        $spec .= 'T';
        $spec .= 0 !== $value->h ? $value->h.'H' : '';
        $spec .= 0 !== $value->i ? $value->i.'M' : '';
        $spec .= 0 !== $value->s ? $value->s.'S' : '';

        if ('T' === \mb_substr($spec, -1)) {
            $spec = \mb_substr($spec, 0, -1);
        }

        if ('P' === $spec) {
            return self::NONE;
        }

        return $spec;
    }
}
