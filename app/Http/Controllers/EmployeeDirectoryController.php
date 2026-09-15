<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeDirectoryController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $departmentCode = trim((string) $request->query('department', ''));

        $employees = Employee::query()
            ->select(['id', 'employee_number', 'full_name', 'work_email', 'user_id', 'department_id', 'position_title', 'employment_status'])
            ->with('department:id,code,name,short_name')
            ->where('employment_status', 'active')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $needle = '%'.mb_strtolower($search).'%';
                $query->where(function (Builder $nested) use ($needle): void {
                    $nested->whereRaw('LOWER(employee_number) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(full_name) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(work_email) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(position_title) LIKE ?', [$needle]);
                });
            })
            ->when($departmentCode !== '', function (Builder $query) use ($departmentCode): void {
                $query->whereHas('department', fn (Builder $department) => $department->where('code', $departmentCode));
            })
            ->orderBy('full_name')
            ->paginate(40)
            ->withQueryString();

        $featuredEmails = [
            'admin@talibon.demo',
            'mayor@talibon.demo',
            'engineering@talibon.demo',
            'budget@talibon.demo',
            'hr@talibon.demo',
            'legislative@talibon.demo',
            'employee@talibon.demo',
        ];

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'filters' => [
                'q' => $search,
                'department' => $departmentCode,
            ],
            'departments' => Department::query()
                ->activeRoutable()
                ->orderBy('name')
                ->get(['code', 'name', 'short_name']),
            'summary' => [
                'employees' => Employee::query()->where('employment_status', 'active')->count(),
                'portalAccounts' => Employee::query()->where('employment_status', 'active')->whereNotNull('user_id')->count(),
                'featuredLogins' => User::query()->whereIn('email', $featuredEmails)->where('is_active', true)->count(),
                'offices' => Department::query()->activeRoutable()->count(),
            ],
        ]);
    }
}
