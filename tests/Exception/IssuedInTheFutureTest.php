<?php

namespace Firebase\Auth\Token\Tests\Exception;

use Firebase\Auth\Token\Exception\InvalidToken;
use Firebase\Auth\Token\Exception\IssuedInTheFuture;
use Firebase\Auth\Token\Tests\TestCase;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Token;

class IssuedInTheFutureTest extends TestCase
{
    /**
     * @var Token
     */
    private $token;

    /**
     * @var \DateTimeImmutable
     */
    private $expiryDate;

    protected function setUp()
    {
        $this->expiryDate = (new \DateTimeImmutable())->modify('+1 hour');

        $this->token = (new Builder())
            ->setIssuedAt($this->expiryDate->getTimestamp())
            ->getToken();
    }

    /** @test */
    public function it_is_an_invalid_token()
    {
        $this->assertInstanceOf(InvalidToken::class, new IssuedInTheFuture($this->token));
    }

    /** @test */
    public function it_displays_the_iat_date()
    {
        $this->assertContains(
            $this->expiryDate->format(\DateTime::ATOM),
            (new IssuedInTheFuture($this->token))->getMessage()
        );
    }
}
