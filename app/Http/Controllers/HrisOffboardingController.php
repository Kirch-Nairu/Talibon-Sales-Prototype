<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\OffboardingCase;
use App\Models\OffboardingTask;
use App\Services\EmployeeOffboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class HrisOffboardingController extends Controller
{
    public function index(): Response
    {
        $cases = OffboardingCase::query()
            ->with(['employee:id,employee_number,full_name,department_id,position_title,employment_status', 'employee.department:id,code,name,short_name'])
            ->withCount(['tasks as open_required_tasks_count' => fn ($query) => $query->where('is_required', true)->whereNotIn('status', ['completed', 'waived', 'not_required'])])
            ->latest('initiated_at')
            ->limit(100)
            ->get();

        return Inertia::render('Hris/Offboarding/Index', [
            'cases' => $cases,
            'employees' => Employee::query()->where('employment_status', 'active')->with('department:id,code,name,short_name')->orderBy('full_name')->get(['id', 'employee_number', 'full_name', 'department_id', 'position_title']),
            'today' => now()->toDateString(),
        ]);
    }

    public function store(Request $request, EmployeeOffboardingService $service): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'separation_type' => ['required', Rule::in(['resignation', 'retirement', 'end_of_contract', 'termination', 'transfer_out', 'other'])],
            'effective_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:4000'],
        ]);
        $employee = Employee::query()->findOrFail((int) $data['employee_id']);
        $case = $service->start($request->user(), $employee, $data);

        return redirect()->route('hris.offboarding.show', $case)->with('success', 'Offboarding case created with mandatory clearance controls.');
    }

    public function show(OffboardingCase $case): Response
    {
        $case->load(['employee:id,employee_number,full_name,user_id,department_id,position_title,employment_status,separation_date', 'employee.department:id,code,name,short_name', 'tasks.ownerDepartment:id,code,name,short_name', 'tasks.completer:id,name']);

        return Inertia::render('Hris/Offboarding/Show', [
            'case' => $case,
            'summary' => [
                'required' => $case->tasks->where('is_required', true)->count(),
                'completed' => $case->tasks->where('status', 'completed')->count(),
                'openRequired' => $case->tasks->where('is_required', true)->whereNotIn('status', ['completed', 'waived', 'not_required'])->count(),
            ],
        ]);
    }

    public function completeTask(Request $request, OffboardingTask $task, EmployeeOffboardingService $service): RedirectResponse
    {
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:3000']]);
        $service->completeTask($request->user(), $task, $data['notes'] ?? null);
        return back()->with('success', 'Clearance task completed.');
    }

    public function finalize(Request $request, OffboardingCase $case, EmployeeOffboardingService $service): RedirectResponse
    {
        $completed = $service->finalize($request->user(), $case);
        return redirect()->route('employees.show', $completed->employee_id)->with('success', 'Offboarding completed. Employment archived and portal access revoked.');
    }
}
