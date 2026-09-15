<?php

namespace App\Domain\Integration;

final class IntegrationRequestAttributes
{
    public const CORRELATION_ID = 'integration.correlation_id';
    public const CLIENT_CONTEXT = 'integration.client_context';

    private function __construct()
    {
    }
}
