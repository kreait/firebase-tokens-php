<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action;

use InvalidArgumentException;

final class CreateCustomToken
{
    const DEFAULT_EXPIRATION_TIME_IN_SECONDS = 3600;

    /** @var string */
    private $uid;

    /** @var array */
    private $customClaims = [];

    /** @var int */
    private $expirationTimeInSeconds = self::DEFAULT_EXPIRATION_TIME_IN_SECONDS;

    private function __construct()
    {
    }

    public static function forUid(string $uid): self
    {
        $action = new self();
        $action->uid = $uid;

        return $action;
    }

    public function withDifferentUid(string $uid): self
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
     * @throws InvalidArgumentException if the amount of seconds is invalid
     */
    public function withExpirationTimeInSeconds(int $seconds): self
    {
        if ($seconds < 1 || $seconds > 3600) {
            throw new InvalidArgumentException('A custom token\'s must expire after between 1 second and 1 hour');
        }

        $action = clone $this;
        $action->expirationTimeInSeconds = $seconds;

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

    public function expirationTimeInSeconds(): int
    {
        return $this->expirationTimeInSeconds;
    }
}
