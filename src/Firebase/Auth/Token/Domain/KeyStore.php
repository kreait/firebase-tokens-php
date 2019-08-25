<?php

namespace Firebase\Auth\Token\Domain;

/**
 * @deprecated 1.9.0
 */
interface KeyStore
{
    /**
     * @param string $keyId
     *
     * @throws \OutOfBoundsException
     *
     * @return string
     */
    public function get($keyId);
}
