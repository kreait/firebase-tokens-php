<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Kreait\Clock;
use Kreait\Firebase\JWT\Action\FetchGooglePublicKeys;
use Kreait\Firebase\JWT\Contract\Keys;
use Kreait\Firebase\JWT\Error\DiscoveryFailed;

final class WithHandlerDiscovery implements Handler
{
    /** @var Handler */
    private $handler;

    public function __construct(Clock $clock)
    {
        $this->handler = self::discover($clock);
    }

    public function handle(FetchGooglePublicKeys $action): Keys
    {
        return $this->handler->handle($action);
    }

    private static function discover(Clock $clock): Handler
    {
        if (\filter_var(\ini_get('allow_url_fopen'), \FILTER_VALIDATE_BOOLEAN)) {
            return new FetchGooglePublicKeys\WithStreamContext($clock);
        }

        if (\interface_exists(ClientInterface::class)) {
            return new FetchGooglePublicKeys\WithGuzzle6(new Client(['http_errors' => false]), $clock);
        }

        throw DiscoveryFailed::noHttpLibraryFound();
    }
}
