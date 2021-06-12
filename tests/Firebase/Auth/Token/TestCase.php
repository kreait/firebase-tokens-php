<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Tests;

use Firebase\Auth\Token\Domain\KeyStore;
use Firebase\Auth\Token\Tests\Util\ArrayKeyStore;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Key\LocalFileReference;
use Lcobucci\JWT\Signer\Rsa\Sha256;

/**
 * @internal
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function onePrivateKey(): Key
    {
        return LocalFileReference::file(__DIR__.'/../../../_fixtures/one.key');
    }

    protected function onePublicKey(): Key
    {
        return LocalFileReference::file(__DIR__.'/../../../_fixtures/one.pub');
    }

    protected function createJwtConfiguration(): Configuration
    {
        return Configuration::forSymmetricSigner(new Sha256(), $this->onePrivateKey());
    }

    protected function createKeyStore(): KeyStore
    {
        return new ArrayKeyStore(['valid_key_id' => $this->onePublicKey()->contents()]);
    }
}
