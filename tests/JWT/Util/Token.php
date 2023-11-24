<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Util;

use Beste\Clock\SystemClock;
use DateTimeImmutable;
use DateTimeInterface;
use Kreait\Firebase\JWT\Signer\None;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Builder;
use Psr\Clock\ClockInterface;

final class Token
{
    private const ID_TOKEN_ISSUER = 'https://securetoken.google.com/project-id';
    private const SESSION_COOKIE_ISSUER = 'https://session.firebase.google.com/project-id';
    private readonly ClockInterface $clock;

    /** @var array<non-empty-string, string> */
    private array $headers = ['typ' => 'JWT', 'alg' => 'RS256', 'kid' => 'kid'];

    /** @var array<non-empty-string, mixed> */
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
     * @param non-empty-string $name
     */
    public function withClaim(string $name, mixed $value): self
    {
        $builder = clone $this;
        $builder->payload[$name] = $value;

        return $builder;
    }

    /**
     * @param non-empty-string $name
     */
    public function withoutClaim(string $name): self
    {
        $builder = clone $this;
        $builder->claimsToDelete[] = $name;

        return $builder;
    }

    /**
     * @param non-empty-string $name
     */
    public function withChangedHeader(string $name, string $value): self
    {
        $builder = clone $this;
        $builder->headers[$name] = $value;

        return $builder;
    }

    /**
     * @param non-empty-string $name
     */
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
     * @param array<non-empty-string, mixed> $extra
     *
     * @return non-empty-string
     */
    public function idToken(array $extra = []): string
    {
        return $this->build(self::ID_TOKEN_ISSUER, $extra);
    }

    /**
     * @param array<non-empty-string, mixed> $extra
     *
     * @return non-empty-string
     */
    public function sessionCookie(array $extra = []): string
    {
        return $this->build(self::SESSION_COOKIE_ISSUER, $extra);
    }

    /**
     * @param non-empty-string $issuer
     * @param array<non-empty-string, mixed> $extra
     *
     * @return non-empty-string
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
        $payload['iat'] ??= $now;
        $payload['auth_time'] ??= $now->modify('-1 second');
        $payload['exp'] ??= $now->modify('+1 hour');

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
     * @param array<non-empty-string, mixed> $payload
     * @param array<non-empty-string, mixed> $headers
     *
     * @return non-empty-string
     */
    private function encode(array $payload, array $headers): string
    {
        $builder = new Builder(new JoseEncoder(), ChainedFormatter::default());

        foreach ($headers as $name => $value) {
            $builder = $builder->withHeader($name, $value);
        }

        foreach ($payload as $name => $value) {
            if ($name === 'iss' && is_string($value) && $value !== '') {
                $builder = $builder->issuedBy($value);
                continue;
            }

            if ($name === 'iat' && ($value instanceof DateTimeImmutable)) {
                $builder = $builder->issuedAt($value);
                continue;
            }

            if ($name === 'aud' && is_string($value) && $value !== '') {
                $builder = $builder->permittedFor($value);
                continue;
            }

            if ($name === 'sub' && is_string($value) && $value !== '') {
                $builder = $builder->relatedTo($value);
                continue;
            }

            if ($name === 'nbf' && ($value instanceof DateTimeImmutable)) {
                $builder = $builder->canOnlyBeUsedAfter($value);
                continue;
            }

            if ($name === 'exp' && ($value instanceof DateTimeImmutable)) {
                $builder = $builder->expiresAt($value);
                continue;
            }

            if ($value instanceof DateTimeInterface) {
                $value = $value->format('U');
            }

            $builder = $builder->withClaim($name, $value);
        }

        if ($this->privateKey) {
            // @phpstan-ignore-next-line
            return $builder->getToken(new Sha256(), InMemory::plainText($this->privateKey))->toString();
        }

        // @phpstan-ignore-next-line
        return $builder->getToken(new None(), new EmptyKey())->toString();
    }
}
