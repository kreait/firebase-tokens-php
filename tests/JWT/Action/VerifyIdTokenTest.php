<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action;

use InvalidArgumentException;
use Kreait\Firebase\JWT\Action\VerifyIdToken;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class VerifyIdTokenTest extends TestCase
{
    public function testItRejectsANegativeLeeway(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // @phpstan-ignore-next-line
        VerifyIdToken::withToken('token')->withLeewayInSeconds(-1);
    }
}
