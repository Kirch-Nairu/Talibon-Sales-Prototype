<?php

namespace App\Http\Controllers;

use App\Models\DtrPeriod;
use App\Models\PayrollPeriod;
use App\Services\DtrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DtrController extends Controller
{
    public function index(Request $request, DtrService $service): Response
    {
        $user = $request->user()->loadMissing('employee.department');
        abort_unless($user->employee, 403);

        $period = DtrPeriod::query()->latest('period_end')->first();
        $summaries = collect();
        $snapshot = null;

        if ($period) {
            $summaries = $period->summaries()
                ->where('employee_id', $user->employee->id)
                ->orderBy('work_date')
                ->get();
            $snapshot = $service->employeeSnapshot($period, $user->employee);
        }

        return Inertia::render('Hris/Dtr', [
            'period' => $period,
            'employee' => [
                'employee_number' => $user->employee->employee_number,
                'full_name' => $user->employee->full_name,
                'department' => $user->employee->department ? [
                    'name' => $user->employee->department->name,
                    'short_name' => $user->employee->department->short_name,
                ] : null,
            ],
            'summaries' => $summaries,
            'snapshot' => $snapshot,
            'isHrAdmin' => $user->isRole('system_admin', 'hr_officer')
                && ($user->isRole('system_admin') || $user->employee?->department?->code === 'HRMO'),
            'payrollPeriods' => PayrollPeriod::query()
                ->latest('period_end')
                ->limit(12)
                ->get(['id', 'label', 'period_start', 'period_end', 'status', 'dtr_period_id', 'calculation_mode']),
        ]);
    }

    public function generate(Request $request, DtrService $service): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        $service->generate($request->user(), $data);

        return back()->with('success', 'DTR period generated from recorded attendance and approved leave evidence.');
    }

    public function lock(Request $request, DtrPeriod $period, DtrService $service): RedirectResponse
    {
        $service->lock($request->user(), $period);

        return back()->with('success', 'DTR period locked for payroll-context linking.');
    }

    public function linkPayroll(Request $request, PayrollPeriod $payroll, DtrService $service): RedirectResponse
    {
        $data = $request->validate(['dtr_period_id' => ['required', 'integer', 'exists:dtr_periods,id']]);
        $dtr = DtrPeriod::query()->findOrFail((int) $data['dtr_period_id']);
        $service->linkPayroll($request->user(), $payroll, $dtr);

        return back()->with('success', 'DTR context linked to payroll without recalculating monetary values.');
    }
}
