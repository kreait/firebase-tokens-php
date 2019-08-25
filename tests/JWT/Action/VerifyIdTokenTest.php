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
    /** @test */
    public function it_rejects_a_negative_leeway()
    {
        $this->expectException(InvalidArgumentException::class);
        VerifyIdToken::withToken('token')->withLeewayInSeconds(-1);
    }
}
