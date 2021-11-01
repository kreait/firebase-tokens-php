<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Contract;

trait KeysTrait
{
    /** @var array<string, string> */
    private array $values = [];

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->values;
    }
}
