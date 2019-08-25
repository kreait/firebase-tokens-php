<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT;

use Kreait\Clock\SystemClock;
use Kreait\Firebase\JWT\Action\CreateCustomToken;
use Kreait\Firebase\JWT\Action\CreateCustomToken\Error\CustomTokenCreationFailed;
use Kreait\Firebase\JWT\Action\CreateCustomToken\Handler;
use Kreait\Firebase\JWT\Action\CreateCustomToken\WithHandlerDiscovery;
use Kreait\Firebase\JWT\Contract\Token;

final class CustomTokenGenerator
{
    /** @var Handler */
    private $handler;

    public function __construct(Handler $handler)
    {
        $this->handler = $handler;
    }

    public static function withClientEmailAndPrivateKey(string $clientEmail, string $privateKey): self
    {
        $handler = new WithHandlerDiscovery($clientEmail, $privateKey, new SystemClock());

        return new self($handler);
    }

    /**
     * @throws CustomTokenCreationFailed
     */
    public function createCustomToken(string $uid, array $claims = null, int $expirationTimeInSeconds = null): Token
    {
        $action = CreateCustomToken::forUid($uid);

        if ($claims !== null) {
            $action = $action->withCustomClaims($claims);
        }

        if ($expirationTimeInSeconds !== null) {
            $action = $action->withExpirationTimeInSeconds($expirationTimeInSeconds);
        }

        return $this->handler->handle($action);
    }
}
