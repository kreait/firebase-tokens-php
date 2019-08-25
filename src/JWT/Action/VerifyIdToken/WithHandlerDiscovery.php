<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action\VerifyIdToken;

use Firebase\JWT\JWT;
use Kreait\Clock;
use Kreait\Firebase\JWT\Action\VerifyIdToken;
use Kreait\Firebase\JWT\Contract\Keys;
use Kreait\Firebase\JWT\Contract\Token;
use Kreait\Firebase\JWT\Error\DiscoveryFailed;
use Lcobucci\JWT\Builder;

final class WithHandlerDiscovery implements Handler
{
    /** @var Handler */
    private $handler;

    public function __construct(string $projectId, Keys $keys, Clock $clock)
    {
        $this->handler = self::discoverHandler($projectId, $keys, $clock);
    }

    public function handle(VerifyIdToken $action): Token
    {
        return $this->handler->handle($action);
    }

    private static function discoverHandler(string $projectId, Keys $keys, Clock $clock): Handler
    {
        if (class_exists(JWT::class)) {
            return new VerifyIdToken\WithFirebaseJWT($projectId, $keys, $clock);
        }

        if (class_exists(Builder::class)) {
            return new VerifyIdToken\WithLcobucciV3JWT($projectId, $keys, $clock);
        }

        throw DiscoveryFailed::noJWTLibraryFound();
    }
}
