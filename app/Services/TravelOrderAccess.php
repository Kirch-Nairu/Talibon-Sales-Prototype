<?php

namespace App\Services;

use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class TravelOrderAccess
{
    public function canAccessIndex(User $actor): bool
    {
        $actor->loadMissing('employee.department');

        return $actor->is_active
            && $actor->employee?->department?->is_active === true;
    }

    public function canRecordApproved(User $actor): bool
    {
        $actor->loadMissing('employee.department');

        return $actor->is_active
            && $actor->isRole('mayor_approver')
            && $actor->employee?->department?->is_active === true
            && $actor->employee?->department?->code === 'MAYOR';
    }

    public function canUpdateState(User $actor): bool
    {
        return $this->canRecordApproved($actor);
    }

    public function canView(User $actor, TravelOrder $travelOrder): bool
    {
        return $this->scopeVisibleTo(
            TravelOrder::query()->whereKey($travelOrder->getKey()),
            $actor,
        )->exists();
    }

    public function scopeVisibleTo(Builder $query, User $actor): Builder
    {
        $actor->loadMissing('employee.department');
        $employee = $actor->employee;
        $department = $employee?->department;

        if (! $actor->is_active || ! $employee || ! $department || ! $department->is_active) {
            return $query->whereRaw('1 = 0');
        }

        if ($this->hasMunicipalReadAuthority($actor)) {
            return $query;
        }

        if ($actor->isRole('department_head')) {
            return $query->where(function (Builder $visible) use ($employee): void {
                $visible->where('department_id', $employee->department_id)
                    ->orWhereHas('issuedTo', function (Builder $issued) use ($employee): void {
                        $issued->where('employees.department_id', $employee->department_id);
                    });
            });
        }

        return $query->whereHas('issuedTo', function (Builder $issued) use ($employee): void {
            $issued->where('employees.id', $employee->id);
        });
    }

    private function hasMunicipalReadAuthority(User $actor): bool
    {
        return $actor->isRole('mayor_approver', 'mayor_staff')
            && $actor->employee?->department?->code === 'MAYOR';
    }
}
