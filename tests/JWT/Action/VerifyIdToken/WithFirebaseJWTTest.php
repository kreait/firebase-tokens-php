<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action\VerifyIdToken;

use Kreait\Firebase\JWT\Action\VerifyIdToken\Handler;
use Kreait\Firebase\JWT\Action\VerifyIdToken\WithFirebaseJWT;

/**
 * @internal
 */
final class WithFirebaseJWTTest extends TestCase
{
    protected function createHandler(): Handler
    {
        return new WithFirebaseJWT($this->projectId, $this->keys, $this->clock);
    }
}
