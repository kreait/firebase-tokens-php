<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Tests;

use DateTimeImmutable;
use Firebase\Auth\Token\Domain;
use Firebase\Auth\Token\Generator;
use Lcobucci\JWT\Token;

/**
 * @internal
 */
abstract class GeneratorTestCase extends TestCase
{
    protected Domain\Generator $generator;

    protected function setUp(): void
    {
        $this->generator = new class implements Domain\Generator {
            public function createCustomToken($uid, array $claims = []): Token
            {
                throw new \RuntimeException("Generator has not been set up");
            }
        };
    }

    public function testCreateCustomToken(): void
    {
        $token = $this->generator->createCustomToken($uid = 'some-uid', $claims = ['some' => 'claim']);

        $this->assertInstanceOf(Token\Plain::class, $token);
        $this->assertSame($uid, $token->claims()->get('uid'));
        $this->assertEqualsCanonicalizing($claims, $token->claims()->get('claims'));
    }

    public function testCreateCustomTokenWithEmptyClaims(): void
    {
        $token = $this->generator->createCustomToken('some-uid');
        $this->assertInstanceOf(Token\Plain::class, $token);

        $this->assertSame('some-uid', $token->claims()->get('uid'));
        $this->assertNull($token->claims()->get('claims'));
    }

    public function testDontCarryStateBetweenCalls(): void
    {
        $token1 = $this->generator->createCustomToken('first', ['admin' => true]);
        $token2 = $this->generator->createCustomToken('second');

        $this->assertInstanceOf(Token\Plain::class, $token1);
        $this->assertInstanceOf(Token\Plain::class, $token2);

        $this->assertSame(['admin' => true], $token1->claims()->get('claims'));
        $this->assertSame([], $token2->claims()->get('claims', []));
    }
}
