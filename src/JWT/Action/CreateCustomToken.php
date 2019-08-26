<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action;

use DateInterval;
use InvalidArgumentException;
use Kreait\Firebase\JWT\Value\Duration;

final class CreateCustomToken
{
    const MINIMUM_TTL = 'PT1S';
    const MAXIMUM_TTL = 'PT1H';
    const DEFAULT_TTL = self::MAXIMUM_TTL;

    /** @var string */
    private $uid;

    /** @var array */
    private $customClaims = [];

    /** @var Duration */
    private $ttl;

    private function __construct()
    {
        $this->ttl = Duration::fromDateIntervalSpec(self::DEFAULT_TTL);
    }

    public static function forUid(string $uid): self
    {
        $action = new self();
        $action->uid = $uid;

        return $action;
    }

    public function withChangedUid(string $uid): self
    {
        $action = clone $this;
        $action->uid = $uid;

        return $action;
    }

    public function withCustomClaim(string $name, $value): self
    {
        $action = clone $this;
        $action->customClaims[$name] = $value;

        return $action;
    }

    public function withCustomClaims(array $claims): self
    {
        $action = clone $this;
        $action->customClaims = $claims;

        return $action;
    }

    public function withAddedCustomClaims(array $claims): self
    {
        $action = clone $this;
        $action->customClaims = array_merge($action->customClaims, $claims);

        return $action;
    }

    /**
     * @param Duration|DateInterval|string|int $ttl
     */
    public function withTimeToLive($ttl): self
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

    public function customClaims(): array
    {
        return $this->customClaims;
    }

    public function timeToLive(): Duration
    {
        return $this->ttl;
    }
}
