<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HrisController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user()->loadMissing('employee.department');
        $employee = $user->employee;
        abort_unless($employee, 403);

        $employee->load([
            'leaveCreditAccounts.leaveType',
            'leaveRequests' => fn ($query) => $query->with('leaveType')->latest()->limit(10),
            'attendanceLogs' => fn ($query) => $query->latest('occurred_at')->limit(10),
        ]);

        $accounts = $employee->leaveCreditAccounts->keyBy('leave_type_id');
        $leaveTypes = LeaveType::query()->where('is_active', true)->orderBy('name')->get()->map(function (LeaveType $type) use ($accounts): array {
            $account = $accounts->get($type->id);
            return [
                'id' => $type->id,
                'code' => $type->code,
                'name' => $type->name,
                'tracks_balance' => $type->tracks_balance,
                'balance' => $account?->balance,
                'entitlement_label' => $type->entitlement_label,
            ];
        });

        $lastAttendance = AttendanceLog::query()->latest('occurred_at')->first();

        return Inertia::render('Hris/Dashboard', [
            'employee' => $employee->only(['id', 'employee_number', 'full_name', 'position_title']),
            'department' => $employee->department,
            'leaveTypes' => $leaveTypes,
            'requests' => $employee->leaveRequests,
            'attendance' => $employee->attendanceLogs,
            'canAdmin' => $user->isRole('system_admin', 'hr_officer'),
            'attendanceIntegration' => [
                'source' => 'Prototype Biometric 01',
                'status' => 'simulation',
                'lastEvent' => $lastAttendance?->occurred_at?->toIso8601String(),
                'events' => AttendanceLog::query()->count(),
                'employeesRepresented' => AttendanceLog::query()->distinct('employee_id')->count('employee_id'),
            ],
        ]);
    }
}
