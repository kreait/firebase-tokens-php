<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action\FetchGooglePublicKeys;

use DateTimeImmutable;
use Kreait\Clock\FrozenClock;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys\Handler;

/**
 * @internal
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    abstract protected function createHandler(): Handler;

    protected FrozenClock $clock;

    protected FetchGooglePublicKeys $action;

    protected function setUp(): void
    {
        $now = new DateTimeImmutable();
        $now = $now->setTimestamp($now->getTimestamp()); // Trim microseconds, just to be sure
        $this->clock = new FrozenClock($now);

        $this->action = FetchGooglePublicKeys::fromUrl('bogus');
    }

    public function testItReturnsKeys(): void
    {
        $this->createHandler()->handle($this->action);
        $this->addToAssertionCount(1);
    }
}
