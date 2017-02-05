<?php

namespace Firebase\Auth\Token;

use Lcobucci\JWT\Token;

final class Handler implements Domain\Generator, Domain\Verifier
{
    /**
     * @var Generator
     */
    private $generator;

    /**
     * @var Verifier
     */
    private $verifier;

    public function __construct(string $projectId, string $clientEmail, string $privateKey)
    {
        $this->generator = new Generator($clientEmail, $privateKey);
        $this->verifier = new Verifier($projectId);
    }

    public function createCustomToken($uid, array $claims = []): Token
    {
        return $this->generator->createCustomToken($uid, $claims);
    }

    public function verifyIdToken($token): Token
    {
        return $this->verifier->verifyIdToken($token);
    }
}
