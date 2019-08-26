<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action\CreateCustomToken;

use Kreait\Clock;
use Kreait\Firebase\JWT\Action\CreateCustomToken;
use Kreait\Firebase\JWT\Contract\Token;
use Kreait\Firebase\JWT\Error\CustomTokenCreationFailed;
use Kreait\Firebase\JWT\Token as TokenInstance;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Claim;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Throwable;

final class WithLcobucciV3JWT implements Handler
{
    /** @var string */
    private $clientEmail;

    /** @var string */
    private $privateKey;

    /** @var Signer */
    private $signer;

    /** @var Clock */
    private $clock;

    public function __construct(string $clientEmail, string $privateKey, Clock $clock)
    {
        $this->clientEmail = $clientEmail;
        $this->privateKey = $privateKey;
        $this->signer = new Sha256();
        $this->clock = $clock;
    }

    public function handle(CreateCustomToken $action): Token
    {
        $now = $this->clock->now();

        $builder = (new Builder())
            ->setIssuedAt($now->getTimestamp())
            ->setIssuer($this->clientEmail)
            ->setExpiration($now->add($action->timeToLive()->value())->getTimestamp())
            ->setSubject($this->clientEmail)
            ->setAudience('https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit')
            ->set('uid', $action->uid())
        ;

        if (!empty($customClaims = $action->customClaims())) {
            $builder = $builder->set('claims', $customClaims);
        }

        try {
            $token = $builder->getToken($this->signer, new Signer\Key($this->privateKey));
        } catch (Throwable $e) {
            throw CustomTokenCreationFailed::because($e->getMessage(), $e->getCode(), $e);
        }

        /** @var Claim[] $claims */
        $claims = $token->getClaims();

        $payload = [];
        foreach ($claims as $claim) {
            $payload[$claim->getName()] = $claim->getValue();
        }

        return TokenInstance::withValues((string) $token, $token->getHeaders(), $payload);
    }
}
