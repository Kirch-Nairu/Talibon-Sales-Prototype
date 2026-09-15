<?php

namespace App\Services;

use App\Models\User;
use App\Services\Reports\CorePortalReportAccess;

final class PortalNavigationAccess
{
    public function __construct(
        private readonly CorePortalReportAccess $reports,
        private readonly SystemAdministrationAccess $systemAdministration,
        private readonly TravelOrderAccess $travelOrders,
    ) {
    }

    /** @return array<string, bool> */
    public function for(User $user): array
    {
        $user->loadMissing('employee.department');

        if (! $user->is_active) {
            return self::none();
        }

        $hasOperationalIdentity = $user->employee?->department?->is_active === true;
        $mayorOffice = $user->isRole('system_admin')
            || ($user->isRole('mayor_approver', 'mayor_staff')
                && $user->employee?->department?->code === 'MAYOR');

        return [
            'systemAdministration' => $this->systemAdministration->allowed($user),
            'dashboard' => $hasOperationalIdentity,
            'transactions' => $hasOperationalIdentity,
            'correspondence' => $hasOperationalIdentity,
            'records' => $hasOperationalIdentity,
            'travelOrders' => $this->travelOrders->canAccessIndex($user),
            'reports' => $this->reports->allows($user),
            'mayorOffice' => $mayorOffice,
            'memoranda' => $hasOperationalIdentity,
            'departments' => $hasOperationalIdentity,
            'audit' => $user->isRole('system_admin', 'mayor_approver'),
        ];
    }

    /** @return array<string, bool> */
    public static function none(): array
    {
        return [
            'systemAdministration' => false,
            'dashboard' => false,
            'transactions' => false,
            'correspondence' => false,
            'records' => false,
            'travelOrders' => false,
            'reports' => false,
            'mayorOffice' => false,
            'memoranda' => false,
            'departments' => false,
            'audit' => false,
        ];
    }
}
