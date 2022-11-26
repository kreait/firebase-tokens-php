<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT;

use function in_array;

/**
 * @internal
 */
final class Util
{
    public static function getenv(string $name): ?string
    {
        $value = $_SERVER[$name] ?? $_ENV[$name] ?? getenv($name);

        if ($value === false) {
            return null;
        }

        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    public static function authEmulatorHost(): string
    {
        $emulatorHost = self::getenv('FIREBASE_AUTH_EMULATOR_HOST');

        if (!in_array($emulatorHost, [null, ''], true)) {
            return $emulatorHost;
        }

        return '';
    }
}
