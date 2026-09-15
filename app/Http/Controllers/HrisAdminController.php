<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HrisAdminController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Hris/Admin', [
            'employees' => Employee::query()
                ->with(['user:id,name,email', 'department:id,code,name,short_name'])
                ->orderBy('employee_number')
                ->get(['id', 'employee_number', 'full_name', 'user_id', 'department_id', 'position_title', 'employment_status']),
            'pending' => LeaveRequest::query()
                ->where('status', 'pending')
                ->with([
                    'employee:id,employee_number,full_name,user_id,department_id,position_title',
                    'employee.user:id,name',
                    'employee.department:id,name,short_name',
                    'leaveType',
                ])
                ->oldest()
                ->get(),
        ]);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest, LeaveService $service): RedirectResponse
    {
        $data = $request->validate(['review_notes' => ['nullable', 'string', 'max:3000']]);
        $service->approve($request->user(), $leaveRequest, $data['review_notes'] ?? null);
        return back()->with('success', 'Leave request approved and applicable credits deducted.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest, LeaveService $service): RedirectResponse
    {
        $data = $request->validate(['review_notes' => ['nullable', 'string', 'max:3000']]);
        $service->reject($request->user(), $leaveRequest, $data['review_notes'] ?? null);
        return back()->with('success', 'Leave request rejected.');
    }
}
