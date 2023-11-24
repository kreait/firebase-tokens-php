<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Keys;

use Kreait\Firebase\JWT\Contract\Keys;
use Kreait\Firebase\JWT\Contract\KeysTrait;

/**
 * @internal
 */
final class StaticKeys implements Keys
{
    use KeysTrait;

    private function __construct() {}

    public static function empty(): self
    {
        return new self();
    }

    /**
     * @param array<non-empty-string, non-empty-string> $values
     */
    public static function withValues(array $values): self
    {
        $keys = new self();
        $keys->values = $values;

        return $keys;
    }
}
