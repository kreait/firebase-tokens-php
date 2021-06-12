<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action\VerifyIdToken;

use Kreait\Clock;
use Kreait\Firebase\JWT\Action\VerifyIdToken;
use Kreait\Firebase\JWT\Contract\Keys;
use Kreait\Firebase\JWT\Contract\Token;

/**
 * @deprecated 1.14.0 Use {@see WithLcobucciJWT} instead
 * @codeCoverageIgnore
 */
final class WithLcobucciV3JWT implements Handler
{
    private WithLcobucciJWT $handler;

    public function __construct(string $projectId, Keys $keys, Clock $clock)
    {
        $this->handler = new WithLcobucciJWT($projectId, $keys, $clock);
    }

    public function handle(VerifyIdToken $action): Token
    {
        return $this->handler->handle($action);
    }
}
