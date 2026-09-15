<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\DtrDailySummary;
use App\Models\DtrPeriod;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DtrService
{
    private const IN_EVENTS = ['in', 'time_in', 'check_in'];
    private const OUT_EVENTS = ['out', 'time_out', 'check_out'];

    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function generate(User $actor, array $data): DtrPeriod
    {
        $this->assertHrActor($actor);
        $start = Carbon::parse($data['period_start'])->startOfDay();
        $end = Carbon::parse($data['period_end'])->endOfDay();

        if ($end->lt($start)) {
            throw ValidationException::withMessages(['period_end' => 'The DTR period end must be on or after the start date.']);
        }
        if ($start->diffInDays($end) > 62) {
            throw ValidationException::withMessages(['period_end' => 'A DTR generation window may not exceed 63 calendar days.']);
        }

        return DB::transaction(function () use ($actor, $data, $start, $end): DtrPeriod {
            $period = DtrPeriod::query()->firstOrNew([
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
            ]);

            if ($period->exists && $period->status === 'locked') {
                throw ValidationException::withMessages(['period' => 'Locked DTR periods cannot be regenerated.']);
            }

            $period->fill([
                'label' => $data['label'] ?: $start->format('M d').' - '.$end->format('M d, Y').' DTR',
                'status' => 'generated',
                'generated_at' => now(),
                'generated_by_user_id' => $actor->id,
            ])->save();

            $this->regenerateSummaries($period);
            $this->audit->record($actor, 'hr.dtr.generated', 'Generated DTR period '.$period->label.'.', 'allowed', DtrPeriod::class, $period->id);

            return $period->fresh(['summaries']);
        });
    }

    public function lock(User $actor, DtrPeriod $period): DtrPeriod
    {
        $this->assertHrActor($actor);

        return DB::transaction(function () use ($actor, $period): DtrPeriod {
            $locked = DtrPeriod::query()->lockForUpdate()->findOrFail($period->id);
            if ($locked->status === 'locked') {
                return $locked;
            }

            $locked->update([
                'status' => 'locked',
                'locked_at' => now(),
                'locked_by_user_id' => $actor->id,
            ]);
            $this->audit->record($actor, 'hr.dtr.locked', 'Locked DTR period '.$locked->label.'.', 'allowed', DtrPeriod::class, $locked->id);

            return $locked->fresh();
        });
    }

    public function employeeSnapshot(DtrPeriod $period, Employee $employee): array
    {
        $base = DtrDailySummary::query()
            ->where('dtr_period_id', $period->id)
            ->where('employee_id', $employee->id);

        $approvedLeaveUnits = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $period->period_end)
            ->whereDate('end_date', '>=', $period->period_start)
            ->sum('units');

        return [
            'daysWithLogs' => (clone $base)->where('raw_event_count', '>', 0)->count(),
            'completeDays' => (clone $base)->where('source_status', 'complete_pair')->count(),
            'partialDays' => (clone $base)->where('source_status', 'partial')->count(),
            'leaveDaysRepresented' => (clone $base)->where('leave_status', 'approved')->count(),
            'approvedLeaveUnits' => (float) $approvedLeaveUnits,
        ];
    }

    public function linkPayroll(User $actor, PayrollPeriod $payroll, DtrPeriod $dtr): PayrollPeriod
    {
        $this->assertHrActor($actor);

        if ($dtr->status !== 'locked') {
            throw ValidationException::withMessages(['dtr_period' => 'Lock the DTR period before linking it to payroll.']);
        }
        if ($dtr->period_start->gt($payroll->period_start) || $dtr->period_end->lt($payroll->period_end)) {
            throw ValidationException::withMessages(['dtr_period' => 'The DTR period must fully cover the payroll period.']);
        }

        return DB::transaction(function () use ($actor, $payroll, $dtr): PayrollPeriod {
            $lockedPayroll = PayrollPeriod::query()->with('entries.employee')->lockForUpdate()->findOrFail($payroll->id);

            foreach ($lockedPayroll->entries as $entry) {
                $snapshot = $this->employeeSnapshot($dtr, $entry->employee);
                $entry->update([
                    'dtr_days_with_logs' => $snapshot['daysWithLogs'],
                    'dtr_complete_days' => $snapshot['completeDays'],
                    'dtr_partial_days' => $snapshot['partialDays'],
                    'approved_leave_units' => $snapshot['approvedLeaveUnits'],
                    'dtr_snapshot_status' => 'linked_context_only',
                ]);
            }

            $lockedPayroll->update([
                'dtr_period_id' => $dtr->id,
                'calculation_mode' => 'prototype_with_dtr_context',
                'source_notes' => 'DTR and approved-leave context linked for review. Existing payroll monetary values were preserved and were not recalculated from attendance.',
            ]);

            $this->audit->record($actor, 'hr.payroll.dtr_linked', 'Linked DTR '.$dtr->label.' to payroll '.$lockedPayroll->label.' without recalculating monetary values.', 'allowed', PayrollPeriod::class, $lockedPayroll->id);

            return $lockedPayroll->fresh(['dtrPeriod']);
        });
    }

    private function regenerateSummaries(DtrPeriod $period): void
    {
        DtrDailySummary::query()->where('dtr_period_id', $period->id)->delete();

        $start = $period->period_start->copy()->startOfDay();
        $end = $period->period_end->copy()->endOfDay();
        $logs = AttendanceLog::query()
            ->whereBetween('occurred_at', [$start, $end])
            ->orderBy('occurred_at')
            ->get();
        $leaves = LeaveRequest::query()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $period->period_end)
            ->whereDate('end_date', '>=', $period->period_start)
            ->get();

        $employeeIds = $logs->pluck('employee_id')->merge($leaves->pluck('employee_id'))->unique()->values();
        $logsByDay = $logs->groupBy(fn (AttendanceLog $log): string => $log->employee_id.'|'.$log->occurred_at->toDateString());
        $leavesByEmployee = $leaves->groupBy('employee_id');

        foreach ($employeeIds as $employeeId) {
            $employeeLeaves = $leavesByEmployee->get($employeeId, collect());

            foreach (CarbonPeriod::create($period->period_start, $period->period_end) as $day) {
                $workDay = Carbon::instance($day)->startOfDay();
                $date = $workDay->toDateString();
                $dayLogs = $logsByDay->get($employeeId.'|'.$date, collect());
                $approvedLeave = $employeeLeaves->first(fn (LeaveRequest $leave): bool =>
                    $leave->start_date->startOfDay()->lte($workDay) && $leave->end_date->startOfDay()->gte($workDay)
                );

                if ($dayLogs->isEmpty() && ! $approvedLeave) {
                    continue;
                }

                $ins = $dayLogs->filter(fn (AttendanceLog $log): bool => in_array(strtolower($log->event_type), self::IN_EVENTS, true));
                $outs = $dayLogs->filter(fn (AttendanceLog $log): bool => in_array(strtolower($log->event_type), self::OUT_EVENTS, true));
                $firstIn = $ins->sortBy('occurred_at')->first()?->occurred_at;
                $lastOut = $outs->sortByDesc('occurred_at')->first()?->occurred_at;
                $status = $dayLogs->isEmpty()
                    ? 'leave_only'
                    : ($firstIn && $lastOut ? 'complete_pair' : 'partial');

                DtrDailySummary::query()->create([
                    'dtr_period_id' => $period->id,
                    'employee_id' => $employeeId,
                    'work_date' => $date,
                    'first_in_at' => $firstIn,
                    'last_out_at' => $lastOut,
                    'raw_event_count' => $dayLogs->count(),
                    'leave_status' => $approvedLeave ? 'approved' : null,
                    'source_status' => $status,
                    'generated_at' => now(),
                ]);
            }
        }
    }

    private function assertHrActor(User $actor): void
    {
        $actor->loadMissing('employee.department');
        $allowed = $actor->isRole('system_admin', 'hr_officer')
            && ($actor->isRole('system_admin') || $actor->employee?->department?->code === 'HRMO');

        if (! $allowed) {
            throw ValidationException::withMessages(['authorization' => 'Authorized HR administration is required for DTR and payroll-context mutations.']);
        }
    }
}
