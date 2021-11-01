<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Tests;

use Firebase\Auth\Token\Domain\Generator;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Plain;
use RuntimeException;

/**
 * @internal
 */
abstract class GeneratorTestCase extends TestCase
{
    protected Generator $generator;

    protected function setUp(): void
    {
        $this->generator = new class() implements Generator {
            public function createCustomToken($uid, array $claims = []): Token
            {
                throw new RuntimeException('Generator has not been set up');
            }
        };
    }

    public function testCreateCustomToken(): void
    {
        $token = $this->generator->createCustomToken($uid = 'some-uid', $claims = ['some' => 'claim']);

        $this->assertInstanceOf(Plain::class, $token);
        $this->assertSame($uid, $token->claims()->get('uid'));
        $this->assertEqualsCanonicalizing($claims, $token->claims()->get('claims'));
    }

    public function testCreateCustomTokenWithEmptyClaims(): void
    {
        $token = $this->generator->createCustomToken('some-uid');
        $this->assertInstanceOf(Plain::class, $token);

        $this->assertSame('some-uid', $token->claims()->get('uid'));
        $this->assertNull($token->claims()->get('claims'));
    }

    public function testDontCarryStateBetweenCalls(): void
    {
        $token1 = $this->generator->createCustomToken('first', ['admin' => true]);
        $token2 = $this->generator->createCustomToken('second');

        $this->assertInstanceOf(Plain::class, $token1);
        $this->assertInstanceOf(Plain::class, $token2);

        $this->assertSame(['admin' => true], $token1->claims()->get('claims'));
        $this->assertSame([], $token2->claims()->get('claims', []));
    }
}
