<?php

namespace Firebase\Auth\Token\Exception;

class UnknownKey extends \LogicException
{
    /**
     * @var string
     */
    private $keyId;

    public function __construct($keyId)
    {
        parent::__construct(sprintf('A key with ID "%s" could not be found.', $keyId));

        $this->keyId = $keyId;
    }

    public function getKeyId()
    {
        return $this->keyId;
    }
}
