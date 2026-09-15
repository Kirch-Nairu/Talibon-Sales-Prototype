<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Models\AssetInventorySession;
use App\Models\AssetMaintenanceRecord;
use App\Models\Department;
use App\Models\User;
use App\Services\AssetLifecycleService;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PropertyLifecycleController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function index(Request $request): Response
    {
        $user = $this->authorizedViewer($request);

        return Inertia::render('Property/Lifecycle', [
            'assets' => Asset::query()
                ->with(['currentDepartment:id,code,name,short_name', 'accountableEmployee:id,employee_number,full_name'])
                ->orderBy('property_number')
                ->limit(500)
                ->get(),
            'inventorySessions' => AssetInventorySession::query()
                ->with('department:id,code,name,short_name')
                ->withCount('scans')
                ->latest('inventory_date')
                ->limit(50)
                ->get(),
            'openMaintenance' => AssetMaintenanceRecord::query()
                ->with('asset:id,property_number,description')
                ->whereIn('status', ['open', 'in_progress'])
                ->latest()
                ->limit(50)
                ->get(),
            'pendingDisposals' => AssetDisposal::query()
                ->with('asset:id,property_number,description,status')
                ->where('status', 'recommended')
                ->latest('recommended_at')
                ->limit(50)
                ->get(),
            'departments' => Department::query()->activeRoutable()->orderBy('name')->get(['id', 'code', 'name', 'short_name']),
            'canManage' => $this->canManage($user),
            'canReconcile' => $this->canReconcile($user),
        ]);
    }

    public function startMaintenance(Request $request, Asset $asset, AssetLifecycleService $service): RedirectResponse
    {
        $data = $request->validate([
            'maintenance_type' => ['required', Rule::in(['preventive', 'corrective', 'repair', 'inspection', 'other'])],
            'issue_description' => ['required', 'string', 'max:3000'],
            'service_provider' => ['nullable', 'string', 'max:180'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'started_on' => ['nullable', 'date'],
        ]);
        $service->startMaintenance($request->user(), $asset, $data);
        return back()->with('success', 'Maintenance record started.');
    }

    public function completeMaintenance(Request $request, AssetMaintenanceRecord $record, AssetLifecycleService $service): RedirectResponse
    {
        $data = $request->validate([
            'condition_after' => ['required', Rule::in(['good', 'fair', 'needs_repair', 'unserviceable'])],
            'actual_cost' => ['nullable', 'numeric', 'min:0'],
            'completed_on' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:3000'],
        ]);
        $service->completeMaintenance($request->user(), $record, $data);
        return back()->with('success', 'Maintenance record completed.');
    }

    public function startInventory(Request $request, AssetLifecycleService $service): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'inventory_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
        $service->startInventory($request->user(), $data);
        return back()->with('success', 'Inventory session started.');
    }

    public function scanInventory(Request $request, AssetInventorySession $session, Asset $asset, AssetLifecycleService $service): RedirectResponse
    {
        $data = $request->validate([
            'scan_value' => ['required', 'string', 'max:180'],
            'observed_location' => ['nullable', 'string', 'max:180'],
            'observed_condition' => ['nullable', Rule::in(['good', 'fair', 'needs_repair', 'unserviceable'])],
            'verification_status' => ['nullable', Rule::in(['verified', 'missing', 'location_mismatch', 'condition_mismatch'])],
            'remarks' => ['nullable', 'string', 'max:3000'],
        ]);

        if ($asset->status === 'disposed') {
            throw ValidationException::withMessages(['asset' => 'Disposed property cannot be verified in an active physical inventory.']);
        }

        if (! hash_equals((string) $asset->qr_value, (string) $data['scan_value'])) {
            $this->audit->record(
                $request->user(),
                'property.inventory.reference_mismatch',
                'Rejected inventory reference that did not match '.$asset->property_number.'.',
                'denied',
                Asset::class,
                $asset->id,
            );
            throw ValidationException::withMessages(['scan_value' => 'The scanned/reference value does not match the selected property record.']);
        }

        $service->scanInventory($request->user(), $session, $asset, $data);
        return back()->with('success', 'Inventory observation recorded.');
    }

    public function closeInventory(Request $request, AssetInventorySession $session, AssetLifecycleService $service): RedirectResponse
    {
        $service->closeInventory($request->user(), $session);
        return back()->with('success', 'Inventory session closed.');
    }

    public function reconcile(Request $request, Asset $asset, AssetLifecycleService $service): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['reconciled', 'variance', 'pending_review'])],
            'accounting_reference' => ['nullable', 'string', 'max:180'],
            'book_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
        $service->reconcile($request->user(), $asset, $data);
        return back()->with('success', 'Accounting reconciliation recorded.');
    }

    public function recommendDisposal(Request $request, Asset $asset, AssetLifecycleService $service): RedirectResponse
    {
        $data = $request->validate([
            'method' => ['nullable', Rule::in(['sale', 'transfer', 'destruction', 'donation', 'other'])],
            'reason' => ['required', 'string', 'max:3000'],
        ]);
        $service->recommendDisposal($request->user(), $asset, $data);
        return back()->with('success', 'Asset recommended for disposal.');
    }

    public function decideDisposal(Request $request, AssetDisposal $disposal, AssetLifecycleService $service): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'method' => ['nullable', Rule::in(['sale', 'transfer', 'destruction', 'donation', 'other'])],
            'authority_reference' => ['required', 'string', 'max:180'],
            'proceeds' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:3000'],
        ]);
        $service->decideDisposal($request->user(), $disposal, $data);
        return back()->with('success', 'Disposal decision recorded.');
    }

    private function authorizedViewer(Request $request): User
    {
        $user = $request->user()->loadMissing('employee.department');
        $code = $user->employee?->department?->code;
        $allowed = $user->isRole('system_admin', 'mayor_approver', 'mayor_staff')
            || in_array($code, ['GSO', 'ACCOUNTING', 'INTERNAL_AUDIT'], true);

        if (! $allowed) {
            $this->audit->record($user, 'property.lifecycle.access', 'Attempted access to property lifecycle controls.', 'denied', 'Property');
            abort(403, 'Property lifecycle is restricted to authorized municipal personnel.');
        }
        return $user;
    }

    private function canManage(User $user): bool
    {
        return $user->isRole('system_admin')
            || ($user->employee?->department?->code === 'GSO' && $user->isRole('department_head', 'department_staff'));
    }

    private function canReconcile(User $user): bool
    {
        return $user->isRole('system_admin')
            || ($user->employee?->department?->code === 'ACCOUNTING' && $user->isRole('department_head', 'department_staff'));
    }
}
