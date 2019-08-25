<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action\CreateCustomToken;

use Kreait\Firebase\JWT\Action\CreateCustomToken\Handler;
use Kreait\Firebase\JWT\Action\CreateCustomToken\WithFirebaseJWT;
use Kreait\Firebase\JWT\Tests\Util\KeyPair;

/**
 * @internal
 */
final class WithFirebaseJWTTest extends TestCase
{
    protected static function createHandler(): Handler
    {
        return new WithFirebaseJWT('client@email.tld', KeyPair::privateKey(), self::$clock);
    }

    protected static function createHandlerWithInvalidPrivateKey(): Handler
    {
        return new WithFirebaseJWT('client@email.tld', 'invalid', self::$clock);
    }
}
