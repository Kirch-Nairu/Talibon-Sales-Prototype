<?php

namespace App\Domain\Workflow;

final readonly class WorkflowTransitionRule
{
    public const DESTINATION_CURRENT = 'current';

    public const DESTINATION_TARGET = 'target';

    public const DESTINATION_ORIGIN = 'origin';

    public const DESTINATION_OFFICE = 'office';

    public function __construct(
        public string $action,
        public ?string $status,
        public string $destination,
        public ?string $officeCode = null,
        public bool $requiresAssignment = false,
        public bool $clearAssignment = false,
        public bool $refreshReceivedAt = false,
        public bool $completes = false,
    ) {
    }

    public static function fromArray(string $action, array $rule): self
    {
        return new self(
            action: $action,
            status: $rule['status'] ?? null,
            destination: $rule['destination'] ?? self::DESTINATION_CURRENT,
            officeCode: $rule['office_code'] ?? null,
            requiresAssignment: (bool) ($rule['requires_assignment'] ?? false),
            clearAssignment: (bool) ($rule['clear_assignment'] ?? false),
            refreshReceivedAt: (bool) ($rule['refresh_received_at'] ?? false),
            completes: (bool) ($rule['completes'] ?? false),
        );
    }
}
