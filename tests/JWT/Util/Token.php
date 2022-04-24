<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Util;

use Beste\Clock\SystemClock;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\None;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Builder;
use StellaMaris\Clock\ClockInterface;

final class Token
{
    private const ID_TOKEN_ISSUER = 'https://securetoken.google.com/project-id';
    private const SESSION_COOKIE_ISSUER = 'https://session.firebase.google.com/project-id';

    private ClockInterface $clock;

    /** @var array<string, string> */
    private array $headers = ['typ' => 'JWT', 'alg' => 'RS256', 'kid' => 'kid'];

    /** @var array<string, mixed> */
    private array $payload;

    /** @var string[] */
    private array $claimsToDelete = [];

    /** @var string[] */
    private array $headersToDelete = [];

    private ?string $privateKey;

    public function __construct(?ClockInterface $clock = null)
    {
        $this->clock = $clock ?: SystemClock::create();
        $this->payload = $this->defaultPayload();
        $this->privateKey = KeyPair::privateKey();
    }

    /**
     * @param mixed $value
     */
    public function withClaim(string $name, $value): self
    {
        $builder = clone $this;
        $builder->payload[$name] = $value;

        return $builder;
    }

    public function withoutClaim(string $name): self
    {
        $builder = clone $this;
        $builder->claimsToDelete[] = $name;

        return $builder;
    }

    public function withChangedHeader(string $name, string $value): self
    {
        $builder = clone $this;
        $builder->headers[$name] = $value;

        return $builder;
    }

    public function withoutHeader(string $name): self
    {
        $builder = clone $this;
        $builder->headersToDelete[] = $name;

        return $builder;
    }

    public function withoutSignature(): self
    {
        $builder = clone $this;
        $builder->privateKey = null;

        return $builder;
    }

    /**
     * @param array<string, mixed> $extra
     */
    public function idToken(array $extra = []): string
    {
        return $this->build(self::ID_TOKEN_ISSUER, $extra);
    }

    /**
     * @param array<string, mixed> $extra
     */
    public function sessionCookie(array $extra = []): string
    {
        return $this->build(self::SESSION_COOKIE_ISSUER, $extra);
    }

    /**
     * @param array<string, scalar> $extra
     */
    private function build(string $issuer, array $extra = []): string
    {
        $now = $this->clock->now();

        $headers = $this->headers;

        foreach ($this->headersToDelete as $header) {
            unset($headers[$header]);
        }

        $payload = $this->payload;
        $payload['iss'] = $issuer;
        $payload['iat'] = $payload['iat'] ?? $now;
        $payload['auth_time'] = $payload['auth_time'] ?? $now->modify('-1 second');
        $payload['exp'] = $payload['exp'] ?? $now->modify('+1 hour');

        foreach ($extra as $key => $value) {
            $payload[$key] = $value;
        }

        foreach ($this->claimsToDelete as $claim) {
            unset($payload[$claim]);
        }

        return $this->encode($payload, $headers);
    }

    /**
     * @return array{iss: string, sub: string, aud: string}
     */
    private function defaultPayload(): array
    {
        return [
            'iss' => 'https://securetoken.google.com/project-id',
            'sub' => 'uid',
            'aud' => 'project-id',
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     */
    private function encode(array $payload, array $headers): string
    {
        $builder = new Builder(new JoseEncoder(), ChainedFormatter::default());

        foreach ($headers as $name => $value) {
            $builder = $builder->withHeader($name, $value);
        }

        foreach ($payload as $name => $value) {
            switch ($name) {
                case 'iss':
                    $builder = $builder->issuedBy($value);

                    break;

                case 'iat':
                    $builder = $builder->issuedAt($value);

                    break;

                case 'aud':
                    $builder = $builder->permittedFor($value);

                    break;

                case 'sub':
                    $builder = $builder->relatedTo($value);

                    break;

                case 'nbf':
                    $builder = $builder->canOnlyBeUsedAfter($value);

                    break;

                case 'exp':
                    $builder = $builder->expiresAt($value);

                    break;

                default:
                    if ($value instanceof \DateTimeInterface) {
                        $value = $value->format('U');
                    }

                    $builder = $builder->withClaim($name, $value);

                    break;
            }
        }

        if ($this->privateKey) {
            return $builder->getToken(new Sha256(), InMemory::plainText($this->privateKey))->toString();
        }

        return $builder->getToken(new None(), InMemory::empty())->toString();
    }
}
