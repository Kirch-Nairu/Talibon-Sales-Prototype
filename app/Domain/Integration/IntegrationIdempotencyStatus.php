<?php

namespace App\Domain\Integration;

enum IntegrationIdempotencyStatus: string
{
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
}
