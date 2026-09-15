<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeHealthAccessGrant;
use App\Models\User;
use App\Services\EmployeeHealthVaultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HealthAccessController extends Controller
{
    public function index(Request $request, EmployeeHealthVaultService $service): Response
    {
        $actor = $request->user();
        abort_unless($service->canManageAccess($actor), 403);

        return Inertia::render('Hris/HealthAccess', [
            'grants' => EmployeeHealthAccessGrant::query()
                ->with(['user:id,name,email', 'employee:id,employee_number,full_name', 'granter:id,name'])
                ->latest('granted_at')
                ->limit(150)
                ->get(),
            'users' => User::query()
                ->where('is_active', true)
                ->with('employee:id,user_id,employee_number,full_name,department_id')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role']),
            'employees' => Employee::query()
                ->whereIn('employment_status', ['active', 'onboarding'])
                ->orderBy('full_name')
                ->get(['id', 'employee_number', 'full_name', 'position_title']),
        ]);
    }

    public function store(Request $request, EmployeeHealthVaultService $service): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'can_manage' => ['nullable', 'boolean'],
            'purpose' => ['required', 'string', 'max:2000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $recipient = User::query()->findOrFail((int) $data['user_id']);
        $employee = ! empty($data['employee_id']) ? Employee::query()->findOrFail((int) $data['employee_id']) : null;
        $service->grantAccess($request->user(), $recipient, $employee, $data);

        return back()->with('success', 'Explicit employee health-vault access grant recorded.');
    }

    public function revoke(Request $request, EmployeeHealthAccessGrant $grant, EmployeeHealthVaultService $service): RedirectResponse
    {
        $service->revokeAccess($request->user(), $grant);

        return back()->with('success', 'Health-vault access grant revoked.');
    }
}
