<?php

namespace Firebase\Auth\Token;

use Firebase\Auth\Token\Domain\KeyStore;

/**
 * @codeCoverageIgnore
 */
class ArrayKeyStore implements KeyStore
{
    private $keys;

    public function __construct(array $keys)
    {
        $this->keys = $keys;
    }

    public function get($keyId)
    {
        if (!array_key_exists($keyId, $this->keys)) {
            throw new \OutOfBoundsException();
        }

        return $this->keys[$keyId];
    }
}
