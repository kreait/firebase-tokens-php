<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action\CreateCustomToken;

use DateTimeInterface;
use Kreait\Clock;
use Kreait\Firebase\JWT\Action\CreateCustomToken;
use Kreait\Firebase\JWT\Contract\Token;
use Kreait\Firebase\JWT\Error\CustomTokenCreationFailed;
use Kreait\Firebase\JWT\Token as TokenInstance;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Token\Plain;
use Throwable;

final class WithLcobucciJWT implements Handler
{
    /** @var string */
    private $clientEmail;

    /** @var Clock */
    private $clock;

    /** @var Configuration */
    private $config;

    public function __construct(string $clientEmail, string $privateKey, Clock $clock)
    {
        $this->clientEmail = $clientEmail;
        $this->clock = $clock;

        $this->config = Configuration::forSymmetricSigner(
            new Signer\Rsa\Sha256(),
            Signer\Key\InMemory::plainText($privateKey)
        );
    }

    public function handle(CreateCustomToken $action): Token
    {
        $now = $this->clock->now();

        $builder = $this->config->builder()
            ->issuedAt($now)
            ->issuedBy($this->clientEmail)
            ->expiresAt($now->add($action->timeToLive()->value()))
            ->relatedTo($this->clientEmail)
            ->permittedFor('https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit')
            ->withClaim('uid', $action->uid());

        if ($tenantId = $action->tenantId()) {
            $builder = $builder->withClaim('tenant_id', $tenantId);
        }

        if (!empty($customClaims = $action->customClaims())) {
            $builder = $builder->withClaim('claims', $customClaims);
        }

        try {
            $token = $builder->getToken($this->config->signer(), $this->config->signingKey());
        } catch (Throwable $e) {
            throw CustomTokenCreationFailed::because($e->getMessage(), $e->getCode(), $e);
        }

        if (!($token instanceof Plain)) {
            return TokenInstance::withValues($token->toString(), [], []);
        }

        $claims = $token->claims()->all();
        foreach ($claims as &$claim) {
            if ($claim instanceof DateTimeInterface) {
                $claim = $claim->getTimestamp();
            }
        }
        unset($claim);

        $headers = $token->headers()->all();
        foreach ($headers as &$header) {
            if ($header instanceof DateTimeInterface) {
                $header = $header->getTimestamp();
            }
        }
        unset($header);

        return TokenInstance::withValues($token->toString(), $headers, $claims);
    }
}
