<?php

namespace Firebase\Auth\Token\Tests;

use Firebase\Auth\Token\Generator;
use Lcobucci\JWT\Token;

class GeneratorTest extends TestCase
{
    /**
     * @var Generator
     */
    private $generator;

    protected function setUp()
    {
        $this->generator = new Generator('user@domain.tld', 'some-key', $this->createMockSigner());
    }

    public function testCreateCustomToken()
    {
        $token = $this->generator->createCustomToken('some-uid', ['some' => 'claim']);

        $this->assertInstanceOf(Token::class, $token);
    }

    public function testCreateCustomTokenWithEmptyClaims()
    {
        $token = $this->generator->createCustomToken('some-uid');

        $this->assertInstanceOf(Token::class, $token);
    }

    public function testCreateCustomTokenWithCustomExpiration()
    {
        $expiresAt = (new \DateTimeImmutable())->modify(random_int(1, 3600).' minutes');

        $token = $this->generator->createCustomToken('some-uid', [], $expiresAt);

        $this->assertSame($expiresAt->getTimestamp(), $token->getClaim('exp'));
    }
}
