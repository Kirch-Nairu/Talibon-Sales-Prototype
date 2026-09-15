<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Employee;
use App\Models\EmployeeDevelopmentRecord;
use App\Models\PerformanceRecord;
use App\Models\WorkflowTransaction;
use App\Services\EmployeeHealthVaultService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeProfileController extends Controller
{
    public function __invoke(Request $request, Employee $employee, EmployeeHealthVaultService $healthVault): Response
    {
        $viewer = $request->user()->loadMissing('employee.department');
        $employee->load(['department:id,code,name,short_name', 'supervisor:id,employee_number,full_name,position_title']);

        $isSelf = (int) ($viewer->employee?->id ?? 0) === (int) $employee->id;
        $isHrPrivileged = $viewer->isRole('system_admin', 'hr_officer');
        $isSameOfficeHead = $viewer->isRole('department_head')
            && (int) ($viewer->employee?->department_id ?? 0) === (int) $employee->department_id;
        $isExecutive = $viewer->isRole('mayor_approver', 'mayor_staff');
        $canViewPrivate = $isSelf || $isHrPrivileged;
        $canViewHrRecord = $isSelf || $isHrPrivileged;
        $canViewWorkContext = $isSelf || $isHrPrivileged || $isSameOfficeHead || $isExecutive;

        $documents = $canViewHrRecord
            ? Document::query()
                ->whereHas('links', fn ($query) => $query
                    ->where('linkable_type', Employee::class)
                    ->where('linkable_id', $employee->id))
                ->latest()
                ->limit(30)
                ->get(['id', 'public_id', 'title', 'document_type', 'classification', 'retention_code', 'created_at'])
                ->map(fn (Document $document): array => [
                    'id' => $document->id,
                    'public_id' => $document->public_id,
                    'title' => $document->title,
                    'document_type' => $document->document_type,
                    'classification' => $document->classification,
                    'retention_code' => $document->retention_code,
                    'created_at' => $document->created_at?->toIso8601String(),
                ])
            : collect();

        $activeAssignments = $canViewWorkContext
            ? WorkflowTransaction::query()
                ->where('assigned_employee_id', $employee->id)
                ->whereNotIn('status', ['approved', 'disapproved', 'closed'])
                ->latest()
                ->limit(10)
                ->get(['id', 'reference_no', 'title', 'priority', 'status', 'due_at'])
                ->map(fn (WorkflowTransaction $transaction): array => [
                    'id' => $transaction->id,
                    'reference_no' => $transaction->reference_no,
                    'title' => $transaction->title,
                    'priority' => $transaction->priority,
                    'status' => $transaction->status,
                    'due_at' => $transaction->due_at?->toIso8601String(),
                ])
            : collect();

        $performanceRecords = $canViewHrRecord
            ? PerformanceRecord::query()
                ->where('employee_id', $employee->id)
                ->latest('period_end')
                ->limit(12)
                ->get(['id', 'period_start', 'period_end', 'rating', 'rating_scale', 'status', 'summary', 'reviewed_at'])
            : collect();

        $developmentRecords = $canViewHrRecord
            ? EmployeeDevelopmentRecord::query()
                ->where('employee_id', $employee->id)
                ->latest('id')
                ->limit(30)
                ->get(['id', 'record_type', 'title', 'provider', 'reference_no', 'attained_at', 'expires_at', 'status'])
            : collect();

        $healthVaultAccess = $healthVault->canView($viewer, $employee);

        return Inertia::render('Employees/Show', [
            'employee' => [
                'id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'full_name' => $employee->full_name,
                'work_email' => $employee->work_email,
                'position_title' => $employee->position_title,
                'employment_status' => $employee->employment_status,
                'department' => $employee->department,
                'supervisor' => $employee->supervisor ? [
                    'id' => $employee->supervisor->id,
                    'employee_number' => $employee->supervisor->employee_number,
                    'full_name' => $employee->supervisor->full_name,
                    'position_title' => $employee->supervisor->position_title,
                ] : null,
            ],
            'employmentProfile' => $canViewHrRecord ? [
                'employment_type' => $employee->employment_type,
                'appointment_date' => $employee->appointment_date?->toDateString(),
                'employment_start_date' => $employee->employment_start_date?->toDateString(),
                'contract_end_date' => $employee->contract_end_date?->toDateString(),
                'biometric_external_id' => $employee->biometric_external_id,
            ] : null,
            'privateProfile' => $canViewPrivate ? [
                'date_of_birth' => $employee->date_of_birth?->toDateString(),
                'personal_email' => $employee->personal_email,
                'mobile_number' => $employee->mobile_number,
                'home_address' => $employee->home_address,
                'emergency_contact_name' => $employee->emergency_contact_name,
                'emergency_contact_relationship' => $employee->emergency_contact_relationship,
                'emergency_contact_phone' => $employee->emergency_contact_phone,
                'government_ids' => [
                    'gsis' => $employee->gsis_number,
                    'philhealth' => $employee->philhealth_number,
                    'pagibig' => $employee->pagibig_number,
                    'tin' => $employee->tin_number,
                ],
            ] : null,
            'documents' => $documents,
            'activeAssignments' => $activeAssignments,
            'performanceRecords' => $performanceRecords,
            'developmentRecords' => $developmentRecords,
            'permissions' => [
                'isSelf' => $isSelf,
                'canViewPrivate' => $canViewPrivate,
                'canViewHrRecord' => $canViewHrRecord,
                'canViewWorkContext' => $canViewWorkContext,
                'healthVaultAccess' => $healthVaultAccess,
                'canManageHealthAccess' => $healthVault->canManageAccess($viewer),
            ],
        ]);
    }
}
