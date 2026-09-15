<?php

namespace App\Services\Reports;

use App\Models\User;

final class CorePortalReportAccess
{
    public function allows(?User $actor): bool
    {
        if (! $actor?->is_active) {
            return false;
        }

        $actor->loadMissing('employee.department');

        return $actor->employee?->employment_status === 'active'
            && $actor->employee->department_id !== null
            && $actor->employee->department?->is_active === true;
    }
}
