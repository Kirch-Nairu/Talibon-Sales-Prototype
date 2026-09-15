<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;

final class SystemAdministrationQuery
{
    public function __construct(
        private readonly DashboardTransactionQuery $transactions,
    ) {}

    /** @return array<string, mixed> */
    public function dashboard(User $actor): array
    {
        $officeIdentities = $this->officeIdentities();

        return [
            ...$this->buildSummary($actor, $officeIdentities),
            'officeIdentities' => $officeIdentities,
        ];
    }

    /** @return array<string, mixed> */
    public function summary(User $actor): array
    {
        return $this->buildSummary($actor, $this->officeIdentities());
    }

    /**
     * @param  array<int, array<string, mixed>>  $officeIdentities
     * @return array<string, mixed>
     */
    private function buildSummary(User $actor, array $officeIdentities): array
    {
        $transactionWorkspace = $this->transactions->workspace($actor);
        $officeIdentityCollection = collect($officeIdentities);

        return [
            'overview' => $this->overview(),
            'officeIdentityStatus' => [
                'configured' => $officeIdentityCollection->whereNotNull('officialEmail')->count(),
                'pending' => $officeIdentityCollection->whereNull('officialEmail')->count(),
            ],
            'operations' => [
                'summary' => $transactionWorkspace['municipalOverview'] ?? [],
                'departmentWorkload' => $transactionWorkspace['departmentWorkload'] ?? [],
            ],
            'security' => $this->security(),
        ];
    }

    /** @return array<string, int> */
    private function overview(): array
    {
        $privilegedRoles = array_values(config('identity.privileged_roles', []));

        return [
            'totalEmployees' => Employee::query()->count(),
            'portalUsers' => User::query()->count(),
            'activeUsers' => User::query()->where('is_active', true)->count(),
            'inactiveUsers' => User::query()->where('is_active', false)->count(),
            'employeesWithoutPortalAccounts' => Employee::query()->whereNull('user_id')->count(),
            'activeDepartments' => Department::query()->where('is_active', true)->count(),
            'privilegedUsers' => User::query()->whereIn('role', $privilegedRoles)->count(),
            'mfaEnrolled' => User::query()->whereNotNull('mfa_confirmed_at')->count(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function officeIdentities(): array
    {
        $configured = config('office_identity.official_emails', []);
        $pendingStatus = (string) config(
            'office_identity.pending_status',
            'Awaiting official office email registry',
        );

        return Department::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'short_name'])
            ->map(function (Department $department) use ($configured, $pendingStatus): array {
                $email = $configured[$department->code] ?? null;
                $email = is_string($email) && trim($email) !== '' ? trim($email) : null;

                return [
                    'office' => $department->name,
                    'code' => $department->code,
                    'shortName' => $department->short_name,
                    'officialEmail' => $email,
                    'status' => $email ? 'Configured' : $pendingStatus,
                ];
            })
            ->all();
    }

    /** @return array<string, mixed> */
    private function security(): array
    {
        $privilegedRoles = array_values(config('identity.privileged_roles', []));

        $events = AuditLog::query()
            ->select(['id', 'actor_user_id', 'action', 'outcome', 'summary', 'created_at'])
            ->with('actor:id,name')
            ->where(function ($query): void {
                $query->where('action', 'like', 'auth.%')
                    ->orWhere('action', 'like', 'security.%');
            })
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(fn (AuditLog $event): array => [
                'actor' => $event->actor?->name,
                'action' => $event->action,
                'outcome' => $event->outcome,
                'summary' => $event->summary,
                'createdAt' => $event->created_at?->toIso8601String(),
            ])
            ->all();

        return [
            'privilegedAccounts' => User::query()->whereIn('role', $privilegedRoles)->count(),
            'mfaEnrolled' => User::query()->whereNotNull('mfa_confirmed_at')->count(),
            'inactiveAccounts' => User::query()->where('is_active', false)->count(),
            'recentEvents' => $events,
        ];
    }
}
