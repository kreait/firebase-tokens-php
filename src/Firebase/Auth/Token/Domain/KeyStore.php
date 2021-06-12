<?php

declare(strict_types=1);

namespace Firebase\Auth\Token\Domain;

use OutOfBoundsException;

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
