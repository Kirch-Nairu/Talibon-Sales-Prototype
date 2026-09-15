<?php

namespace App\Domain\Integration;

use App\Models\IntegrationClient;
use RuntimeException;

final class IntegrationAuthenticationException extends RuntimeException
{
    public function __construct(
        public readonly IntegrationAuthenticationFailure $reason,
        public readonly ?IntegrationClient $client = null,
    ) {
        parent::__construct('Integration authentication failed.');
    }
}
