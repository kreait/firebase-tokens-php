<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Action\CreateCustomToken;

use Kreait\Firebase\JWT\Action\CreateCustomToken;
use Kreait\Firebase\JWT\Action\CreateCustomToken\Error\CustomTokenCreationFailed;
use Kreait\Firebase\JWT\Contract\Token;

interface Handler
{
    /**
     * @throws CustomTokenCreationFailed
     */
    public function handle(CreateCustomToken $action): Token;
}
