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
     * @dataProvider invalidExpirationTimesInSeconds
     */
    public function testItRejectsAnInvalidExpirationTimeInSeconds(int $seconds): void
    {
        $this->expectException(InvalidArgumentException::class);
        CreateCustomToken::forUid('uid')->withTimeToLive($seconds);
    }

    /**
     * @return array<string, array<array-key, int>>
     */
    public function invalidExpirationTimesInSeconds(): array
    {
        return [
            'zero' => [0],
            'more than 1 hour' => [3601],
        ];
    }

    public function testTheUidCanBeChanged(): void
    {
        $action = CreateCustomToken::forUid('old')->withChangedUid('new');

        $this->assertSame('new', $action->uid());
    }

    public function testAClaimCanBeSet(): void
    {
        $action = CreateCustomToken::forUid('uid')
            ->withCustomClaims(['a' => 'b', 'c' => 'd'])
            ->withCustomClaim('c', 'x')
            ->withCustomClaim('e', 'f')
        ;

        $this->assertEquals(['a' => 'b', 'c' => 'x', 'e' => 'f'], $action->customClaims());
    }

    public function testCustomClaimsCanBeAdded(): void
    {
        $action = CreateCustomToken::forUid('uid')
            ->withCustomClaims(['a' => 'b'])
            ->withAddedCustomClaims(['c' => 'd'])
        ;

        $this->assertEquals(['a' => 'b', 'c' => 'd'], $action->customClaims());
    }
}
