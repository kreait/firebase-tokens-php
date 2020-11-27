<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action\CreateCustomToken;

use Kreait\Clock;
use Kreait\Firebase\JWT\Action\CreateCustomToken;
use Kreait\Firebase\JWT\Contract\Token;
use Kreait\Firebase\JWT\Error\CustomTokenCreationFailed;
use Kreait\Firebase\JWT\Token as TokenInstance;
use Lcobucci\JWT\Builder;
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
            ->issuedAt($now)
            ->issuedBy($this->clientEmail)
            ->expiresAt($now->add($action->timeToLive()->value()))
            ->relatedTo($this->clientEmail)
            ->permittedFor('https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit')
            ->withClaim('uid', $action->uid())
        ;

        if ($tenantId = $action->tenantId()) {
            $builder = $builder->withClaim('tenant_id', $tenantId);
        }

        if (!empty($customClaims = $action->customClaims())) {
            $builder = $builder->withClaim('claims', $customClaims);
        }

        try {
            $token = $builder->getToken($this->signer, new Signer\Key($this->privateKey));
        } catch (Throwable $e) {
            throw CustomTokenCreationFailed::because($e->getMessage(), $e->getCode(), $e);
        }

        $claims = $token->claims()->all();
        foreach ($claims as &$claim) {
            if ($claim instanceof \DateTimeInterface) {
                $claim = $claim->getTimestamp();
            }
        }
        unset($claim);

        $headers = $token->headers()->all();
        foreach ($headers as &$header) {
            if ($header instanceof \DateTimeInterface) {
                $header = $header->getTimestamp();
            }
        }
        unset($header);

        return TokenInstance::withValues((string) $token, $headers, $claims);
    }
}
