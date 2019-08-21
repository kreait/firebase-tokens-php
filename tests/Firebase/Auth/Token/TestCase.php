<?php

namespace Firebase\Auth\Token\Tests;

use Lcobucci\JWT\Signature;
use Lcobucci\JWT\Signer;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @return Signer|\PHPUnit_Framework_MockObject_MockObject
     */
    public function createMockSigner()
    {
        $signer = $this->createMock(Signer::class);

        $signer->method('getAlgorithmId')
            ->willReturn('mock');

        $signer->method('modifyHeader')
            ->willReturnCallback(function (&$headers) {
                $headers['alg'] = 'mock';
            });

        $signer->method('sign')
            ->willReturnCallback(function ($payload, $key) {
                $key = is_string($key) ? new Signer\Key($key) : $key;

                return new Signature($payload.$key->getContent());
            });

        $signer->method('verify')
            ->willReturnCallback(function ($expected, $payload, $key) {
                $key = is_string($key) ? new Signer\Key($key) : $key;

                return $expected === $payload.$key->getContent();
            });

        return $signer;
    }
}
