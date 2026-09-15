<?php

namespace App\Services;

use App\Domain\Correspondence\CorrespondenceClassification;
use App\Domain\Correspondence\CorrespondenceLifecycleState;
use App\Models\CorrespondenceRecord;
use App\Models\IntegrationClient;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CorrespondenceAccessDecider
{
    private const REGISTER_ROLES = [
        'department_head',
        'department_staff',
        'mayor_approver',
        'mayor_staff',
        'hr_officer',
        'legislative_staff',
    ];

    private const CLASSIFY_ROLES = [
        'department_head',
        'mayor_approver',
        'hr_officer',
        'legislative_staff',
    ];

    private const ROUTE_ROLES = [
        'department_head',
        'mayor_approver',
        'hr_officer',
        'legislative_staff',
    ];

    private const ACTION_ROLES = [
        'department_head',
        'department_staff',
        'mayor_approver',
        'mayor_staff',
        'hr_officer',
        'legislative_staff',
    ];

    private const CONFIDENTIAL_VIEW_ROLES = [
        'department_head',
        'mayor_approver',
        'hr_officer',
        'legislative_staff',
    ];

    private const RESTRICTED_VIEW_ROLES = [
        'department_head',
        'mayor_approver',
    ];

    public function canRegister(User $actor, CorrespondenceRecord $record): bool
    {
        return $record->lifecycle_state === CorrespondenceLifecycleState::Received
            && $this->hasActiveMunicipalIdentity($actor)
            && in_array($actor->role, self::REGISTER_ROLES, true);
    }

    public function canClassify(User $actor, CorrespondenceRecord $record): bool
    {
        return $record->lifecycle_state === CorrespondenceLifecycleState::Registered
            && $this->hasOfficeOrAssignmentContext($actor, $record)
            && in_array($actor->role, self::CLASSIFY_ROLES, true);
    }

    public function canRoute(User $actor, CorrespondenceRecord $record): bool
    {
        return $record->lifecycle_state === CorrespondenceLifecycleState::Classified
            && $record->workflow_transaction_id === null
            && in_array($actor->role, self::ROUTE_ROLES, true)
            && $this->canView($actor, $record);
    }

    public function canAct(User $actor, CorrespondenceRecord $record): bool
    {
        return $record->lifecycle_state === CorrespondenceLifecycleState::Routed
            && $record->workflow_transaction_id !== null
            && in_array($actor->role, self::ACTION_ROLES, true)
            && $this->canView($actor, $record);
    }

    public function canView(User $actor, CorrespondenceRecord $record): bool
    {
        if (! $this->hasOfficeOrAssignmentContext($actor, $record)) {
            return false;
        }

        $classification = $record->classification;
        if (! $classification instanceof CorrespondenceClassification
            || in_array($classification, [
                CorrespondenceClassification::Public,
                CorrespondenceClassification::Internal,
            ], true)) {
            return in_array($actor->role, self::REGISTER_ROLES, true);
        }

        if ($classification === CorrespondenceClassification::Confidential) {
            return in_array($actor->role, self::CONFIDENTIAL_VIEW_ROLES, true);
        }

        return in_array($actor->role, self::RESTRICTED_VIEW_ROLES, true);
    }

    public function canViewInWorkspace(User $actor, CorrespondenceRecord $record): bool
    {
        return $this->canRegister($actor, $record) || $this->canView($actor, $record);
    }

    /**
     * Apply the same office, assignment and classification rules used by canView(),
     * plus the unregistered RECEIVE intake boundary needed by human registrars.
     */
    public function scopeVisibleTo(Builder $query, User $actor): Builder
    {
        if (! $this->hasActiveMunicipalIdentity($actor)) {
            return $query->whereRaw('1 = 0');
        }

        $canRegister = in_array($actor->role, self::REGISTER_ROLES, true);
        $classifications = $this->visibleClassificationValues($actor);

        if (! $canRegister && $classifications === []) {
            return $query->whereRaw('1 = 0');
        }

        $employeeId = (int) $actor->employee->id;
        $departmentId = (int) $actor->employee->department_id;

        return $query->where(function (Builder $visible) use (
            $canRegister,
            $classifications,
            $employeeId,
            $departmentId,
        ): void {
            if ($canRegister) {
                $visible->where(function (Builder $intake): void {
                    $intake->where('lifecycle_state', CorrespondenceLifecycleState::Received->value)
                        ->whereNull('classification')
                        ->whereNull('receiving_department_id')
                        ->whereNull('workflow_transaction_id');
                });
            }

            $method = $canRegister ? 'orWhere' : 'where';
            $visible->{$method}(function (Builder $scoped) use (
                $canRegister,
                $classifications,
                $employeeId,
                $departmentId,
            ): void {
                $scoped->where(function (Builder $context) use ($employeeId, $departmentId): void {
                    $context->whereHas('workflowTransaction', function (Builder $workflow) use ($employeeId, $departmentId): void {
                        $workflow->where(function (Builder $ownership) use ($employeeId, $departmentId): void {
                            $ownership->where('assigned_employee_id', $employeeId)
                                ->orWhere('current_department_id', $departmentId);
                        });
                    })->orWhere(function (Builder $unlinked) use ($departmentId): void {
                        $unlinked->whereNull('workflow_transaction_id')
                            ->where('receiving_department_id', $departmentId);
                    });
                });

                $scoped->where(function (Builder $classification) use ($canRegister, $classifications): void {
                    if ($canRegister) {
                        $classification->whereNull('classification');
                    }

                    if ($classifications !== []) {
                        $method = $canRegister ? 'orWhereIn' : 'whereIn';
                        $classification->{$method}('classification', $classifications);
                    }
                });
            });
        });
    }

    /** @return array<int, string> */
    public function visibleClassificationValues(User $actor): array
    {
        if (! $this->hasActiveMunicipalIdentity($actor)) {
            return [];
        }

        if (in_array($actor->role, self::RESTRICTED_VIEW_ROLES, true)) {
            return array_map(
                fn (CorrespondenceClassification $classification): string => $classification->value,
                CorrespondenceClassification::cases(),
            );
        }

        if (in_array($actor->role, self::CONFIDENTIAL_VIEW_ROLES, true)) {
            return [
                CorrespondenceClassification::Public->value,
                CorrespondenceClassification::Internal->value,
                CorrespondenceClassification::Confidential->value,
            ];
        }

        if (in_array($actor->role, self::REGISTER_ROLES, true)) {
            return [
                CorrespondenceClassification::Public->value,
                CorrespondenceClassification::Internal->value,
            ];
        }

        return [];
    }

    /** @return array<int, string> */
    public function actionLifecycleValues(User $actor): array
    {
        if (! $this->hasActiveMunicipalIdentity($actor)) {
            return [];
        }

        $states = [];
        if (in_array($actor->role, self::REGISTER_ROLES, true)) {
            $states[] = CorrespondenceLifecycleState::Received->value;
        }
        if (in_array($actor->role, self::CLASSIFY_ROLES, true)) {
            $states[] = CorrespondenceLifecycleState::Registered->value;
        }
        if (in_array($actor->role, self::ROUTE_ROLES, true)) {
            $states[] = CorrespondenceLifecycleState::Classified->value;
        }
        if (in_array($actor->role, self::ACTION_ROLES, true)) {
            $states[] = CorrespondenceLifecycleState::Routed->value;
        }

        return $states;
    }

    public function scopeActionRequired(Builder $query, User $actor): Builder
    {
        $states = $this->actionLifecycleValues($actor);

        return $states === []
            ? $query->whereRaw('1 = 0')
            : $query->whereIn('lifecycle_state', $states);
    }

    public function requiresAction(User $actor, CorrespondenceRecord $record): bool
    {
        return $this->canRegister($actor, $record)
            || $this->canClassify($actor, $record)
            || $this->canRoute($actor, $record)
            || $this->canAct($actor, $record);
    }

    public function canIntegrationReadStatus(
        IntegrationClient $client,
        CorrespondenceRecord $record,
    ): bool {
        return (int) $record->receiving_integration_client_id === (int) $client->id;
    }

    private function hasOfficeOrAssignmentContext(User $actor, CorrespondenceRecord $record): bool
    {
        if (! $this->hasActiveMunicipalIdentity($actor)) {
            return false;
        }

        $record->loadMissing('workflowTransaction');
        $workflow = $record->workflowTransaction;
        if ($workflow !== null && $workflow->assigned_employee_id !== null) {
            if ((int) $workflow->assigned_employee_id === (int) $actor->employee->id) {
                return true;
            }
        }

        $officeId = $workflow?->current_department_id ?? $record->receiving_department_id;

        return $officeId !== null
            && (int) $actor->employee->department_id === (int) $officeId;
    }

    private function hasActiveMunicipalIdentity(User $actor): bool
    {
        $actor->loadMissing('employee');

        return $actor->is_active
            && $actor->employee !== null
            && $actor->employee->employment_status === 'active'
            && $actor->employee->department_id !== null;
    }
}
