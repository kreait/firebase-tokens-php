<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Util;

use Firebase\JWT\JWT;
use Kreait\Clock;
use Kreait\Clock\SystemClock;

/**
 * @internal
 */
final class IdToken
{
    private Clock $clock;

    /** @var array<string, string> */
    private array $headers = ['typ' => 'JWT', 'alg' => 'RS256', 'kid' => 'kid'];

    /** @var array<string, mixed> */
    private array $payload;

    /** @var string[] */
    private array $claimsToDelete = [];

    /** @var string[] */
    private array $headersToDelete = [];

    private ?string $privateKey;

    public function __construct(Clock $clock = null)
    {
        $this->clock = $clock ?: new SystemClock();
        $this->payload = $this->defaultPayload();
        $this->privateKey = KeyPair::privateKey();
    }

    /**
     * @param mixed $value
     */
    public function withClaim(string $name, $value): self
    {
        $builder = clone $this;
        $builder->payload[$name] = $value;

        return $builder;
    }

    public function withoutClaim(string $name): self
    {
        $builder = clone $this;
        $builder->claimsToDelete[] = $name;

        return $builder;
    }

    public function withChangedHeader(string $name, string $value): self
    {
        $builder = clone $this;
        $builder->headers[$name] = $value;

        return $builder;
    }

    public function withoutHeader(string $name): self
    {
        $builder = clone $this;
        $builder->headersToDelete[] = $name;

        return $builder;
    }

    public function withoutSignature(): self
    {
        $builder = clone $this;
        $builder->privateKey = null;

        return $builder;
    }

    public function build(): string
    {
        $now = $this->clock->now();

        $headers = $this->headers;

        foreach ($this->headersToDelete as $header) {
            unset($headers[$header]);
        }

        $payload = $this->payload;
        $payload['iat'] = $payload['iat'] ?? $now->getTimestamp();
        $payload['auth_time'] = $payload['auth_time'] ?? ($now->getTimestamp() - 1);
        $payload['exp'] = $payload['exp'] ?? ($now->getTimestamp() + 3600); // 1 hour
        $payload['nbf'] = $payload['nbf'] ?? ($now->getTimestamp() - 10);

        foreach ($this->claimsToDelete as $claim) {
            unset($payload[$claim]);
        }

        return $this->encode($payload, $headers);
    }

    /**
     * @return array{iss: string, sub: string, aud: string}
     */
    private function defaultPayload(): array
    {
        return [
            'iss' => 'https://securetoken.google.com/project-id',
            'sub' => 'uid',
            'aud' => 'project-id',
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     */
    private function encode(array $payload, array $headers): string
    {
        $segments = [];
        $segments[] = JWT::urlsafeB64Encode(JWT::jsonEncode($headers));
        $segments[] = JWT::urlsafeB64Encode(JWT::jsonEncode($payload));
        $signingInput = \implode('.', $segments);

        if ($this->privateKey) {
            $signature = JWT::sign($signingInput, $this->privateKey, $headers['alg']);
            $segments[] = JWT::urlsafeB64Encode($signature);
        }

        return \implode('.', $segments);
    }
}
