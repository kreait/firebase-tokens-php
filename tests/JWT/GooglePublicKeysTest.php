<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests;

use DateInterval;
use DateTimeImmutable;
use Kreait\Clock\FrozenClock;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;
use Kreait\Firebase\JWT\GooglePublicKeys;
use Kreait\Firebase\JWT\Keys\ExpiringKeys;
use Kreait\Firebase\JWT\Keys\StaticKeys;
use PHPUnit\Framework\TestCase;

final class GooglePublicKeysTest extends TestCase
{
    private $handler;

    /** @var FrozenClock */
    private $clock;

    /** @var GooglePublicKeys */
    private $keys;

    /** @var ExpiringKeys */
    private $expiringResult;

    /** @var StaticKeys */
    private $staticResult;

    protected function setUp()
    {
        $now = new DateTimeImmutable();
        $now = $now->setTimestamp($now->getTimestamp()); // Trim microseconds, just to be sure

        $this->clock = new FrozenClock($now);
        $this->handler = $this->createMock(FetchGooglePublicKeys\Handler::class);

        $this->expiringResult = ExpiringKeys::withValuesAndExpirationTime(['ir' => 'relevant'], $this->clock->now()->modify('+1 hour'));
        $this->staticResult = StaticKeys::withValues(['ir' => 'relevant']);

        $this->keys = new GooglePublicKeys($this->handler, $this->clock);
    }

    /** @test */
    public function it_fetches_keys_only_the_first_time()
    {
        $this->handler->expects($this->once())->method('handle')->willReturn($this->expiringResult);

        $this->assertSame($this->expiringResult->all(), $this->keys->all());
        $this->assertSame($this->expiringResult->all(), $this->keys->all());
    }

    /** @test */
    public function it_re_fetches_keys_when_they_are_expired()
    {
        $this->handler->expects($this->exactly(2))->method('handle')->willReturn($this->expiringResult);

        $this->keys->all();
        $this->clock->setTo($this->clock->now()->add(new DateInterval('PT2H')));
        $this->keys->all();
    }

    /** @test */
    public function it_uses_non_expiring_keys_forever()
    {
        $this->handler->expects($this->once())->method('handle')->willReturn($this->staticResult);

        $this->assertSame($this->staticResult->all(), $this->keys->all());
        $this->assertSame($this->staticResult->all(), $this->keys->all());
    }
}
