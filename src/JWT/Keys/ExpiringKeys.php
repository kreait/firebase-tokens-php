<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Keys;

use DateTimeImmutable;
use Kreait\Firebase\JWT\Contract\Expirable;
use Kreait\Firebase\JWT\Contract\ExpirableTrait;
use Kreait\Firebase\JWT\Contract\Keys;
use Kreait\Firebase\JWT\Contract\KeysTrait;

/**
 * @internal
 */
final class ExpiringKeys implements Expirable, Keys
{
    use KeysTrait;
    use ExpirableTrait;

    private function __construct()
    {
        $this->expirationTime = new DateTimeImmutable('0001-01-01'); // Very distant past :)
    }

    /**
     * @param array<non-empty-string, non-empty-string> $values
     */
    public static function withValuesAndExpirationTime(array $values, DateTimeImmutable $expirationTime): self
    {
        $keys = new self();
        $keys->values = $values;
        $keys->expirationTime = $expirationTime;

        return $keys;
    }
}
