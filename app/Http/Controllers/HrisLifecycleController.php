<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeMovement;
use App\Models\EmployeeMovementTask;
use App\Models\OnboardingCase;
use App\Models\OnboardingTask;
use App\Services\EmployeeLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class HrisLifecycleController extends Controller
{
    public function index(): Response
    {
        $onboardingCases = OnboardingCase::query()
            ->with([
                'employee:id,employee_number,full_name,work_email,user_id,department_id,position_title,employment_status',
                'targetDepartment:id,code,name,short_name',
            ])
            ->withCount([
                'tasks as open_required_tasks_count' => fn ($query) => $query
                    ->where('is_required', true)
                    ->whereNotIn('status', ['completed', 'waived']),
            ])
            ->latest('started_at')
            ->limit(100)
            ->get();

        $movements = EmployeeMovement::query()
            ->with([
                'employee:id,employee_number,full_name,department_id,position_title',
                'fromDepartment:id,code,name,short_name',
                'toDepartment:id,code,name,short_name',
            ])
            ->withCount([
                'tasks as open_required_tasks_count' => fn ($query) => $query
                    ->where('is_required', true)
                    ->whereNotIn('status', ['completed', 'waived', 'not_required']),
            ])
            ->latest('applied_at')
            ->limit(100)
            ->get();

        return Inertia::render('Hris/Lifecycle/Index', [
            'onboardingCases' => $onboardingCases,
            'movements' => $movements,
            'departments' => Department::query()
                ->activeRoutable()
                ->orderBy('branch')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'short_name', 'branch']),
            'employees' => Employee::query()
                ->where('employment_status', 'active')
                ->with('department:id,code,name,short_name')
                ->orderBy('full_name')
                ->get(['id', 'employee_number', 'full_name', 'department_id', 'position_title', 'supervisor_employee_id']),
            'supervisors' => Employee::query()
                ->where('employment_status', 'active')
                ->orderBy('full_name')
                ->get(['id', 'employee_number', 'full_name', 'department_id', 'position_title']),
            'today' => now()->toDateString(),
            'summary' => [
                'onboardingActive' => $onboardingCases->where('status', '!=', 'completed')->count(),
                'onboardingBlocked' => $onboardingCases->where('open_required_tasks_count', '>', 0)->count(),
                'movementReviews' => $movements->sum('open_required_tasks_count'),
            ],
        ]);
    }

    public function storeOnboarding(Request $request, EmployeeLifecycleService $service): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'work_email' => ['required', 'email:rfc', 'max:255'],
            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where(fn ($query) => $query->where('is_active', true)->where('is_routable', true)),
            ],
            'position_title' => ['required', 'string', 'max:255'],
            'employment_type' => ['nullable', Rule::in(['regular', 'permanent', 'casual', 'contractual', 'coterminous', 'job_order', 'other'])],
            'appointment_date' => ['nullable', 'date'],
            'planned_start_date' => ['nullable', 'date', 'after_or_equal:today'],
            'supervisor_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'appointment_reference' => ['nullable', 'string', 'max:160'],
        ]);

        $case = $service->startOnboarding($request->user(), $data);

        return redirect()->route('hris.lifecycle.onboarding.show', $case)
            ->with('success', 'Onboarding case created with required operational blockers.');
    }

    public function showOnboarding(OnboardingCase $case): Response
    {
        $case->load([
            'employee:id,employee_number,full_name,work_email,user_id,department_id,position_title,employment_status,employment_type,employment_start_date',
            'employee.department:id,code,name,short_name',
            'targetDepartment:id,code,name,short_name',
            'supervisor:id,employee_number,full_name,position_title',
            'tasks.ownerDepartment:id,code,name,short_name',
            'tasks.completer:id,name',
        ]);

        return Inertia::render('Hris/Lifecycle/Show', [
            'case' => $case,
            'summary' => [
                'required' => $case->tasks->where('is_required', true)->count(),
                'completed' => $case->tasks->where('status', 'completed')->count(),
                'openRequired' => $case->tasks
                    ->where('is_required', true)
                    ->whereNotIn('status', ['completed', 'waived'])
                    ->count(),
            ],
        ]);
    }

    public function completeOnboardingTask(Request $request, OnboardingTask $task, EmployeeLifecycleService $service): RedirectResponse
    {
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:3000']]);
        $service->completeOnboardingTask($request->user(), $task, $data['notes'] ?? null);

        return back()->with('success', 'Onboarding task completed.');
    }

    public function completeOnboarding(Request $request, OnboardingCase $case, EmployeeLifecycleService $service): RedirectResponse
    {
        $completed = $service->completeOnboarding($request->user(), $case);

        return redirect()->route('employees.show', $completed->employee_id)
            ->with('success', 'Onboarding completed and employee access activated.');
    }

    public function applyMovement(Request $request, Employee $employee, EmployeeLifecycleService $service): RedirectResponse
    {
        $data = $request->validate([
            'movement_type' => ['required', Rule::in(['transfer', 'promotion', 'reassignment', 'acting_assignment'])],
            'effective_date' => ['required', 'date', 'before_or_equal:today'],
            'to_department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where(fn ($query) => $query->where('is_active', true)->where('is_routable', true)),
            ],
            'to_position_title' => ['nullable', 'string', 'max:255'],
            'new_supervisor_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'reason' => ['nullable', 'string', 'max:3000'],
        ]);

        $service->applyMovement($request->user(), $employee, $data);

        return back()->with('success', 'Employment movement applied and review tasks generated.');
    }

    public function completeMovementTask(Request $request, EmployeeMovementTask $task, EmployeeLifecycleService $service): RedirectResponse
    {
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:3000']]);
        $service->completeMovementTask($request->user(), $task, $data['notes'] ?? null);

        return back()->with('success', 'Movement review task completed.');
    }
}
