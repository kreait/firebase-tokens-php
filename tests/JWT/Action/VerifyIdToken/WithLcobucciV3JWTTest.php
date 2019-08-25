<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action\VerifyIdToken;

use Kreait\Firebase\JWT\Action\VerifyIdToken\Handler;
use Kreait\Firebase\JWT\Action\VerifyIdToken\WithLcobucciV3JWT;

/**
 * @internal
 */
final class WithLcobucciV3JWTTest extends TestCase
{
    protected function createHandler(): Handler
    {
        return new WithLcobucciV3JWT($this->projectId, $this->keys, $this->clock);
    }
}
