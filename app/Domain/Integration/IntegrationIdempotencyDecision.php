<?php

namespace App\Domain\Integration;

use App\Models\IntegrationIdempotencyRecord;

final readonly class IntegrationIdempotencyDecision
{
    public function __construct(
        public IntegrationIdempotencyDecisionType $type,
        public IntegrationIdempotencyRecord $record,
        public ?string $processingToken = null,
    ) {
    }
}
