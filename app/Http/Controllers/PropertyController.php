<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\AssetAccountabilityService;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function index(Request $request): Response
    {
        $user = $this->authorizedViewer($request);
        $assets = Asset::query()
            ->with([
                'currentDepartment:id,code,name,short_name',
                'accountableEmployee:id,employee_number,full_name,department_id,position_title',
            ])
            ->orderBy('property_number')
            ->limit(500)
            ->get();

        return Inertia::render('Property/Index', [
            'assets' => $assets,
            'employees' => Employee::query()
                ->whereIn('employment_status', ['active', 'onboarding'])
                ->with('department:id,code,name,short_name')
                ->orderBy('full_name')
                ->get(['id', 'employee_number', 'full_name', 'department_id', 'position_title', 'employment_status']),
            'departments' => Department::query()
                ->activeRoutable()
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'short_name']),
            'canManage' => $this->canManage($user),
            'summary' => [
                'total' => $assets->count(),
                'assigned' => $assets->where('status', 'assigned')->count(),
                'available' => $assets->where('status', 'available')->count(),
                'needsAttention' => $assets->whereIn('condition', ['needs_repair', 'unserviceable'])->count(),
            ],
        ]);
    }

    public function store(Request $request, AssetAccountabilityService $service): RedirectResponse
    {
        $this->authorizedManager($request);
        $data = $request->validate([
            'property_number' => ['required', 'string', 'max:120'],
            'qr_value' => ['nullable', 'string', 'max:180'],
            'category' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:180'],
            'acquisition_date' => ['nullable', 'date'],
            'acquisition_cost' => ['nullable', 'numeric', 'min:0'],
            'funding_source' => ['nullable', 'string', 'max:180'],
            'supplier' => ['nullable', 'string', 'max:180'],
            'warranty_until' => ['nullable', 'date'],
            'current_department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where(fn ($query) => $query->where('is_active', true)->where('is_routable', true)),
            ],
            'physical_location' => ['nullable', 'string', 'max:180'],
            'condition' => ['nullable', Rule::in(['good', 'fair', 'needs_repair', 'unserviceable'])],
        ]);

        $asset = $service->register($request->user(), $data);

        return redirect()->route('property.index')->with('success', $asset->property_number.' registered.');
    }

    public function assign(Request $request, Asset $asset, AssetAccountabilityService $service): RedirectResponse
    {
        $this->authorizedManager($request);
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'reference_no' => ['nullable', 'string', 'max:160'],
            'par_reference' => ['nullable', 'string', 'max:160'],
            'ics_reference' => ['nullable', 'string', 'max:160'],
            'physical_location' => ['nullable', 'string', 'max:180'],
            'condition_at_issue' => ['nullable', Rule::in(['good', 'fair', 'needs_repair'])],
            'remarks' => ['nullable', 'string', 'max:3000'],
        ]);

        $employee = Employee::query()->findOrFail((int) $data['employee_id']);
        $service->assign($request->user(), $asset, $employee, $data);

        return back()->with('success', 'Property accountability assignment recorded.');
    }

    public function returnAsset(Request $request, Asset $asset, AssetAccountabilityService $service): RedirectResponse
    {
        $this->authorizedManager($request);
        $data = $request->validate([
            'condition_at_return' => ['nullable', Rule::in(['good', 'fair', 'needs_repair', 'unserviceable'])],
            'remarks' => ['nullable', 'string', 'max:3000'],
        ]);

        $service->returnAsset($request->user(), $asset, $data);

        return back()->with('success', 'Property returned to municipal accountability custody.');
    }

    private function authorizedViewer(Request $request): User
    {
        $user = $request->user()->loadMissing('employee.department');
        $officeCode = $user->employee?->department?->code;
        $allowed = $user->isRole('system_admin', 'mayor_approver', 'mayor_staff')
            || in_array($officeCode, ['GSO', 'ACCOUNTING'], true)
            || ($user->isRole('hr_officer') && $officeCode === 'HRMO');

        if (! $allowed) {
            $this->audit->record($user, 'property.access', 'Attempted access to the restricted property accountability workspace.', 'denied', 'Property');
            abort(403, 'Property accountability is restricted to authorized municipal personnel.');
        }

        return $user;
    }

    private function authorizedManager(Request $request): User
    {
        $user = $this->authorizedViewer($request);
        if (! $this->canManage($user)) {
            $this->audit->record($user, 'property.mutate', 'Attempted unauthorized property-accountability mutation.', 'denied', 'Property');
            abort(403, 'Only authorized GSO property personnel may change property accountability records.');
        }

        return $user;
    }

    private function canManage(User $user): bool
    {
        return $user->isRole('system_admin')
            || ($user->employee?->department?->code === 'GSO' && $user->isRole('department_head', 'department_staff'));
    }
}
