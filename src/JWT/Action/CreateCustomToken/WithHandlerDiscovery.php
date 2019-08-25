<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action\CreateCustomToken;

use Firebase\JWT\JWT;
use Kreait\Clock;
use Kreait\Firebase\JWT\Action\CreateCustomToken;
use Kreait\Firebase\JWT\Contract\Token;
use Kreait\Firebase\JWT\Error\DiscoveryFailed;
use Lcobucci\JWT\Builder;

final class WithHandlerDiscovery implements Handler
{
    /** @var Handler */
    private $handler;

    public function __construct(string $clientEmail, string $privateKey, Clock $clock)
    {
        $this->handler = self::discoverHandler($clientEmail, $privateKey, $clock);
    }

    public function handle(CreateCustomToken $action): Token
    {
        return $this->handler->handle($action);
    }

    private static function discoverHandler(string $clientEmail, string $privateKey, Clock $clock): Handler
    {
        if (class_exists(JWT::class)) {
            return new CreateCustomToken\WithFirebaseJWT($clientEmail, $privateKey, $clock);
        }

        if (class_exists(Builder::class)) {
            return new CreateCustomToken\WithLcobucciV3JWT($clientEmail, $privateKey, $clock);
        }

        throw DiscoveryFailed::noJWTLibraryFound();
    }
}
