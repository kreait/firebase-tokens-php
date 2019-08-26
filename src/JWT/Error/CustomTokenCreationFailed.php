<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Error;

use RuntimeException;
use Throwable;

final class CustomTokenCreationFailed extends RuntimeException
{
    public static function because(string $reason, int $code = null, Throwable $previous = null): self
    {
        $code = $code ?: 0;

        return new self($reason, $code, $previous);
    }
}
