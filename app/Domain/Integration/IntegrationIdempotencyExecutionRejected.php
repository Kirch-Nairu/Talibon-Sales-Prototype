<?php

namespace App\Domain\Integration;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

final class IntegrationIdempotencyExecutionRejected extends RuntimeException
{
    public function __construct(public readonly Response $response)
    {
        parent::__construct('Idempotent integration execution returned a non-success response.');
    }
}
