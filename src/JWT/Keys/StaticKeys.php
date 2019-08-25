<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Keys;

use Kreait\Firebase\JWT\Contract\Keys;
use Kreait\Firebase\JWT\Contract\KeysTrait;

final class StaticKeys implements Keys
{
    use KeysTrait;

    private function __construct()
    {
    }

    public static function empty(): self
    {
        return new self();
    }

    public static function withValues(array $values): self
    {
        $keys = new self();
        $keys->values = $values;

        return $keys;
    }
}
