<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Util;

use Lcobucci\JWT\Signer\Key;

final class EmptyKey implements Key
{
    public function contents(): string
    {
        // @phpstan-ignore-next-line
        return '';
    }

    public function passphrase(): string
    {
        return '';
    }
}
