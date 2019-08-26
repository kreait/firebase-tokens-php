<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Error;

use RuntimeException;

final class IdTokenVerificationFailed extends RuntimeException
{
    public static function withTokenAndReasons(string $token, array $reasons): self
    {
        if (strlen($token) > 18) {
            $token = substr($token, 0, 15).'...';
        }

        $summary = implode(PHP_EOL.'- ', $reasons);

        $message = "The value '{$token}' is not a verified ID token:".PHP_EOL.'- '.$summary.PHP_EOL;

        return new self($message);
    }
}
