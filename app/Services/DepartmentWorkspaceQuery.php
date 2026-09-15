<?php

namespace App\Services;

use App\Models\Department;
use App\Models\TransactionEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class DepartmentWorkspaceQuery
{
    private const ACTIVITY_LIMIT = 15;

    public function __construct(
        private readonly DashboardOfficeQuery $office,
        private readonly TransactionVisibilityQuery $visibility,
    ) {
    }

    /** @return array<string, mixed> */
    public function workspace(User $actor): array
    {
        $actor->loadMissing('employee.department');

        if (! $this->allowed($actor)) {
            throw new AccessDeniedHttpException('Department operational workspace is limited to active Department Heads.');
        }

        /** @var Department $department */
        $department = $actor->employee->department;
        $office = $this->office->workspace($actor);

        return [
            'department' => [
                'id' => (int) $department->id,
                'code' => $department->code,
                'name' => $department->name,
                'shortName' => $department->short_name,
                'branch' => $department->branch,
                'officeType' => $department->office_type,
            ],
            'metrics' => $office['metrics'],
            'statusOverview' => $office['statusOverview'],
            'staffWorkload' => $office['staffWorkload'],
            'oldestUnresolved' => $office['oldestUnresolved'],
            'recentActivity' => $this->recentActivity($actor, (int) $department->id),
            'activityLimit' => self::ACTIVITY_LIMIT,
            'drilldowns' => [
                ['label' => 'Office Work', 'href' => '/transactions?view=office_queue'],
                ['label' => 'Correspondence', 'href' => '/correspondence'],
                ['label' => 'Records', 'href' => '/records'],
                ['label' => 'Reports', 'href' => '/reports'],
            ],
        ];
    }

    public function allowed(User $actor): bool
    {
        $actor->loadMissing('employee.department');

        return $actor->is_active
            && $actor->isRole('department_head')
            && $actor->employee !== null
            && $actor->employee->employment_status === 'active'
            && $actor->employee->department !== null
            && $actor->employee->department->is_active;
    }

    /** @return array<int, array<string, mixed>> */
    private function recentActivity(User $actor, int $departmentId): array
    {
        $authorizedTransactions = $this->visibility
            ->scope($actor)
            ->select('id');

        return TransactionEvent::query()
            ->whereIn('transaction_id', $authorizedTransactions)
            ->where(function (Builder $events) use ($departmentId): void {
                $events->where('from_department_id', $departmentId)
                    ->orWhere('to_department_id', $departmentId);
            })
            ->select([
                'id',
                'transaction_id',
                'actor_user_id',
                'from_department_id',
                'to_department_id',
                'action',
                'previous_status',
                'new_status',
                'created_at',
            ])
            ->with([
                'transaction:id,reference_no,title,transaction_type,priority,status',
                'actor:id,name',
                'fromDepartment:id,code,name,short_name',
                'toDepartment:id,code,name,short_name',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (TransactionEvent $event): array => [
                'id' => (int) $event->id,
                'action' => $event->action,
                'actionLabel' => str($event->action)->replace('_', ' ')->headline()->toString(),
                'reference' => $event->transaction?->reference_no,
                'title' => $event->transaction?->title,
                'workflowType' => $event->transaction?->transaction_type,
                'priority' => $event->transaction?->priority,
                'status' => $event->new_status ?: $event->transaction?->status,
                'actor' => $event->actor?->name,
                'fromOffice' => $event->fromDepartment?->short_name ?: $event->fromDepartment?->name,
                'toOffice' => $event->toDepartment?->short_name ?: $event->toDepartment?->name,
                'createdAt' => $event->created_at?->toIso8601String(),
                'detailUrl' => $event->transaction_id ? '/transactions/'.$event->transaction_id : null,
            ])
            ->all();
    }
}
