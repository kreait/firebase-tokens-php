<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests;

use DateTimeImmutable;
use Kreait\Firebase\JWT\Contract\Expirable;
use Kreait\Firebase\JWT\Contract\ExpirableTrait;
use Kreait\Firebase\JWT\Contract\Keys;
use Kreait\Firebase\JWT\Contract\KeysTrait;
use Kreait\Firebase\JWT\Tests\Util\KeyPair;

/**
 * @internal
 */
final class TestKeys implements Keys, Expirable
{
    use KeysTrait;
    use ExpirableTrait;

    /**
     * @internal
     */
    public function __construct()
    {
        $this->values = [
            'kid' => KeyPair::publicKey(),
            'invalid' => 'invalid',
        ];
        $this->expirationTime = new DateTimeImmutable('0001-01-01'); // Very distant past
    }
}
