<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action\CreateCustomToken;

use Kreait\Firebase\JWT\Action\CreateCustomToken\Handler;
use Kreait\Firebase\JWT\Action\CreateCustomToken\WithLcobucciV3JWT;
use Kreait\Firebase\JWT\Tests\Util\KeyPair;

/**
 * @internal
 */
final class WithLcobucciV3JWTTest extends TestCase
{
    protected static function createHandler(): Handler
    {
        return new WithLcobucciV3JWT('client@email.tld', KeyPair::privateKey(), self::$clock);
    }

    protected static function createHandlerWithInvalidPrivateKey(): Handler
    {
        return new WithLcobucciV3JWT('client@email.tld', 'invalid_private_key', self::$clock);
    }
}
