<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action\VerifyIdToken;

use Kreait\Firebase\JWT\Action\VerifyIdToken\Handler;
use Kreait\Firebase\JWT\Action\VerifyIdToken\WithLcobucciJWT;

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
