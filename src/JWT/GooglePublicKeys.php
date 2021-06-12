<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT;

use Kreait\Clock;
use Kreait\Clock\SystemClock;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys\Handler;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys\WithHandlerDiscovery;
use Kreait\Firebase\JWT\Contract\Expirable;
use Kreait\Firebase\JWT\Contract\Keys;

final class GooglePublicKeys implements Keys
{
    private Clock $clock;

    private Handler $handler;

    private ?Keys $keys = null;

    public function __construct(Handler $handler = null, Clock $clock = null)
    {
        $this->clock = $clock ?: new SystemClock();
        $this->handler = $handler ?: new WithHandlerDiscovery($this->clock);
    }

    public function all(): array
    {
        $keysAreThereButExpired = $this->keys instanceof Expirable && $this->keys->isExpiredAt($this->clock->now());

        if (!$this->keys || $keysAreThereButExpired) {
            $this->keys = $this->handler->handle(FetchGooglePublicKeys::fromGoogle());
            // There is a small chance that we get keys that are already expired, but at this point we're happy
            // that we got keys at all. The next time this method gets called, we will re-fetch.
        }

        return $this->keys->all();
    }
}
