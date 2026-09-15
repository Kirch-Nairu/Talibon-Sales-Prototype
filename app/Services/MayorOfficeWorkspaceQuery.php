<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use App\Models\WorkflowTransaction;

final class MayorOfficeWorkspaceQuery
{
    /** @return array<string, mixed> */
    public function workspace(User $user): array
    {
        $user->loadMissing('employee.department');

        abort_unless(
            $user->isRole('system_admin', 'mayor_approver', 'mayor_staff')
            && ($user->isRole('system_admin') || $user->employee?->department?->code === 'MAYOR'),
            403,
        );

        $mayorDepartment = Department::query()
            ->where('code', 'MAYOR')
            ->firstOrFail();

        $open = WorkflowTransaction::query()
            ->whereNotIn('status', ['approved', 'disapproved', 'closed']);

        $queue = (clone $open)
            ->where('current_department_id', $mayorDepartment->id)
            ->with([
                'originDepartment:id,code,name,short_name',
                'currentDepartment:id,code,name,short_name',
            ])
            ->orderByRaw("CASE WHEN priority = 'urgent' THEN 0 WHEN priority = 'high' THEN 1 ELSE 2 END")
            ->orderBy('due_at')
            ->get();

        $overdue = (clone $open)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->with('currentDepartment:id,code,name,short_name')
            ->orderBy('due_at')
            ->limit(100)
            ->get();

        $returned = (clone $open)
            ->whereIn('status', ['returned', 'information_requested'])
            ->with([
                'originDepartment:id,code,name,short_name',
                'currentDepartment:id,code,name,short_name',
            ])
            ->latest('received_at')
            ->limit(100)
            ->get();

        $bottlenecks = (clone $open)
            ->selectRaw('current_department_id, COUNT(*) as open_count')
            ->groupBy('current_department_id')
            ->orderByDesc('open_count')
            ->with('currentDepartment:id,code,name,short_name')
            ->limit(10)
            ->get();

        return [
            'queue' => $queue,
            'overdue' => $overdue,
            'returned' => $returned,
            'bottlenecks' => $bottlenecks,
            'stats' => [
                'forApproval' => $queue->where('status', 'for_approval')->count(),
                'forReview' => $queue->where('status', 'for_review')->count(),
                'highPriority' => $queue->whereIn('priority', ['high', 'urgent'])->count(),
                'total' => $queue->count(),
                'municipalityOpen' => (clone $open)->count(),
                'municipalityOverdue' => $overdue->count(),
                'returnedOrInfoRequested' => $returned->count(),
            ],
        ];
    }
}
