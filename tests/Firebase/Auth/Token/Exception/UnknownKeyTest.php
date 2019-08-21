<?php

namespace Firebase\Auth\Token\Tests\Exception;

use Firebase\Auth\Token\Exception\UnknownKey;
use Firebase\Auth\Token\Tests\TestCase;

class UnknownKeyTest extends TestCase
{
    /** @test */
    public function it_provides_the_key_id()
    {
        $keyId = 'some-kid';

        $this->assertSame($keyId, (new UnknownKey($keyId))->getKeyId());
    }
}
