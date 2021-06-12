<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Tests\Util;

use Firebase\Auth\Token\Domain\KeyStore;
use OutOfBoundsException;

class ArrayKeyStore implements KeyStore
{
    /**
     * @var array<string, string>
     */
    private array $keys;

    /**
     * @param array<string, string> $keys
     */
    public function __construct(array $keys)
    {
        $this->keys = $keys;
    }

    public function get($keyId)
    {
        if (!\array_key_exists($keyId, $this->keys)) {
            throw new OutOfBoundsException(\sprintf('Key with ID "%s" not found.', $keyId));
        }

        return $this->keys[$keyId];
    }
}
