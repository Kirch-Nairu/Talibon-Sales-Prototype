<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeHealthAccessGrant;
use App\Models\EmployeeHealthRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeHealthVaultService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly PlatformNotificationService $notifications,
    ) {
    }

    public function canManageAccess(User $actor): bool
    {
        $actor->loadMissing('employee.department');

        return $actor->isRole('system_admin')
            || $actor->employee?->department?->code === 'DPO';
    }

    public function canView(User $actor, Employee $employee): bool
    {
        return $this->activeGrantQuery($actor, $employee)
            ->where('can_view', true)
            ->exists();
    }

    public function canManageRecords(User $actor, Employee $employee): bool
    {
        return $this->activeGrantQuery($actor, $employee)
            ->where('can_manage', true)
            ->exists();
    }

    public function recordsFor(User $actor, Employee $employee)
    {
        if (! $this->canView($actor, $employee)) {
            $this->audit->record(
                $actor,
                'hr.health.access',
                'Denied restricted employee health-vault access for '.$employee->employee_number.'.',
                'denied',
                Employee::class,
                $employee->id,
            );
            abort(403, 'Explicit employee health-vault authorization is required.');
        }

        $this->audit->record(
            $actor,
            'hr.health.access',
            'Viewed restricted employee health-vault records for '.$employee->employee_number.'.',
            'allowed',
            Employee::class,
            $employee->id,
        );

        return EmployeeHealthRecord::query()
            ->where('employee_id', $employee->id)
            ->with('creator:id,name')
            ->latest('issued_at')
            ->latest('id')
            ->get();
    }

    public function record(User $actor, Employee $employee, array $data): EmployeeHealthRecord
    {
        if (! $this->canManageRecords($actor, $employee)) {
            $this->audit->record(
                $actor,
                'hr.health.mutate',
                'Denied restricted employee health-vault mutation for '.$employee->employee_number.'.',
                'denied',
                Employee::class,
                $employee->id,
            );
            abort(403, 'Explicit health-vault manage authorization is required.');
        }

        return DB::transaction(function () use ($actor, $employee, $data): EmployeeHealthRecord {
            $record = EmployeeHealthRecord::query()->create([
                'employee_id' => $employee->id,
                'record_type' => $data['record_type'],
                'title' => $data['title'],
                'issued_at' => $data['issued_at'] ?? null,
                'valid_until' => $data['valid_until'] ?? null,
                'status' => $data['status'] ?? 'active',
                'summary' => $data['summary'] ?? null,
                'restriction_notes' => $data['restriction_notes'] ?? null,
                'created_by_user_id' => $actor->id,
            ]);

            $this->audit->record(
                $actor,
                'hr.health.recorded',
                'Recorded employment/occupational-health evidence for '.$employee->employee_number.'.',
                'allowed',
                EmployeeHealthRecord::class,
                $record->id,
            );

            return $record->fresh('creator:id,name');
        });
    }

    public function grantAccess(User $actor, User $recipient, ?Employee $employee, array $data): EmployeeHealthAccessGrant
    {
        $this->assertAccessManager($actor);

        if (! $recipient->is_active) {
            throw ValidationException::withMessages(['user_id' => 'Health-vault access can only be granted to an active portal identity.']);
        }

        return DB::transaction(function () use ($actor, $recipient, $employee, $data): EmployeeHealthAccessGrant {
            $grant = EmployeeHealthAccessGrant::query()->create([
                'user_id' => $recipient->id,
                'employee_id' => $employee?->id,
                'can_view' => true,
                'can_manage' => (bool) ($data['can_manage'] ?? false),
                'purpose' => $data['purpose'],
                'granted_by_user_id' => $actor->id,
                'granted_at' => now(),
                'expires_at' => $data['expires_at'] ?? null,
            ]);

            $this->notifications->notifyUser($recipient, [
                'event_key' => 'health-vault-grant-'.$grant->id,
                'source_domain' => 'hr_health_access',
                'source_type' => EmployeeHealthAccessGrant::class,
                'source_id' => $grant->id,
                'priority' => 'acknowledgement_required',
                'title' => 'Restricted health-vault access granted',
                'message' => $employee
                    ? 'You received explicit access to the employment health vault of '.$employee->employee_number.'.'
                    : 'You received explicit municipality-wide employment health-vault access.',
                'action_url' => $employee ? '/hris/health/'.$employee->id : '/employees',
                'requires_acknowledgement' => true,
                'expires_at' => $grant->expires_at,
            ]);

            $this->audit->record(
                $actor,
                'hr.health.access_granted',
                'Granted explicit health-vault access to '.$recipient->email.($employee ? ' for '.$employee->employee_number : ' for all employee vaults').'.',
                'allowed',
                EmployeeHealthAccessGrant::class,
                $grant->id,
            );

            return $grant->fresh(['user:id,name,email', 'employee:id,employee_number,full_name']);
        });
    }

    public function revokeAccess(User $actor, EmployeeHealthAccessGrant $grant): EmployeeHealthAccessGrant
    {
        $this->assertAccessManager($actor);

        if ($grant->revoked_at) {
            return $grant;
        }

        $grant->update([
            'revoked_at' => now(),
            'revoked_by_user_id' => $actor->id,
        ]);

        $this->audit->record(
            $actor,
            'hr.health.access_revoked',
            'Revoked explicit health-vault access grant #'.$grant->id.'.',
            'allowed',
            EmployeeHealthAccessGrant::class,
            $grant->id,
        );

        return $grant->fresh();
    }

    private function activeGrantQuery(User $actor, Employee $employee): Builder
    {
        return EmployeeHealthAccessGrant::query()
            ->where('user_id', $actor->id)
            ->whereNull('revoked_at')
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function (Builder $query) use ($employee): void {
                $query->whereNull('employee_id')->orWhere('employee_id', $employee->id);
            });
    }

    private function assertAccessManager(User $actor): void
    {
        if (! $this->canManageAccess($actor)) {
            $this->audit->record(
                $actor,
                'hr.health.access_admin',
                'Denied attempt to manage employee health-vault access grants.',
                'denied',
                EmployeeHealthAccessGrant::class,
            );
            abort(403, 'Health-vault access policy may only be administered by authorized security/data-protection personnel.');
        }
    }
}
