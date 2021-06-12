<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Tests;

use DateTimeImmutable;
use Firebase\Auth\Token\Generator;
use Lcobucci\JWT\Token;

/**
 * @internal
 */
class GeneratorTest extends TestCase
{
    /** @var Generator */
    protected $generator;

    protected function setUp(): void
    {
        $this->generator = new Generator('user@domain.tld', $this->onePrivateKey()->contents());
    }

    public function testCreateCustomToken(): void
    {
        $token = $this->generator->createCustomToken('some-uid', ['some' => 'claim']);

        $this->assertInstanceOf(Token::class, $token);
    }

    public function testCreateCustomTokenWithEmptyClaims(): void
    {
        $token = $this->generator->createCustomToken('some-uid');

        $this->assertSame('some-uid', $token->claims()->get('uid'));
    }

    public function testCreateCustomTokenWithCustomExpiration(): void
    {
        $expiresAt = (new DateTimeImmutable())->modify(\random_int(1, 3600).' minutes');

        $token = $this->generator->createCustomToken('some-uid', [], $expiresAt);

        $this->assertSame($expiresAt->getTimestamp(), $token->claims()->get('exp')->getTimestamp());
    }

    public function testDontCarryStateBetweenCalls(): void
    {
        $token1 = $this->generator->createCustomToken('first', ['admin' => true]);
        $token2 = $this->generator->createCustomToken('second');

        $this->assertSame(['admin' => true], $token1->claims()->get('claims'));
        $this->assertSame([], $token2->claims()->get('claims', []));
    }
}
