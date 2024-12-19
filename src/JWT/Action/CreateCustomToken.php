<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action;

use DateInterval;
use InvalidArgumentException;
use Kreait\Firebase\JWT\Value\Duration;

final class CreateCustomToken
{
    public const MINIMUM_TTL = 'PT1S';

    public const MAXIMUM_TTL = 'PT1H';

    public const DEFAULT_TTL = self::MAXIMUM_TTL;

    private ?string $tenantId = null;

    /** @var array<string, mixed> */
    private array $customClaims = [];

    private Duration $ttl;

    private function __construct(private string $uid)
    {
        $this->ttl = Duration::fromDateIntervalSpec(self::DEFAULT_TTL);
    }

    public static function forUid(string $uid): self
    {
        return new self($uid);
    }

    public function withTenantId(string $tenantId): self
    {
        $action = clone $this;
        $action->tenantId = $tenantId;

        return $action;
    }

    public function withChangedUid(string $uid): self
    {
        $action = clone $this;
        $action->uid = $uid;

        return $action;
    }

    public function withCustomClaim(string $name, mixed $value): self
    {
        $action = clone $this;
        $action->customClaims[$name] = $value;

        return $action;
    }

    /**
     * @param array<string, mixed> $claims
     */
    public function withCustomClaims(array $claims): self
    {
        $action = clone $this;
        $action->customClaims = $claims;

        return $action;
    }

    /**
     * @param array<string, mixed> $claims
     */
    public function withAddedCustomClaims(array $claims): self
    {
        $action = clone $this;
        $action->customClaims = [...$action->customClaims, ...$claims];

        return $action;
    }

    public function withTimeToLive(Duration|DateInterval|string|int $ttl): self
    {
        $ttl = Duration::make($ttl);

        $minTtl = Duration::fromDateIntervalSpec(self::MINIMUM_TTL);
        $maxTtl = Duration::fromDateIntervalSpec(self::MAXIMUM_TTL);

        if ($ttl->isSmallerThan($minTtl) || $ttl->isLargerThan($maxTtl)) {
            $message = 'The expiration time of a custom token must be between %s and %s, but got %s';

            throw new InvalidArgumentException(sprintf($message, $minTtl, $maxTtl, $ttl));
        }

        $action = clone $this;
        $action->ttl = $ttl;

        return $action;
    }

    public function uid(): string
    {
        return $this->uid;
    }

    public function tenantId(): ?string
    {
        return $this->tenantId;
    }

    /**
     * @return array<string, mixed>
     */
    public function customClaims(): array
    {
        return $this->customClaims;
    }

    public function timeToLive(): Duration
    {
        return $this->ttl;
    }
}
