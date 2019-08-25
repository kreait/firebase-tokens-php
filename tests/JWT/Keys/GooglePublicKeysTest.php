<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Keys;

use DateTimeImmutable;
use Kreait\Clock\FrozenClock;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;
use Kreait\Firebase\JWT\Keys\ExpiringKeys;
use Kreait\Firebase\JWT\Keys\GooglePublicKeys;
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

    /** @var ExpiringKeys */
    private $expiredResult;

    /** @var StaticKeys */
    private $nonExpiringResult;

    protected function setUp()
    {
        $now = new DateTimeImmutable();
        $now = $now->setTimestamp($now->getTimestamp()); // Trim microseconds, just to be sure

        $this->clock = new FrozenClock($now);
        $this->handler = $this->createMock(FetchGooglePublicKeys\Handler::class);

        $this->expiringResult = ExpiringKeys::withValuesAndExpirationTime(['ir' => 'relevant'], $this->clock->now()->modify('+1 hour'));
        $this->expiredResult = ExpiringKeys::withValuesAndExpirationTime(['ir' => 'relevant'], $this->clock->now()->modify('-1 hour'));
        $this->nonExpiringResult = StaticKeys::withValues(['ir_relevant']);

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
        $this->handler->expects($this->at(0))->method('handle')->willReturn($this->expiredResult);
        $this->handler->expects($this->at(1))->method('handle')->willReturn($this->expiringResult);

        $this->assertSame($this->expiredResult->all(), $this->keys->all());
        $this->assertSame($this->expiringResult->all(), $this->keys->all());
    }

    /** @test */
    public function it_uses_non_expiring_keys_forever()
    {
        $this->handler->expects($this->once())->method('handle')->willReturn($this->nonExpiringResult);

        $this->assertSame($this->nonExpiringResult->all(), $this->keys->all());
        $this->assertSame($this->nonExpiringResult->all(), $this->keys->all());
    }
}
