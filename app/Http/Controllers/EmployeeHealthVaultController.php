<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\EmployeeHealthVaultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeHealthVaultController extends Controller
{
    public function show(Request $request, Employee $employee, EmployeeHealthVaultService $service): Response
    {
        $employee->loadMissing('department:id,code,name,short_name');
        $records = $service->recordsFor($request->user(), $employee);

        return Inertia::render('Hris/HealthVault', [
            'employee' => [
                'id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'full_name' => $employee->full_name,
                'position_title' => $employee->position_title,
                'department' => $employee->department,
            ],
            'records' => $records->map(fn ($record): array => [
                'id' => $record->id,
                'record_type' => $record->record_type,
                'title' => $record->title,
                'issued_at' => $record->issued_at?->toDateString(),
                'valid_until' => $record->valid_until?->toDateString(),
                'status' => $record->status,
                'summary' => $record->summary,
                'restriction_notes' => $record->restriction_notes,
                'creator' => $record->creator?->name,
                'created_at' => $record->created_at?->toIso8601String(),
            ]),
            'canManage' => $service->canManageRecords($request->user(), $employee),
        ]);
    }

    public function store(Request $request, Employee $employee, EmployeeHealthVaultService $service): RedirectResponse
    {
        $data = $request->validate([
            'record_type' => ['required', Rule::in([
                'medical_certificate',
                'fit_to_work',
                'occupational_exam',
                'accommodation',
                'vaccination',
                'health_clearance',
                'other',
            ])],
            'title' => ['required', 'string', 'max:255'],
            'issued_at' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['active', 'expired', 'superseded', 'cleared'])],
            'summary' => ['nullable', 'string', 'max:5000'],
            'restriction_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if (! empty($data['issued_at']) && ! empty($data['valid_until']) && $data['valid_until'] < $data['issued_at']) {
            return back()->withErrors(['valid_until' => 'Valid-until date cannot be earlier than the issued date.'])->withInput();
        }

        $service->record($request->user(), $employee, $data);

        return back()->with('success', 'Restricted employment/occupational-health record saved.');
    }
}
