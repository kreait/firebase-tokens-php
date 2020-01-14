<?php

namespace Firebase\Auth\Token\Tests;

use Firebase\Auth\Token\Domain\KeyStore;
use Firebase\Auth\Token\Exception\ExpiredToken;
use Firebase\Auth\Token\Exception\InvalidSignature;
use Firebase\Auth\Token\Exception\InvalidToken;
use Firebase\Auth\Token\Exception\IssuedInTheFuture;
use Firebase\Auth\Token\Exception\UnknownKey;
use Firebase\Auth\Token\Tests\Util\ArrayKeyStore;
use Firebase\Auth\Token\Verifier;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Token;

class VerifierTest extends TestCase
{
    /**
     * @var Verifier
     */
    private $verifier;

    /**
     * @var KeyStore|\PHPUnit_Framework_MockObject_MockObject
     */
    private $keyStore;

    /**
     * @var Signer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $signer;

    protected function setUp()
    {
        $this->keyStore = new ArrayKeyStore(['valid_key_id' => 'valid_key']);

        $this->signer = $this->createMockSigner();

        $this->verifier = new Verifier('project-id', $this->keyStore, $this->signer);
    }

    /**
     * @param Token $token
     *
     * @dataProvider validTokenStringProvider
     */
    public function testItSucceedsWithAValidToken($token)
    {
        $token = (new Parser())->parse($token);

        $this->signer
            ->expects($this->once())
            ->method('verify')
            ->willReturn(true);

        $this->verifier->verifyIdToken($token);
    }

    /**
     * @param string $token
     *
     * @dataProvider validTokenStringProvider
     */
    public function testItCanHandleTokenString($token)
    {
        $this->signer
            ->expects($this->once())
            ->method('verify')
            ->willReturn(true);

        $this->verifier->verifyIdToken($token);
    }

    public function testItFailsOnAnUnknownKey()
    {
        $token = (new Builder())
            ->setExpiration(time() + 1800)
            ->set('auth_time', time() - 1800)
            ->setIssuedAt(time() - 10)
            ->setIssuer('https://securetoken.google.com/project-id')
            ->setHeader('kid', 'invalid_key_id')
            ->getToken();

        $this->expectException(UnknownKey::class);
        $this->verifier->verifyIdToken($token);
    }

    public function testItVerifiesTheSignatureNoMatterWhat()
    {
        $token = (new Builder())
            ->setExpiration(time() + 1800)
            ->set('auth_time', time() - 1800)
            ->setIssuedAt(time() - 10)
            ->setIssuer('invalid') // Should not trigger
            ->setHeader('kid', 'valid_key_id')
            ->sign($this->createMockSigner(), 'invalid_key')
            ->getToken();

        $this->expectException(InvalidSignature::class);
        $this->verifier->verifyIdToken($token);
    }

    /**
     * @param string $exception
     * @dataProvider invalidTokenProvider
     */
    public function testInvalidTokenResultsInException(Token $token, $exception)
    {
        $this->expectException($exception);

        $this->verifier->verifyIdToken($token);
    }

    public function validTokenStringProvider()
    {
        return [
            'fully_valid' => [(string) (new Builder())
                ->setExpiration(time() + 1800)
                ->set('auth_time', time() - 1800)
                ->setIssuedAt(time() - 10)
                ->setIssuer('https://securetoken.google.com/project-id')
                ->setHeader('kid', 'valid_key_id')
                ->sign($this->createMockSigner(), 'valid_key')
                ->getToken(),
            ],
            'needing_leeway_for_iat' => [
                (string) (new Builder())
                    ->setExpiration(time() + 1800)
                    ->set('auth_time', time() - 1800)
                    ->setIssuedAt(time() + 299)
                    ->setIssuer('https://securetoken.google.com/project-id')
                    ->setHeader('kid', 'valid_key_id')
                    ->sign($this->createMockSigner(), 'valid_key')
                    ->getToken(),
            ],
            'needing_leeway_for_auth_time' => [
                (string) (new Builder())
                    ->setExpiration(time() + 1800)
                    ->set('auth_time', time() + 299)
                    ->setIssuedAt(time() - 10)
                    ->setIssuer('https://securetoken.google.com/project-id')
                    ->setHeader('kid', 'valid_key_id')
                    ->sign($this->createMockSigner(), 'valid_key')
                    ->getToken(),
            ],
        ];
    }

    public function invalidTokenProvider()
    {
        return [
            'no_exp_claim' => [
                (new Builder())->getToken(),
                InvalidToken::class,
            ],
            'expired' => [
                (new Builder())
                    ->setExpiration(time() - 10)
                    ->setHeader('kid', 'valid_key_id')
                    ->sign($this->createMockSigner(), 'valid_key')
                    ->getToken(),
                ExpiredToken::class,
            ],
            'no_auth_time_claim' => [
                (new Builder())
                    ->setExpiration(time() + 1800)
                    ->setHeader('kid', 'valid_key_id')
                    ->sign($this->createMockSigner(), 'valid_key')
                    ->getToken(),
                InvalidToken::class,
            ],
            'not_issued_in_the_past' => [
                (new Builder())
                    ->setExpiration(time() + 1800)
                    ->set('auth_time', time() + 1800)
                    ->setHeader('kid', 'valid_key_id')
                    ->sign($this->createMockSigner(), 'valid_key')
                    ->getToken(),
                InvalidToken::class,
            ],
            'no_iat_claim' => [
                (new Builder())
                    ->setExpiration(time() + 1800)
                    ->set('auth_time', time() - 1800)
                    ->setHeader('kid', 'valid_key_id')
                    ->sign($this->createMockSigner(), 'valid_key')
                    ->getToken(),
                InvalidToken::class,
            ],
            'not_yet_issued' => [
                (new Builder())
                    ->setExpiration(time() + 1800)
                    ->set('auth_time', time() - 1800)
                    ->setIssuedAt(time() + 1800)
                    ->setHeader('kid', 'valid_key_id')
                    ->sign($this->createMockSigner(), 'valid_key')
                    ->getToken(),
                IssuedInTheFuture::class,
            ],
            'no_iss_claim' => [
                (new Builder())
                    ->setExpiration(time() + 1800)
                    ->set('auth_time', time() - 1800)
                    ->setIssuedAt(time() - 10)
                    ->setHeader('kid', 'valid_key_id')
                    ->sign($this->createMockSigner(), 'valid_key')
                    ->getToken(),
                InvalidToken::class,
            ],
            'invalid_issuer' => [
                (new Builder())
                    ->setExpiration(time() + 1800)
                    ->set('auth_time', time() - 1800)
                    ->setIssuedAt(time() - 10)
                    ->setIssuer('invalid_issuer')
                    ->setHeader('kid', 'valid_key_id')
                    ->sign($this->createMockSigner(), 'valid_key')
                    ->getToken(),
                InvalidToken::class,
            ],
            'missing_key_id' => [
                (new Builder())
                    ->setExpiration(time() + 1800)
                    ->set('auth_time', time() - 1800)
                    ->setIssuedAt(time() - 10)
                    ->setIssuer('https://securetoken.google.com/project-id')
                    ->sign($this->createMockSigner(), 'invalid_key')
                    ->getToken(),
                InvalidToken::class,
            ],
            'unsigned' => [
                (new Builder())
                    ->setExpiration(time() + 1800)
                    ->set('auth_time', time() - 1800)
                    ->setIssuedAt(time() - 10)
                    ->setIssuer('https://securetoken.google.com/project-id')
                    ->setHeader('kid', 'valid_key_id')
                    ->getToken(),
                InvalidToken::class,
            ],
            'invalid_signature' => [
                (new Builder())
                    ->setExpiration(time() + 1800)
                    ->set('auth_time', time() - 1800)
                    ->setIssuedAt(time() - 10)
                    ->setIssuer('https://securetoken.google.com/project-id')
                    ->setHeader('kid', 'valid_key_id')
                    ->sign($this->createMockSigner(), 'invalid_key')
                    ->getToken(),
                InvalidSignature::class,
            ],
        ];
    }
}
