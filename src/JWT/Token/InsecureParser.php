<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Token;

use DateTimeImmutable;
use Lcobucci\JWT\Decoder;
use Lcobucci\JWT\Parser as ParserInterface;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\Token\Signature;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;

use function array_key_exists;
use function is_array;

/**
 * This is an almost 1:1 copy of the parser in `lcobucci/jwt`, with only the signature verification
 * removed; hence the name `InsecureParser`.
 *
 * @internal
 */
final class InsecureParser implements ParserInterface
{
    private const MICROSECOND_PRECISION = 6;

    public function __construct(private readonly Decoder $decoder)
    {
    }

    /**
     * @param non-empty-string $jwt
     */
    public function parse(string $jwt): Token
    {
        [$encodedHeaders, $encodedClaims] = $this->splitJwt($jwt);

        if ($encodedHeaders === '') {
            throw new InvalidTokenStructure('The JWT string is missing the Header part');
        }

        if ($encodedClaims === '') {
            throw new InvalidTokenStructure('The JWT string is missing the Claim part');
        }

        $header = $this->parseHeader($encodedHeaders);

        return new Plain(
            new DataSet($header, $encodedHeaders),
            new DataSet($this->parseClaims($encodedClaims), $encodedClaims),
            new Signature('none', 'none'),
        );
    }

    /**
     * Splits the JWT string into an array.
     *
     * @param non-empty-string $jwt
     *
     * @throws InvalidTokenStructure when JWT doesn't have all parts
     *
     * @return string[]
     */
    private function splitJwt(string $jwt): array
    {
        return explode('.', $jwt);
    }

    /**
     * Parses the header from a string.
     *
     * @param non-empty-string $data
     *
     * @throws InvalidTokenStructure when parsed content isn't an array
     * @throws UnsupportedHeaderFound when an invalid header is informed
     *
     * @return array<non-empty-string, mixed>
     */
    private function parseHeader(string $data): array
    {
        $header = $this->decoder->jsonDecode($this->decoder->base64UrlDecode($data));

        if (!is_array($header)) {
            throw InvalidTokenStructure::arrayExpected('headers');
        }

        $this->guardAgainstEmptyStringKeys($header, 'headers');

        if (array_key_exists('enc', $header)) {
            throw UnsupportedHeaderFound::encryption();
        }

        if (!array_key_exists('typ', $header)) {
            $header['typ'] = 'JWT';
        }

        return $header;
    }

    /**
     * Parses the claim set from a string.
     *
     * @param non-empty-string $data
     *
     * @throws InvalidTokenStructure when parsed content isn't an array or contains non-parseable dates
     *
     * @return array<non-empty-string, mixed>
     */
    private function parseClaims(string $data): array
    {
        $claims = $this->decoder->jsonDecode($this->decoder->base64UrlDecode($data));

        if (!is_array($claims)) {
            throw InvalidTokenStructure::arrayExpected('claims');
        }

        $this->guardAgainstEmptyStringKeys($claims, 'claims');

        if (array_key_exists(RegisteredClaims::AUDIENCE, $claims)) {
            $claims[RegisteredClaims::AUDIENCE] = (array) $claims[RegisteredClaims::AUDIENCE];
        }

        foreach (RegisteredClaims::DATE_CLAIMS as $claim) {
            if (!array_key_exists($claim, $claims)) {
                continue;
            }

            if (!is_scalar($claims[$claim])) {
                continue;
            }

            if (is_bool($claims[$claim])) {
                continue;
            }

            $claims[$claim] = $this->convertDate($claims[$claim]);
        }

        return $claims;
    }

    /**
     * @param array<array-key, mixed> $array
     * @param non-empty-string $part
     *
     * @phpstan-assert array<non-empty-string, mixed> $array
     */
    private function guardAgainstEmptyStringKeys(array $array, string $part): void
    {
        foreach ($array as $key => $value) {
            if ($key === '') {
                throw InvalidTokenStructure::arrayExpected($part);
            }
        }
    }

    /** @throws InvalidTokenStructure */
    private function convertDate(int|float|string $timestamp): DateTimeImmutable
    {
        if (!is_numeric($timestamp)) {
            throw InvalidTokenStructure::dateIsNotParseable($timestamp);
        }

        $normalizedTimestamp = number_format((float) $timestamp, self::MICROSECOND_PRECISION, '.', '');

        $date = DateTimeImmutable::createFromFormat('U.u', $normalizedTimestamp);

        if ($date === false) {
            throw InvalidTokenStructure::dateIsNotParseable($normalizedTimestamp);
        }

        return $date;
    }
}
