<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT;

use Beste\Clock\SystemClock;
use GuzzleHttp\Client;
use InvalidArgumentException;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys\WithGuzzle;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys\WithPsr6Cache;
use Kreait\Firebase\JWT\Action\VerifyIdToken;
use Kreait\Firebase\JWT\Action\VerifyIdToken\Handler;
use Kreait\Firebase\JWT\Action\VerifyIdToken\WithLcobucciJWT;
use Kreait\Firebase\JWT\Contract\Token;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use Psr\Cache\CacheItemPoolInterface;

final class IdTokenVerifier
{
    /**
     * @var non-empty-string|null
     */
    private ?string $expectedTenantId = null;

    public function __construct(private readonly Handler $handler) {}

    /**
     * @param non-empty-string $projectId
     */
    public static function createWithProjectId(string $projectId): self
    {
        $clock = SystemClock::create();
        $keyHandler = new WithGuzzle(new Client(['http_errors' => false]), $clock);

        $keys = new GooglePublicKeys($keyHandler, $clock);
        $handler = new WithLcobucciJWT($projectId, $keys, $clock);

        return new self($handler);
    }

    /**
     * @param non-empty-string $projectId
     */
    public static function createWithProjectIdAndCache(string $projectId, CacheItemPoolInterface $cache): self
    {
        $clock = SystemClock::create();

        $innerKeyHandler = new WithGuzzle(new Client(['http_errors' => false]), $clock);
        $keyHandler = new WithPsr6Cache($innerKeyHandler, $cache, $clock);

        $keys = new GooglePublicKeys($keyHandler, $clock);
        $handler = new WithLcobucciJWT($projectId, $keys, $clock);

        return new self($handler);
    }

    /**
     * @param non-empty-string $tenantId
     */
    public function withExpectedTenantId(string $tenantId): self
    {
        $verifier = clone $this;
        $verifier->expectedTenantId = $tenantId;

        return $verifier;
    }

    public function execute(VerifyIdToken $action): Token
    {
        if ($this->expectedTenantId) {
            $action = $action->withExpectedTenantId($this->expectedTenantId);
        }

        return $this->handler->handle($action);
    }

    /**
     * @param non-empty-string $token
     *
     * @throws IdTokenVerificationFailed
     */
    public function verifyIdToken(string $token): Token
    {
        return $this->execute(VerifyIdToken::withToken($token));
    }

    /**
     * @param non-empty-string $token
     * @param int<0, max> $leewayInSeconds
     *
     * @throws IdTokenVerificationFailed
     * @throws InvalidArgumentException on invalid leeway
     */
    public function verifyIdTokenWithLeeway(string $token, int $leewayInSeconds): Token
    {
        return $this->execute(VerifyIdToken::withToken($token)->withLeewayInSeconds($leewayInSeconds));
    }
}
