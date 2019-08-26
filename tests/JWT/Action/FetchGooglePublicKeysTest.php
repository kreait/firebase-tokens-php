<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action;

use DateInterval;
use DateTimeImmutable;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class FetchGooglePublicKeysTest extends TestCase
{
    /** @test */
    public function its_url_points_to_google()
    {
        $this->assertSame(
            'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com',
            FetchGooglePublicKeys::fromGoogle()->url()
        );
    }

    /** @test */
    public function its_url_can_be_changed()
    {
        $this->assertSame(
            'https://domain.tld',
            FetchGooglePublicKeys::fromUrl('https://domain.tld')->url()
        );
    }

    /** @test */
    public function it_has_a_fallback_cache_duration_of_one_hour()
    {
        $now = new DateTimeImmutable();

        $fallbackCacheDuration = FetchGooglePublicKeys::fromGoogle()->getFallbackCacheDuration();
        $expectedDuration = new DateInterval('PT1H');

        // Date intervals cannot reliably be compared themselves, e.g. PT60M != PT1H
        $this->assertSame(
            $now->add($expectedDuration)->getTimestamp(),
            $now->add($fallbackCacheDuration)->getTimestamp()
        );
    }

    /** @test */
    public function its_fallback_cache_duration_can_be_changed()
    {
        $duration = new DateInterval('PT13H37M');
        $action = FetchGooglePublicKeys::fromGoogle()->ifKeysDoNotExpireCacheFor($duration);

        $this->assertSame($duration, $action->getFallbackCacheDuration());
    }
}
