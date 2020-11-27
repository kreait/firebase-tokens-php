<?php

namespace Firebase\Auth\Token\Tests\Exception;

use Firebase\Auth\Token\Exception\InvalidToken;
use Firebase\Auth\Token\Tests\TestCase;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key\InMemory;

class InvalidTokenTest extends TestCase
{
    /** @test */
    public function it_provides_the_token()
    {
        $token = (new Builder())->getToken($this->createMockSigner(), InMemory::plainText('valid_key'));

        $exception = new InvalidToken($token);

        $this->assertSame($token, $exception->getToken());
    }
}
