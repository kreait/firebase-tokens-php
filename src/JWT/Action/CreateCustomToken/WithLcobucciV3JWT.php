<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action\CreateCustomToken;

use Kreait\Clock;
use Kreait\Firebase\JWT\Action\CreateCustomToken;
use Kreait\Firebase\JWT\Contract\Token;

/**
 * @deprecated 1.14.0 Use {@see WithLcobucciJWT} instead
 * @codeCoverageIgnore
 */
final class WithLcobucciV3JWT implements Handler
{
    private WithLcobucciJWT $handler;

    public function __construct(string $clientEmail, string $privateKey, Clock $clock)
    {
        $this->handler = new WithLcobucciJWT($clientEmail, $privateKey, $clock);
    }

    public function handle(CreateCustomToken $action): Token
    {
        return $this->handler->handle($action);
    }
}
