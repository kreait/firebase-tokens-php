<?php

namespace Firebase\Auth\Token\Tests;

use Firebase\Auth\Token\Domain\KeyStore;
use Firebase\Auth\Token\Exception\ExpiredToken;
use Firebase\Auth\Token\Exception\InvalidSignature;
use Firebase\Auth\Token\Exception\InvalidToken;
use Firebase\Auth\Token\Exception\IssuedInTheFuture;
use Firebase\Auth\Token\Exception\UnknownKey;
use Firebase\Auth\Token\Tests\Util\ArrayKeyStore;
use Firebase\Auth\Token\Tests\Util\TestHelperClock;
use Firebase\Auth\Token\Verifier;
use Kreait\Clock\SystemClock;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key\InMemory as InMemoryKey;
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
        $clock = new TestHelperClock(new SystemClock());

        $token = (new Builder())
            ->expiresAt($clock->minutesLater(30))
            ->withClaim('auth_time', $clock->minutesEarlier(30)->getTimestamp())
            ->issuedAt($clock->secondsEarlier(10))
            ->issuedBy('https://securetoken.google.com/project-id')
            ->withHeader('kid', 'invalid_key_id')
            ->getToken($this->signer, InMemoryKey::plainText('valid_key'));

        $this->expectException(UnknownKey::class);
        $this->verifier->verifyIdToken($token);
    }

    public function testItVerifiesTheSignatureNoMatterWhat()
    {
        $clock = new TestHelperClock(new SystemClock());

        $token = (new Builder())
            ->expiresAt($clock->minutesLater(30))
            ->withClaim('auth_time', $clock->minutesEarlier(30)->getTimestamp())
            ->issuedAt($clock->secondsEarlier(10))
            ->issuedBy('invalid') // Should not trigger
            ->withHeader('kid', 'valid_key_id')
            ->getToken($this->signer, InMemoryKey::plainText('invalid_key'));

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
        $clock = new TestHelperClock(new SystemClock());
        $signer = $this->createMockSigner();

        return [
            'fully_valid' => [
                (string) (new Builder())
                    ->expiresAt($clock->minutesLater(30))
                    ->withClaim('auth_time', $clock->minutesEarlier(30)->getTimestamp())
                    ->issuedAt($clock->secondsEarlier(10))
                    ->issuedBy('https://securetoken.google.com/project-id')
                    ->withHeader('kid', 'valid_key_id')
                    ->getToken($signer, InMemoryKey::plainText('valid_key')),
            ],
            'needing_leeway_for_iat' => [
                (string) (new Builder())
                    ->expiresAt($clock->minutesLater(30))
                    ->withClaim('auth_time', $clock->minutesEarlier(30)->getTimestamp())
                    ->issuedAt($clock->secondsLater(299))
                    ->issuedBy('https://securetoken.google.com/project-id')
                    ->withHeader('kid', 'valid_key_id')
                    ->getToken($signer, InMemoryKey::plainText('valid_key')),
            ],
            'needing_leeway_for_auth_time' => [
                (string) (new Builder())
                    ->expiresAt($clock->minutesLater(30))
                    ->withClaim('auth_time', $clock->minutesEarlier(30)->getTimestamp())
                    ->issuedAt($clock->secondsEarlier(10))
                    ->issuedBy('https://securetoken.google.com/project-id')
                    ->withHeader('kid', 'valid_key_id')
                    ->getToken($signer, InMemoryKey::plainText('valid_key')),
            ],
        ];
    }

    public function invalidTokenProvider()
    {
        $clock = new TestHelperClock(new SystemClock());
        $signer = $this->createMockSigner();
        $validKey = InMemoryKey::plainText('valid_key');
        $invalidKey = InMemoryKey::plainText('invalid_key');

        return [
            'no_exp_claim' => [
                (new Builder())->getToken($signer, $validKey),
                InvalidToken::class,
            ],
            'expired' => [
                (new Builder())
                    ->expiresAt($clock->secondsEarlier(10))
                    ->withHeader('kid', 'valid_key_id')
                    ->getToken($signer, $validKey),
                ExpiredToken::class,
            ],
            'no_auth_time_claim' => [
                (new Builder())
                    ->expiresAt($clock->minutesLater(30))
                    ->withHeader('kid', 'valid_key_id')
                    ->getToken($signer, $validKey),
                InvalidToken::class,
            ],
            'not_issued_in_the_past' => [
                (new Builder())
                    ->expiresAt($clock->minutesLater(30))
                    ->withClaim('auth_time', $clock->minutesLater(30)->getTimestamp())
                    ->withHeader('kid', 'valid_key_id')
                    ->getToken($signer, $validKey),
                InvalidToken::class,
            ],
            'no_iat_claim' => [
                (new Builder())
                    ->expiresAt($clock->minutesLater(30))
                    ->withClaim('auth_time', $clock->minutesEarlier(30)->getTimestamp())
                    ->withHeader('kid', 'valid_key_id')
                    ->getToken($signer, $validKey),
                InvalidToken::class,
            ],
            'not_yet_issued' => [
                (new Builder())
                    ->expiresAt($clock->minutesLater(30))
                    ->withClaim('auth_time', $clock->minutesEarlier(30)->getTimestamp())
                    ->issuedAt($clock->minutesLater(30))
                    ->withHeader('kid', 'valid_key_id')
                    ->getToken($signer, $validKey),
                IssuedInTheFuture::class,
            ],
            'no_iss_claim' => [
                (new Builder())
                    ->expiresAt($clock->minutesLater(30))
                    ->withClaim('auth_time', $clock->minutesEarlier(30)->getTimestamp())
                    ->issuedAt($clock->secondsEarlier(10))
                    ->withHeader('kid', 'valid_key_id')
                    ->getToken($signer, $validKey),
                InvalidToken::class,
            ],
            'invalid_issuer' => [
                (new Builder())
                    ->expiresAt($clock->minutesLater(30))
                    ->withClaim('auth_time', $clock->minutesEarlier(30)->getTimestamp())
                    ->issuedAt($clock->secondsEarlier(10))
                    ->issuedBy('invalid_issuer')
                    ->withHeader('kid', 'valid_key_id')
                    ->getToken($signer, $validKey),
                InvalidToken::class,
            ],
            'missing_key_id' => [
                (new Builder())
                    ->expiresAt($clock->minutesLater(30))
                    ->withClaim('auth_time', $clock->minutesEarlier(30)->getTimestamp())
                    ->issuedAt($clock->secondsEarlier(10))
                    ->issuedBy('https://securetoken.google.com/project-id')
                    ->getToken($signer, $invalidKey),
                InvalidToken::class,
            ],
            'invalid_signature' => [
                (new Builder())
                    ->expiresAt($clock->minutesLater(30))
                    ->withClaim('auth_time', $clock->minutesEarlier(30)->getTimestamp())
                    ->issuedAt($clock->secondsEarlier(10))
                    ->issuedBy('https://securetoken.google.com/project-id')
                    ->withHeader('kid', 'valid_key_id')
                    ->getToken($signer, $invalidKey),
                InvalidSignature::class,
            ],
        ];
    }
}
