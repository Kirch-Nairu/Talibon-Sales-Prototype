<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDevelopmentRecord;
use App\Models\PerformanceRecord;
use App\Services\EmployeeDevelopmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class HrisDevelopmentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Hris/Development', [
            'employees' => Employee::query()
                ->whereIn('employment_status', ['active', 'onboarding'])
                ->with('department:id,code,name,short_name')
                ->orderBy('full_name')
                ->get(['id', 'employee_number', 'full_name', 'department_id', 'position_title', 'employment_status', 'contract_end_date']),
            'performanceRecords' => PerformanceRecord::query()
                ->with(['employee:id,employee_number,full_name,department_id', 'employee.department:id,code,name,short_name'])
                ->latest('period_end')
                ->limit(100)
                ->get(),
            'developmentRecords' => EmployeeDevelopmentRecord::query()
                ->with(['employee:id,employee_number,full_name,department_id', 'employee.department:id,code,name,short_name'])
                ->latest('id')
                ->limit(100)
                ->get(),
            'summary' => [
                'performance' => PerformanceRecord::query()->count(),
                'development' => EmployeeDevelopmentRecord::query()->count(),
                'expiringDevelopment' => EmployeeDevelopmentRecord::query()
                    ->where('status', 'active')
                    ->whereNotNull('expires_at')
                    ->whereBetween('expires_at', [today(), today()->addDays(120)])
                    ->count(),
                'contractsDue' => Employee::query()
                    ->whereIn('employment_status', ['active', 'onboarding'])
                    ->whereNotNull('contract_end_date')
                    ->whereBetween('contract_end_date', [today(), today()->addDays(120)])
                    ->count(),
            ],
        ]);
    }

    public function storePerformance(Request $request, Employee $employee, EmployeeDevelopmentService $service): RedirectResponse
    {
        $data = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'evaluator_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'rating_scale' => ['nullable', 'string', 'max:80'],
            'status' => ['required', Rule::in(['draft', 'recorded', 'reviewed', 'final'])],
            'summary' => ['nullable', 'string', 'max:5000'],
            'reviewed' => ['nullable', 'boolean'],
        ]);

        $service->recordPerformance($request->user(), $employee, $data);

        return back()->with('success', 'Performance record added to the governed employee profile.');
    }

    public function storeDevelopment(Request $request, Employee $employee, EmployeeDevelopmentService $service): RedirectResponse
    {
        $data = $request->validate([
            'record_type' => ['required', Rule::in(['training', 'certification', 'competency', 'eligibility'])],
            'title' => ['required', 'string', 'max:255'],
            'provider' => ['nullable', 'string', 'max:255'],
            'reference_no' => ['nullable', 'string', 'max:180'],
            'attained_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'expired', 'superseded', 'revoked'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $service->recordDevelopment($request->user(), $employee, $data);

        return back()->with('success', 'Training, competency, certification, or eligibility record saved.');
    }

    public function syncExpiryAlerts(Request $request, EmployeeDevelopmentService $service): RedirectResponse
    {
        $result = $service->syncExpiryAlerts($request->user());

        return back()->with('success', "Expiry monitoring synchronized: {$result['contracts']} contract review(s), {$result['development']} credential/eligibility record(s).");
    }
}
