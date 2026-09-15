<?php

namespace App\Domain\Integration;

enum IntegrationIdempotencyDecisionType: string
{
    case Execute = 'execute';
    case Replay = 'replay';
    case Conflict = 'conflict';
    case InProgress = 'in_progress';
}
