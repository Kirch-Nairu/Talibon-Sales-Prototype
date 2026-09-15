<?php

namespace App\Services;

use App\Domain\Workflow\Authorization\TransactionCapabilities;
use App\Models\User;

final class DashboardExperienceResolver
{
    public function __construct(
        private readonly TransactionCapabilities $transactionCapabilities,
    ) {}

    /** @return array<string, mixed> */
    public function resolve(User $actor): array
    {
        $actor->loadMissing('employee.department');
        $department = $actor->employee?->department;
        abort_unless($department?->is_active, 403);

        $capabilities = $this->transactionCapabilities->resolve(
            $actor->role ? [$actor->role] : [],
        );
        $profile = $this->profile($actor, $capabilities->allows(TransactionCapabilities::VIEW_ALL));

        return [
            'key' => $profile,
            'label' => $this->label($profile),
            'department' => [
                'id' => (int) $department->id,
                'code' => $department->code,
                'name' => $department->name,
                'shortName' => $department->short_name,
            ],
            'scopes' => [
                'personal' => $profile !== 'system_administration',
                'office' => $profile === 'department_head',
                'municipal' => in_array($profile, ['executive_oversight', 'system_administration'], true),
                'system' => $profile === 'system_administration',
            ],
            'capabilities' => [
                'openOfficeWorkspace' => $profile === 'department_head',
                'viewMunicipalAggregates' => in_array($profile, ['executive_oversight', 'system_administration'], true),
                'openSystemAdministration' => $profile === 'system_administration',
            ],
            'quickActions' => $this->quickActions($profile),
        ];
    }

    private function profile(User $actor, bool $canViewAll): string
    {
        if ($actor->isRole('system_admin')) {
            return 'system_administration';
        }

        if ($canViewAll
            && $actor->isRole('mayor_approver', 'mayor_staff')
            && $actor->employee?->department?->code === 'MAYOR') {
            return 'executive_oversight';
        }

        if ($actor->isRole('department_head')) {
            return 'department_head';
        }

        return 'employee';
    }

    private function label(string $profile): string
    {
        return match ($profile) {
            'system_administration' => 'System administration',
            'executive_oversight' => 'Executive oversight',
            'department_head' => 'Office leadership',
            default => 'Personal operations',
        };
    }

    /** @return array<int, array{label:string,description:string,url:string}> */
    private function quickActions(string $profile): array
    {
        return match ($profile) {
            'system_administration' => [
                ['label' => 'System Administration', 'description' => 'Review identities, access, MFA posture, and office digital identity.', 'url' => '/admin'],
                ['label' => 'Audit & Security', 'description' => 'Open the existing authorized security and audit workspace.', 'url' => '/audit'],
                ['label' => 'MFA Security', 'description' => 'Manage the current account multi-factor security settings.', 'url' => '/security/mfa'],
            ],
            'executive_oversight' => [
                ['label' => "Mayor's Office", 'description' => 'Open the authoritative executive review workspace.', 'url' => '/mayor-office'],
                ['label' => 'Pending approvals', 'description' => 'Review active work currently awaiting executive action.', 'url' => '/transactions?status=for_approval'],
                ['label' => 'Operational Reports', 'description' => 'Open the authorized Core Portal reporting workspace.', 'url' => '/reports'],
            ],
            'department_head' => [
                ['label' => 'My Work', 'description' => 'Review personal actions, deadlines, and recently updated work.', 'url' => '/transactions?view=needs_my_action'],
                ['label' => 'Office Work', 'description' => 'Review active and unassigned work for the current office.', 'url' => '/transactions?view=office_queue'],
                ['label' => 'Correspondence', 'description' => 'Open the authorized correspondence lifecycle workspace.', 'url' => '/correspondence'],
            ],
            default => [
                ['label' => 'Needs Action', 'description' => 'Open active work currently assigned to you.', 'url' => '/transactions?view=needs_my_action'],
                ['label' => 'Correspondence', 'description' => 'Open correspondence available through existing authorization.', 'url' => '/correspondence'],
                ['label' => 'Search Records', 'description' => 'Search the authorized internal records repository.', 'url' => '/records'],
            ],
        };
    }
}
