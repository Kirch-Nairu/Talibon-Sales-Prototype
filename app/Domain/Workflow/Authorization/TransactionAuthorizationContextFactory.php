<?php

namespace App\Domain\Workflow\Authorization;

use App\Domain\Authorization\AuthorizationContext;
use App\Models\User;
use App\Models\WorkflowTransaction;

final readonly class TransactionAuthorizationContextFactory
{
    public function __construct(private TransactionCapabilities $capabilities)
    {
    }

    public function make(
        User $user,
        string $requestedAction,
        ?WorkflowTransaction $transaction = null,
    ): AuthorizationContext {
        $user->loadMissing('employee.department');
        $transaction?->loadMissing('currentDepartment');

        $employee = $user->employee;
        $roles = $user->role ? [$user->role] : [];

        return new AuthorizationContext(
            actorUserId: (int) $user->id,
            actorEmployeeId: $employee?->id,
            actorActive: (bool) $user->is_active,
            actorRoles: $roles,
            capabilities: $this->capabilities->resolve($roles),
            actorOfficeId: $employee?->department_id,
            actorOfficeCode: $employee?->department?->code,
            delegatedOfficeIds: [],
            resourceOfficeId: $transaction?->current_department_id,
            resourceOfficeCode: $transaction?->currentDepartment?->code,
            resourceOriginOfficeId: $transaction?->origin_department_id,
            resourceAssignedEmployeeId: $transaction?->assigned_employee_id,
            classification: $this->classification($transaction),
            workflowState: $transaction?->status,
            requestedAction: $requestedAction,
        );
    }

    private function classification(?WorkflowTransaction $transaction): ?string
    {
        if (! $transaction) {
            return null;
        }

        $classification = $transaction->getAttribute('classification');

        return is_string($classification) && $classification !== ''
            ? $classification
            : null;
    }
}
