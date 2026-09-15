<?php

namespace App\Domain\Workflow;

use App\Models\Department;
use App\Models\WorkflowTransaction;
use Illuminate\Validation\ValidationException;
use LogicException;

class WorkflowDestinationResolver
{
    public function resolve(
        WorkflowTransitionRule $rule,
        WorkflowTransaction $transaction,
        ?int $targetDepartmentId,
    ): int {
        return match ($rule->destination) {
            WorkflowTransitionRule::DESTINATION_CURRENT => (int) $transaction->current_department_id,
            WorkflowTransitionRule::DESTINATION_ORIGIN => (int) $transaction->origin_department_id,
            WorkflowTransitionRule::DESTINATION_TARGET => $this->targetDepartment(
                (int) $transaction->current_department_id,
                $targetDepartmentId,
            ),
            WorkflowTransitionRule::DESTINATION_OFFICE => $this->officeByCode($rule->officeCode),
            default => throw new LogicException("Unknown workflow destination mode [{$rule->destination}]."),
        };
    }

    private function targetDepartment(int $currentDepartmentId, ?int $targetDepartmentId): int
    {
        if (! $targetDepartmentId || $targetDepartmentId === $currentDepartmentId) {
            throw ValidationException::withMessages([
                'target_department_id' => 'Choose a different receiving office.',
            ]);
        }

        if (! Department::query()->activeRoutable()->whereKey($targetDepartmentId)->exists()) {
            throw ValidationException::withMessages([
                'target_department_id' => 'Choose an active routable receiving office.',
            ]);
        }

        return $targetDepartmentId;
    }

    private function officeByCode(?string $officeCode): int
    {
        if (! $officeCode) {
            throw new LogicException('Workflow office destination requires an office code.');
        }

        return (int) Department::query()
            ->activeRoutable()
            ->where('code', $officeCode)
            ->firstOrFail()
            ->id;
    }
}
