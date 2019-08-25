<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Contract;

trait KeysTrait
{
    /** @var array */
    private $values = [];

    public function all(): array
    {
        return $this->values;
    }
}
