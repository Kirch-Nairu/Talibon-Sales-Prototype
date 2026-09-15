<?php

namespace App\Services;

use App\Models\Department;
use App\Models\LeaveCreditAccount;
use App\Models\LeaveCreditTransaction;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly PlatformNotificationService $notifications,
        private readonly CalendarService $calendar,
    ) {
    }

    public function submit(User $actor, array $data): LeaveRequest
    {
        $employee = $actor->employee;
        if (! $employee) {
            throw ValidationException::withMessages(['employee' => 'An employee profile is required.']);
        }

        $type = LeaveType::query()->whereKey($data['leave_type_id'])->where('is_active', true)->firstOrFail();
        $units = (float) $data['units'];

        if ($type->tracks_balance) {
            $account = LeaveCreditAccount::query()->where('employee_id', $employee->id)->where('leave_type_id', $type->id)->first();
            if (! $account || (float) $account->balance < $units) {
                throw ValidationException::withMessages(['units' => 'Requested leave exceeds the currently available credit balance.']);
            }
        }

        $leave = LeaveRequest::query()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'units' => $units,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        $hrmo = Department::query()->where('code', 'HRMO')->first();
        if ($hrmo) {
            $this->notifications->notifyDepartment($hrmo, [
                'event_key' => 'leave-submitted-'.$leave->id,
                'source_domain' => 'leave',
                'source_type' => LeaveRequest::class,
                'source_id' => $leave->id,
                'priority' => 'action_required',
                'title' => 'Leave request awaiting review',
                'message' => $employee->full_name.' submitted '.$type->name.' for '.$leave->start_date->format('M d').' - '.$leave->end_date->format('M d, Y').'.',
                'action_url' => '/hris/admin',
            ]);
        }

        $this->audit->record($actor, 'leave.submitted', "Submitted leave request #{$leave->id}.", 'allowed', LeaveRequest::class, $leave->id);
        return $leave->fresh(['leaveType']);
    }

    public function approve(User $reviewer, LeaveRequest $leave, ?string $notes = null): LeaveRequest
    {
        return DB::transaction(function () use ($reviewer, $leave, $notes): LeaveRequest {
            $locked = LeaveRequest::query()->lockForUpdate()->with(['leaveType', 'employee.user', 'employee.department'])->findOrFail($leave->id);
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['status' => 'Only pending leave requests may be approved.']);
            }

            if ($locked->leaveType->tracks_balance) {
                $account = LeaveCreditAccount::query()->lockForUpdate()->where('employee_id', $locked->employee_id)->where('leave_type_id', $locked->leave_type_id)->firstOrFail();
                $units = (float) $locked->units;
                if ((float) $account->balance < $units) {
                    throw ValidationException::withMessages(['units' => 'The employee no longer has enough leave credits for this request.']);
                }
                $account->update(['balance' => (float) $account->balance - $units]);
                LeaveCreditTransaction::query()->create([
                    'leave_credit_account_id' => $account->id,
                    'amount' => -$units,
                    'entry_type' => 'approved_leave',
                    'source_type' => LeaveRequest::class,
                    'source_id' => $locked->id,
                    'notes' => $notes ?: 'Approved leave deduction',
                    'actor_user_id' => $reviewer->id,
                    'created_at' => now(),
                ]);
            }

            $locked->update(['status' => 'approved', 'reviewed_by_user_id' => $reviewer->id, 'reviewed_at' => now(), 'review_notes' => $notes]);
            $locked->refresh()->load(['leaveType', 'employee.user', 'employee.department']);

            if ($locked->employee->user) {
                $this->notifications->notifyUser($locked->employee->user, [
                    'event_key' => 'leave-approved-'.$locked->id,
                    'source_domain' => 'leave',
                    'source_type' => LeaveRequest::class,
                    'source_id' => $locked->id,
                    'priority' => 'info',
                    'title' => 'Leave request approved',
                    'message' => $locked->leaveType->name.' has been approved for '.$locked->start_date->format('M d').' - '.$locked->end_date->format('M d, Y').'.',
                    'action_url' => '/hris',
                ]);
            }

            $this->calendar->syncApprovedLeave($locked, $reviewer);
            $this->audit->record($reviewer, 'leave.approved', "Approved leave request #{$locked->id}.", 'allowed', LeaveRequest::class, $locked->id);
            return $locked;
        });
    }

    public function reject(User $reviewer, LeaveRequest $leave, ?string $notes = null): LeaveRequest
    {
        $leave = LeaveRequest::query()->whereKey($leave->id)->where('status', 'pending')->with(['leaveType', 'employee.user'])->firstOrFail();
        $leave->update(['status' => 'rejected', 'reviewed_by_user_id' => $reviewer->id, 'reviewed_at' => now(), 'review_notes' => $notes]);

        if ($leave->employee->user) {
            $this->notifications->notifyUser($leave->employee->user, [
                'event_key' => 'leave-rejected-'.$leave->id,
                'source_domain' => 'leave',
                'source_type' => LeaveRequest::class,
                'source_id' => $leave->id,
                'priority' => 'info',
                'title' => 'Leave request reviewed',
                'message' => $leave->leaveType->name.' request was not approved. Review the HRIS request history for details.',
                'action_url' => '/hris',
            ]);
        }

        $this->audit->record($reviewer, 'leave.rejected', "Rejected leave request #{$leave->id}.", 'allowed', LeaveRequest::class, $leave->id);
        return $leave->fresh(['leaveType', 'employee.user']);
    }
}
