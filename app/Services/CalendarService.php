<?php

namespace App\Services;

use App\Domain\Workflow\WorkflowDefinitionResolver;
use App\Models\CalendarEvent;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\WorkflowTransaction;

class CalendarService
{
    public function __construct(private readonly WorkflowDefinitionResolver $definitions)
    {
    }

    public function syncTransactionDue(WorkflowTransaction $transaction): ?CalendarEvent
    {
        if (! $transaction->due_at) {
            return null;
        }

        $transaction->loadMissing('assignedEmployee');
        $terminal = $this->definitions->resolve($transaction)->isTerminal($transaction->status);

        return CalendarEvent::query()->updateOrCreate(
            ['event_key' => 'transaction-due-'.$transaction->id],
            [
                'event_type' => 'workflow_deadline',
                'title' => $transaction->reference_no.' due · '.$transaction->title,
                'description' => 'Action deadline for a routed municipal transaction.',
                'scope' => 'department',
                'department_id' => $transaction->current_department_id,
                'user_id' => $transaction->assignedEmployee?->user_id,
                'source_domain' => 'transaction',
                'source_type' => WorkflowTransaction::class,
                'source_id' => $transaction->id,
                'priority' => $transaction->priority,
                'starts_at' => $transaction->due_at,
                'all_day' => false,
                'action_url' => '/transactions/'.$transaction->id,
                'status' => $terminal ? 'completed' : 'scheduled',
                'created_by_user_id' => $transaction->created_by_user_id,
            ],
        );
    }

    public function syncApprovedLeave(LeaveRequest $leave, User $reviewer): CalendarEvent
    {
        $leave->loadMissing(['employee.user', 'employee.department', 'leaveType']);

        return CalendarEvent::query()->updateOrCreate(
            ['event_key' => 'leave-approved-'.$leave->id],
            [
                'event_type' => 'approved_leave',
                'title' => $leave->employee->full_name.' · '.$leave->leaveType->name,
                'description' => 'Approved employee leave / office availability event.',
                'scope' => 'department',
                'department_id' => $leave->employee->department_id,
                'user_id' => $leave->employee->user_id,
                'source_domain' => 'leave',
                'source_type' => LeaveRequest::class,
                'source_id' => $leave->id,
                'priority' => 'normal',
                'starts_at' => $leave->start_date->copy()->startOfDay(),
                'ends_at' => $leave->end_date->copy()->endOfDay(),
                'all_day' => true,
                'action_url' => '/hris',
                'status' => 'scheduled',
                'created_by_user_id' => $reviewer->id,
            ],
        );
    }
}
