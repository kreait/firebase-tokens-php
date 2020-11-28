<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Domain;

use OutOfBoundsException;

/**
 * @deprecated 1.9.0
 */
interface KeyStore
{
    /**
     * @param string $keyId
     *
     * @throws OutOfBoundsException
     *
     * @return string
     */
    public function get($keyId);
}
