<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Contract;

interface Keys
{
    /**
     * @return array<string, string>
     */
    public function all(): array;
}
