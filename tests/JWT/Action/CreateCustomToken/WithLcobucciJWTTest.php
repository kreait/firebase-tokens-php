<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action\CreateCustomToken;

use Kreait\Firebase\JWT\Action\CreateCustomToken\Handler;
use Kreait\Firebase\JWT\Action\CreateCustomToken\WithLcobucciJWT;
use Kreait\Firebase\JWT\Tests\Util\KeyPair;

/**
 * @internal
 */
final class WithLcobucciJWTTest extends TestCase
{
    protected static function createHandler(): Handler
    {
        return new WithLcobucciJWT('client@email.tld', KeyPair::privateKey(), self::$clock);
    }

    protected static function createHandlerWithInvalidPrivateKey(): Handler
    {
        return new WithLcobucciJWT('client@email.tld', 'invalid_private_key', self::$clock);
    }
}
