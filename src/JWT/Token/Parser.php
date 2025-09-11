<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Token;

use Kreait\Firebase\JWT\Util;
use Lcobucci\JWT\Decoder;
use Lcobucci\JWT\Parser as ParserInterface;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Parser as SecureParser;

final class Parser implements ParserInterface
{
    private ParserInterface $parser;

    public function __construct(public readonly Decoder $decoder)
    {
        if (Util::authEmulatorHost() !== '') {
            $this->parser = new InsecureParser($decoder);
        } else {
            $this->parser = new SecureParser($decoder);
        }
    }

    public function parse(string $jwt): Token
    {
        return $this->parser->parse($jwt);
    }
}
