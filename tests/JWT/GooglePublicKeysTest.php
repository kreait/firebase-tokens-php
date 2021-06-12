<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests;

use DateInterval;
use DateTimeImmutable;
use Kreait\Clock\FrozenClock;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys\Handler;
use Kreait\Firebase\JWT\GooglePublicKeys;
use Kreait\Firebase\JWT\Keys\ExpiringKeys;
use Kreait\Firebase\JWT\Keys\StaticKeys;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class GooglePublicKeysTest extends TestCase
{
    /** @var Handler|MockObject */
    private $handler;

    private FrozenClock $clock;

    private GooglePublicKeys $keys;

    private ExpiringKeys $expiringResult;

    private StaticKeys $staticResult;

    protected function setUp(): void
    {
        $now = new DateTimeImmutable();
        $now = $now->setTimestamp($now->getTimestamp()); // Trim microseconds, just to be sure

        $this->clock = new FrozenClock($now);
        $this->handler = $this->createMock(Handler::class);

        $this->expiringResult = ExpiringKeys::withValuesAndExpirationTime(['ir' => 'relevant'], $this->clock->now()->modify('+1 hour'));
        $this->staticResult = StaticKeys::withValues(['ir' => 'relevant']);

        $this->keys = new GooglePublicKeys($this->handler, $this->clock);
    }

    public function testItFetchesKeysOnlyTheFirstTime(): void
    {
        $this->handler->expects($this->once())->method('handle')->willReturn($this->expiringResult);

        $this->assertSame($this->expiringResult->all(), $this->keys->all());
        $this->assertSame($this->expiringResult->all(), $this->keys->all());
    }

    public function testItReFetchesKeysWhenTheyAreExpired(): void
    {
        $this->handler->expects($this->exactly(2))->method('handle')->willReturn($this->expiringResult);

        $this->keys->all();
        $this->clock->setTo($this->clock->now()->add(new DateInterval('PT2H')));
        $this->keys->all();
    }

    public function testItUsesNonExpiringKeysForever(): void
    {
        $this->handler->expects($this->once())->method('handle')->willReturn($this->staticResult);

        $this->assertSame($this->staticResult->all(), $this->keys->all());
        $this->assertSame($this->staticResult->all(), $this->keys->all());
    }
}
