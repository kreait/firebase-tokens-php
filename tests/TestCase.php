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
                return new Signature($payload.$key);
            });

        $signer->method('verify')
            ->willReturnCallback(function ($expected, $payload, $key) {
                return $expected === $payload.$key;
            });

        return $signer;
    }
}
