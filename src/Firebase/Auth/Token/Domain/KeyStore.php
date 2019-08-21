<?php

namespace Firebase\Auth\Token\Domain;

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
