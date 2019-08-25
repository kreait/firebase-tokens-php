<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action;

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
}
