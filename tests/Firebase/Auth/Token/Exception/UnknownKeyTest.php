<?php

namespace Firebase\Auth\Token\Tests\Exception;

use Firebase\Auth\Token\Exception\UnknownKey;
use Firebase\Auth\Token\Tests\TestCase;
use Lcobucci\JWT\Token;

class UnknownKeyTest extends TestCase
{
    /** @test */
    public function it_provides_the_key_id()
    {
        $token = $this->createMock(Token::class);
        $keyId = 'some-kid';

        $this->assertSame($keyId, (new UnknownKey($token, $keyId))->getKeyId());
    }
}
