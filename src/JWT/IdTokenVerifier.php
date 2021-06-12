<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT;

use InvalidArgumentException;
use Kreait\Clock\SystemClock;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys\WithHandlerDiscovery;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys\WithPsr16SimpleCache;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys\WithPsr6Cache;
use Kreait\Firebase\JWT\Action\VerifyIdToken;
use Kreait\Firebase\JWT\Action\VerifyIdToken\Handler;
use Kreait\Firebase\JWT\Cache\InMemoryCache;
use Kreait\Firebase\JWT\Contract\Token;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;

final class IdTokenVerifier
{
    private Handler $handler;

    private ?string $expectedTenantId = null;

    public function __construct(Handler $handler)
    {
        $this->handler = $handler;
    }

    public static function createWithProjectId(string $projectId): self
    {
        return self::createWithProjectIdAndCache($projectId, InMemoryCache::createEmpty());
    }

    /**
     * @param CacheInterface|CacheItemPoolInterface $cache
     */
    public static function createWithProjectIdAndCache(string $projectId, $cache): self
    {
        $clock = new SystemClock();
        $keyHandler = new WithHandlerDiscovery($clock);

        $keyHandler = $cache instanceof CacheInterface
            ? new WithPsr16SimpleCache($keyHandler, $cache, $clock)
            : new WithPsr6Cache($keyHandler, $cache, $clock);

        $keys = new GooglePublicKeys($keyHandler, $clock);
        $handler = new VerifyIdToken\WithHandlerDiscovery($projectId, $keys, $clock);

        return new self($handler);
    }

    public function withExpectedTenantId(string $tenantId): self
    {
        $generator = clone $this;
        $generator->expectedTenantId = $tenantId;

        return $generator;
    }

    public function execute(VerifyIdToken $action): Token
    {
        if ($this->expectedTenantId) {
            $action = $action->withExpectedTenantId($this->expectedTenantId);
        }

        return $this->handler->handle($action);
    }

    /**
     * @throws IdTokenVerificationFailed
     */
    public function verifyIdToken(string $token): Token
    {
        return $this->execute(VerifyIdToken::withToken($token));
    }

    /**
     * @throws InvalidArgumentException on invalid leeway
     * @throws IdTokenVerificationFailed
     */
    public function verifyIdTokenWithLeeway(string $token, int $leewayInSeconds): Token
    {
        return $this->execute(VerifyIdToken::withToken($token)->withLeewayInSeconds($leewayInSeconds));
    }
}
