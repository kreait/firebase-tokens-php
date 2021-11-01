<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Tests;

use DateTimeImmutable;
use Firebase\Auth\Token\Generator;
use Lcobucci\JWT\Token;

/**
 * @internal
 * @property Generator $generator
 */
class GeneratorTest extends GeneratorTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = new Generator('user@domain.tld', $this->onePrivateKey()->contents());
    }

    public function testCreateCustomTokenWithCustomExpiration(): void
    {
        $expiresAt = (new DateTimeImmutable())->modify(\random_int(1, 3600).' minutes');

        $token = $this->generator->createCustomToken('some-uid', [], $expiresAt);
        $this->assertInstanceOf(Token\Plain::class, $token);

        $this->assertSame($expiresAt->getTimestamp(), $token->claims()->get('exp')->getTimestamp());
    }
}
