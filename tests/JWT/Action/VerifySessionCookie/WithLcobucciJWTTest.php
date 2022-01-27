<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action\VerifySessionCookie;

use Kreait\Firebase\JWT\Action\VerifySessionCookie\Handler;
use Kreait\Firebase\JWT\Action\VerifySessionCookie\WithLcobucciJWT;

/**
 * @internal
 */
final class WithLcobucciJWTTest extends TestCase
{
    protected function createHandler(): Handler
    {
        return new WithLcobucciJWT($this->projectId, $this->keys, $this->clock);
    }
}
