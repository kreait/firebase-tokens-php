<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action;

use DateTimeImmutable;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;
use Kreait\Firebase\JWT\Value\Duration;
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
        $this->assertTrue(Duration::make('PT1H')->equals($fallbackCacheDuration));
    }

    /** @test */
    public function its_fallback_cache_duration_can_be_changed()
    {
        $duration = 'PT13H37M';
        $action = FetchGooglePublicKeys::fromGoogle()->ifKeysDoNotExpireCacheFor($duration);

        $this->assertTrue(Duration::make($duration)->equals($action->getFallbackCacheDuration()));
    }
}
