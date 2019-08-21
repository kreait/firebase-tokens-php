<?php

namespace Firebase\Auth\Token\Tests\Exception;

use DateTimeImmutable;
use DateTimeInterface;
use Firebase\Auth\Token\Exception\ExpiredToken;
use Firebase\Auth\Token\Exception\InvalidToken;
use Firebase\Auth\Token\Tests\TestCase;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Token;

class ExpiredTokenTest extends TestCase
{
    /**
     * @var Token
     */
    private $token;

    /**
     * @var DateTimeInterface
     */
    private $expiryDate;

    protected function setUp()
    {
        $this->expiryDate = new DateTimeImmutable();

        $this->token = (new Builder())
            ->set('exp', $this->expiryDate->getTimestamp())
            ->getToken();
    }

    /** @test */
    public function it_is_an_invalid_token()
    {
        $this->assertInstanceOf(InvalidToken::class, new ExpiredToken($this->token));
    }

    /** @test */
    public function it_displays_the_expiry_date()
    {
        $this->assertContains(
            $this->expiryDate->format(\DateTime::ATOM),
            (new ExpiredToken($this->token))->getMessage()
        );
    }
}
