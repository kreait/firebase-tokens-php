<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Action;

use InvalidArgumentException;
use Kreait\Firebase\JWT\Action\CreateCustomToken;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CreateCustomTokenTest extends TestCase
{
    /**
     * @test
     * @dataProvider invalidExpirationTimesInSeconds
     */
    public function it_rejects_an_invalid_expiration_time_in_seconds(int $seconds)
    {
        $this->expectException(InvalidArgumentException::class);
        CreateCustomToken::forUid('uid')->withTimeToLive($seconds);
    }

    public function invalidExpirationTimesInSeconds(): array
    {
        return [
            [0],
            [3601],
        ];
    }

    /** @test */
    public function the_uid_can_be_changed()
    {
        $action = CreateCustomToken::forUid('old')->withChangedUid('new');

        $this->assertSame('new', $action->uid());
    }

    /** @test */
    public function a_claim_can_be_set()
    {
        $action = CreateCustomToken::forUid('uid')
            ->withCustomClaims(['a' => 'b', 'c' => 'd'])
            ->withCustomClaim('c', 'x')
            ->withCustomClaim('e', 'f');

        $this->assertEquals(['a' => 'b', 'c' => 'x', 'e' => 'f'], $action->customClaims());
    }

    /** @test */
    public function custom_claims_can_be_added()
    {
        $action = CreateCustomToken::forUid('uid')
            ->withCustomClaims(['a' => 'b'])
            ->withAddedCustomClaims(['c' => 'd']);

        $this->assertEquals(['a' => 'b', 'c' => 'd'], $action->customClaims());
    }
}
