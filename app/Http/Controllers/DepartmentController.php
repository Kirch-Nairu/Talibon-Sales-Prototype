<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\WorkflowTransaction;
use App\Services\DepartmentWorkspaceQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function __construct(
        private readonly DepartmentWorkspaceQuery $workspace,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $actor = $request->user();

        if ($actor && $this->workspace->allowed($actor)) {
            return Inertia::render('Departments/Workspace', $this->workspace->workspace($actor));
        }

        return Inertia::render('Departments/Index', $this->directory());
    }

    /** @return array<string, mixed> */
    private function directory(): array
    {
        $departments = Department::query()
            ->activeRoutable()
            ->withCount([
                'employees as active_employees_count' => fn (Builder $query) => $query->where('employment_status', 'active'),
            ])
            ->orderBy('branch')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'short_name', 'branch', 'office_type', 'is_routable', 'sort_order']);

        $terminal = array_values(config('workflow.default.terminal_statuses', [
            'approved',
            'disapproved',
            'closed',
        ]));

        $activeTransactions = WorkflowTransaction::query()
            ->whereIn('current_department_id', $departments->pluck('id'))
            ->whereNotIn('status', $terminal)
            ->select('current_department_id')
            ->selectRaw('COUNT(*) AS aggregate')
            ->groupBy('current_department_id')
            ->pluck('aggregate', 'current_department_id');

        $departments = $departments->map(function (Department $department) use ($activeTransactions): array {
            return [
                'id' => $department->id,
                'code' => $department->code,
                'name' => $department->name,
                'short_name' => $department->short_name,
                'branch' => $department->branch,
                'office_type' => $department->office_type,
                'is_routable' => $department->is_routable,
                'active_employees_count' => $department->active_employees_count,
                'active_transactions_count' => (int) ($activeTransactions[$department->id] ?? 0),
                'is_executive' => $department->code === 'MAYOR',
                'is_legislative' => $department->branch === 'legislative',
            ];
        });

        return [
            'departments' => $departments,
            'summary' => [
                'offices' => $departments->count(),
                'executiveOffices' => $departments->where('branch', 'executive')->count(),
                'legislativeOffices' => $departments->where('branch', 'legislative')->count(),
                'employees' => $departments->sum('active_employees_count'),
                'activeTransactions' => $departments->sum('active_transactions_count'),
            ],
        ];
    }
}
