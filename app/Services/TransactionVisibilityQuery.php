<?php

namespace App\Services;

use App\Domain\Workflow\Authorization\TransactionCapabilities;
use App\Models\User;
use App\Models\WorkflowTransaction;
use Illuminate\Database\Eloquent\Builder;

final class TransactionVisibilityQuery
{
    public function __construct(
        private readonly TransactionCapabilities $capabilities,
    ) {
    }

    public function scope(User $actor): Builder
    {
        $actor->loadMissing('employee.department');
        $query = WorkflowTransaction::query();

        if ($this->canViewAll($actor)) {
            return $query;
        }

        $departmentId = $actor->employee?->department_id;
        if (! $departmentId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $visible) use ($departmentId): void {
            $visible->where('current_department_id', $departmentId)
                ->orWhere('origin_department_id', $departmentId);
        });
    }

    public function canViewAll(User $actor): bool
    {
        $roles = $actor->role ? [$actor->role] : [];

        return $this->capabilities
            ->resolve($roles)
            ->allows(TransactionCapabilities::VIEW_ALL);
    }
}
