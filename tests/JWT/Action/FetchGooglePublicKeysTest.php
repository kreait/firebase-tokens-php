<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action;

use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;
use Kreait\Firebase\JWT\Value\Duration;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class FetchGooglePublicKeysTest extends TestCase
{
    public function testItsUrlPointsToGoogle(): void
    {
        $this->assertSame(
            'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com',
            FetchGooglePublicKeys::fromGoogle()->url()
        );
    }

    public function testItsUrlCanBeChanged(): void
    {
        $this->assertSame(
            'https://domain.tld',
            FetchGooglePublicKeys::fromUrl('https://domain.tld')->url()
        );
    }

    public function testItHasAFallbackCacheDurationOfOneHour(): void
    {
        $fallbackCacheDuration = FetchGooglePublicKeys::fromGoogle()->getFallbackCacheDuration();
        $this->assertTrue(Duration::make('PT1H')->equals($fallbackCacheDuration));
    }

    public function testItsFallbackCacheDurationCanBeChanged(): void
    {
        $duration = 'PT13H37M';
        $action = FetchGooglePublicKeys::fromGoogle()->ifKeysDoNotExpireCacheFor($duration);

        $this->assertTrue(Duration::make($duration)->equals($action->getFallbackCacheDuration()));
    }
}
