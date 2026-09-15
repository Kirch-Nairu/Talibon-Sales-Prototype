<?php

namespace App\Domain\Workflow;

final readonly class ResolvedWorkflowTransition
{
    public function __construct(
        public string $previousStatus,
        public string $newStatus,
        public int $fromDepartmentId,
        public int $toDepartmentId,
        public ?int $assignmentEmployeeId,
        public ?string $remarks,
    ) {
    }
}
