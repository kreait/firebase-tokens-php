<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Contract;

interface Token
{
    public function headers(): array;

    public function payload(): array;

    public function toString(): string;

    public function __toString(): string;
}
