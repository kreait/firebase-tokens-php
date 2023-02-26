<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action;

use InvalidArgumentException;
use Kreait\Firebase\JWT\Action\VerifySessionCookie;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class VerifySessionCookieTest extends TestCase
{
    public function testItRejectsANegativeLeeway(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // @phpstan-ignore-next-line
        VerifySessionCookie::withSessionCookie('cookie')->withLeewayInSeconds(-1);
    }
}
