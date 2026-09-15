<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Employee;
use App\Models\EmployeeDevelopmentRecord;
use App\Models\PerformanceRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeDevelopmentService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly PlatformNotificationService $notifications,
    ) {
    }

    public function recordPerformance(User $actor, Employee $employee, array $data): PerformanceRecord
    {
        $this->assertHrActor($actor);

        return DB::transaction(function () use ($actor, $employee, $data): PerformanceRecord {
            $record = PerformanceRecord::query()->create([
                'employee_id' => $employee->id,
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'evaluator_user_id' => $data['evaluator_user_id'] ?? $actor->id,
                'rating' => $data['rating'] ?? null,
                'rating_scale' => $data['rating_scale'] ?? null,
                'status' => $data['status'] ?? 'recorded',
                'summary' => $data['summary'] ?? null,
                'reviewed_at' => ! empty($data['reviewed']) ? now() : null,
                'created_by_user_id' => $actor->id,
            ]);

            $employee->loadMissing('user');
            if ($employee->user?->is_active) {
                $this->notifications->notifyUser($employee->user, [
                    'event_key' => 'performance-recorded-'.$record->id,
                    'source_domain' => 'hr_performance',
                    'source_type' => PerformanceRecord::class,
                    'source_id' => $record->id,
                    'priority' => 'info',
                    'title' => 'Performance record updated',
                    'message' => 'A performance record for '.$record->period_start->format('M Y').' to '.$record->period_end->format('M Y').' was added to your HR profile.',
                    'action_url' => '/employees/'.$employee->id,
                ]);
            }

            $this->audit->record(
                $actor,
                'hr.performance.recorded',
                'Recorded performance evidence for '.$employee->employee_number.' · '.$employee->full_name.'.',
                'allowed',
                PerformanceRecord::class,
                $record->id,
            );

            return $record->fresh(['employee.department', 'evaluator']);
        });
    }

    public function recordDevelopment(User $actor, Employee $employee, array $data): EmployeeDevelopmentRecord
    {
        $this->assertHrActor($actor);

        return DB::transaction(function () use ($actor, $employee, $data): EmployeeDevelopmentRecord {
            $record = EmployeeDevelopmentRecord::query()->create([
                'employee_id' => $employee->id,
                'record_type' => $data['record_type'],
                'title' => $data['title'],
                'provider' => $data['provider'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'attained_at' => $data['attained_at'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'status' => $data['status'] ?? 'active',
                'notes' => $data['notes'] ?? null,
                'created_by_user_id' => $actor->id,
            ]);

            if ($record->expires_at) {
                $this->publishDevelopmentExpiry($record, $actor);
            }

            $this->audit->record(
                $actor,
                'hr.development.recorded',
                'Recorded '.$record->record_type.' evidence for '.$employee->employee_number.' · '.$employee->full_name.'.',
                'allowed',
                EmployeeDevelopmentRecord::class,
                $record->id,
            );

            return $record->fresh(['employee.department']);
        });
    }

    public function syncExpiryAlerts(User $actor): array
    {
        $this->assertHrActor($actor);
        $windowEnd = today()->addDays(120);
        $contractCount = 0;
        $developmentCount = 0;

        Employee::query()
            ->whereIn('employment_status', ['active', 'onboarding'])
            ->whereNotNull('contract_end_date')
            ->whereBetween('contract_end_date', [today(), $windowEnd])
            ->with(['user', 'department'])
            ->each(function (Employee $employee) use ($actor, &$contractCount): void {
                $this->publishContractExpiry($employee, $actor);
                $contractCount++;
            });

        EmployeeDevelopmentRecord::query()
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [today(), $windowEnd])
            ->with('employee.user')
            ->each(function (EmployeeDevelopmentRecord $record) use ($actor, &$developmentCount): void {
                $this->publishDevelopmentExpiry($record, $actor);
                $developmentCount++;
            });

        $this->audit->record(
            $actor,
            'hr.expiry_alerts.synced',
            "Synchronized {$contractCount} contract and {$developmentCount} development-record expiry alerts.",
            'allowed',
            'HRExpiryAlerts',
        );

        return ['contracts' => $contractCount, 'development' => $developmentCount];
    }

    private function publishContractExpiry(Employee $employee, User $actor): void
    {
        if (! $employee->contract_end_date) {
            return;
        }

        CalendarEvent::query()->updateOrCreate(
            ['event_key' => 'employee-contract-expiry-'.$employee->id],
            [
                'event_type' => 'hr_contract_expiry',
                'title' => 'Contract review · '.$employee->full_name,
                'description' => 'Employment contract end date requires HR review. This is a monitoring event, not an automatic separation action.',
                'scope' => 'department',
                'department_id' => $employee->department_id,
                'user_id' => $employee->user_id,
                'source_domain' => 'hr_contract',
                'source_type' => Employee::class,
                'source_id' => $employee->id,
                'priority' => 'action_required',
                'starts_at' => $employee->contract_end_date->copy()->startOfDay()->addHours(9),
                'all_day' => false,
                'action_url' => '/employees/'.$employee->id,
                'status' => 'scheduled',
                'created_by_user_id' => $actor->id,
            ],
        );

        if ($employee->user?->is_active) {
            $this->notifications->notifyUser($employee->user, [
                'event_key' => 'employee-contract-expiry-'.$employee->id,
                'source_domain' => 'hr_contract',
                'source_type' => Employee::class,
                'source_id' => $employee->id,
                'priority' => 'action_required',
                'title' => 'Contract review date recorded',
                'message' => 'Your employment record has a contract review/end date on '.$employee->contract_end_date->format('M j, Y').'. HR will validate any required action.',
                'action_url' => '/employees/'.$employee->id,
            ]);
        }
    }

    private function publishDevelopmentExpiry(EmployeeDevelopmentRecord $record, User $actor): void
    {
        $record->loadMissing('employee.user');
        if (! $record->expires_at) {
            return;
        }

        CalendarEvent::query()->updateOrCreate(
            ['event_key' => 'employee-development-expiry-'.$record->id],
            [
                'event_type' => 'hr_credential_expiry',
                'title' => ucfirst($record->record_type).' expiry · '.$record->title,
                'description' => 'HR development/eligibility record expiry monitoring event.',
                'scope' => 'department',
                'department_id' => $record->employee->department_id,
                'user_id' => $record->employee->user_id,
                'source_domain' => 'hr_development',
                'source_type' => EmployeeDevelopmentRecord::class,
                'source_id' => $record->id,
                'priority' => 'action_required',
                'starts_at' => $record->expires_at->copy()->startOfDay()->addHours(9),
                'all_day' => false,
                'action_url' => '/employees/'.$record->employee_id,
                'status' => 'scheduled',
                'created_by_user_id' => $actor->id,
            ],
        );

        if ($record->expires_at->lte(today()->addDays(120)) && $record->employee->user?->is_active) {
            $this->notifications->notifyUser($record->employee->user, [
                'event_key' => 'employee-development-expiry-'.$record->id,
                'source_domain' => 'hr_development',
                'source_type' => EmployeeDevelopmentRecord::class,
                'source_id' => $record->id,
                'priority' => 'action_required',
                'title' => 'Credential or eligibility expiry',
                'message' => $record->title.' is recorded to expire on '.$record->expires_at->format('M j, Y').'.',
                'action_url' => '/employees/'.$record->employee_id,
            ]);
        }
    }

    private function assertHrActor(User $actor): void
    {
        $actor->loadMissing('employee.department');
        $allowed = $actor->isRole('system_admin', 'hr_officer')
            && ($actor->isRole('system_admin') || $actor->employee?->department?->code === 'HRMO');

        if (! $allowed) {
            throw ValidationException::withMessages(['authorization' => 'Authorized HR administration is required.']);
        }
    }
}
