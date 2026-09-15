<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetEvent;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AssetAccountabilityService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function register(User $actor, array $data): Asset
    {
        $this->assertPropertyManager($actor);

        return DB::transaction(function () use ($actor, $data): Asset {
            if (Asset::query()->where('property_number', $data['property_number'])->exists()) {
                throw ValidationException::withMessages(['property_number' => 'That property number already exists.']);
            }

            $asset = Asset::query()->create([
                'property_number' => $data['property_number'],
                'qr_value' => $data['qr_value'] ?? 'TAL-ASSET-'.Str::upper(Str::random(12)),
                'category' => $data['category'],
                'description' => $data['description'],
                'serial_number' => $data['serial_number'] ?? null,
                'acquisition_date' => $data['acquisition_date'] ?? null,
                'acquisition_cost' => $data['acquisition_cost'] ?? null,
                'funding_source' => $data['funding_source'] ?? null,
                'supplier' => $data['supplier'] ?? null,
                'warranty_until' => $data['warranty_until'] ?? null,
                'current_department_id' => $data['current_department_id'] ?? Department::query()->where('code', 'GSO')->value('id'),
                'physical_location' => $data['physical_location'] ?? null,
                'condition' => $data['condition'] ?? 'good',
                'status' => 'available',
                'reconciliation_status' => 'unreconciled',
            ]);

            AssetEvent::query()->create([
                'asset_id' => $asset->id,
                'actor_user_id' => $actor->id,
                'event_type' => 'registered',
                'to_department_id' => $asset->current_department_id,
                'remarks' => 'Asset registered in the municipal property accountability ledger.',
                'created_at' => now(),
            ]);

            $this->audit->record(
                $actor,
                'property.asset.registered',
                'Registered asset '.$asset->property_number.' · '.$asset->description.'.',
                'allowed',
                Asset::class,
                $asset->id,
            );

            return $asset->fresh(['currentDepartment', 'accountableEmployee']);
        });
    }

    public function assign(User $actor, Asset $asset, Employee $employee, array $data = []): AssetAssignment
    {
        $this->assertPropertyManager($actor);

        return DB::transaction(function () use ($actor, $asset, $employee, $data): AssetAssignment {
            $lockedAsset = Asset::query()->lockForUpdate()->findOrFail($asset->id);
            $targetEmployee = Employee::query()->with('department')->lockForUpdate()->findOrFail($employee->id);

            if ($lockedAsset->status === 'disposed') {
                throw ValidationException::withMessages(['asset' => 'Disposed property cannot be assigned.']);
            }
            if (! in_array($targetEmployee->employment_status, ['active', 'onboarding'], true)) {
                throw ValidationException::withMessages(['employee_id' => 'Property can only be assigned to an active or onboarding employee.']);
            }

            $previous = AssetAssignment::query()
                ->where('asset_id', $lockedAsset->id)
                ->whereNull('returned_at')
                ->lockForUpdate()
                ->latest('assigned_at')
                ->first();

            if ($previous) {
                $previous->update([
                    'returned_at' => now(),
                    'condition_at_return' => $data['condition_at_issue'] ?? $lockedAsset->condition,
                ]);
            }

            $assignment = AssetAssignment::query()->create([
                'asset_id' => $lockedAsset->id,
                'employee_id' => $targetEmployee->id,
                'department_id' => $targetEmployee->department_id,
                'assignment_type' => $previous ? 'transfer' : 'issue',
                'reference_no' => $data['reference_no'] ?? null,
                'condition_at_issue' => $data['condition_at_issue'] ?? $lockedAsset->condition,
                'assigned_at' => now(),
                'created_by_user_id' => $actor->id,
            ]);

            $lockedAsset->update([
                'current_department_id' => $targetEmployee->department_id,
                'accountable_employee_id' => $targetEmployee->id,
                'physical_location' => $data['physical_location'] ?? $lockedAsset->physical_location,
                'par_reference' => $data['par_reference'] ?? $lockedAsset->par_reference,
                'ics_reference' => $data['ics_reference'] ?? $lockedAsset->ics_reference,
                'condition' => $data['condition_at_issue'] ?? $lockedAsset->condition,
                'status' => 'assigned',
            ]);

            AssetEvent::query()->create([
                'asset_id' => $lockedAsset->id,
                'actor_user_id' => $actor->id,
                'event_type' => $previous ? 'transferred' : 'issued',
                'from_department_id' => $previous?->department_id,
                'to_department_id' => $targetEmployee->department_id,
                'from_employee_id' => $previous?->employee_id,
                'to_employee_id' => $targetEmployee->id,
                'remarks' => $data['remarks'] ?? null,
                'metadata' => ['reference_no' => $assignment->reference_no],
                'created_at' => now(),
            ]);

            $this->audit->record(
                $actor,
                'property.asset.assigned',
                'Assigned '.$lockedAsset->property_number.' to '.$targetEmployee->employee_number.' · '.$targetEmployee->full_name.'.',
                'allowed',
                Asset::class,
                $lockedAsset->id,
            );

            return $assignment->fresh(['asset', 'employee.department']);
        });
    }

    public function returnAsset(User $actor, Asset $asset, array $data = []): Asset
    {
        $this->assertPropertyManager($actor);

        return DB::transaction(function () use ($actor, $asset, $data): Asset {
            $lockedAsset = Asset::query()->lockForUpdate()->findOrFail($asset->id);
            $assignment = AssetAssignment::query()
                ->where('asset_id', $lockedAsset->id)
                ->whereNull('returned_at')
                ->lockForUpdate()
                ->latest('assigned_at')
                ->first();

            if (! $assignment) {
                throw ValidationException::withMessages(['asset' => 'This asset has no active accountability assignment.']);
            }

            $condition = $data['condition_at_return'] ?? $lockedAsset->condition;
            $assignment->update([
                'returned_at' => now(),
                'condition_at_return' => $condition,
            ]);

            $gsoDepartmentId = Department::query()->where('code', 'GSO')->value('id');
            $lockedAsset->update([
                'current_department_id' => $gsoDepartmentId ?? $lockedAsset->current_department_id,
                'accountable_employee_id' => null,
                'condition' => $condition,
                'status' => 'available',
            ]);

            AssetEvent::query()->create([
                'asset_id' => $lockedAsset->id,
                'actor_user_id' => $actor->id,
                'event_type' => 'returned',
                'from_department_id' => $assignment->department_id,
                'to_department_id' => $lockedAsset->current_department_id,
                'from_employee_id' => $assignment->employee_id,
                'remarks' => $data['remarks'] ?? null,
                'created_at' => now(),
            ]);

            $this->audit->record(
                $actor,
                'property.asset.returned',
                'Returned '.$lockedAsset->property_number.' from employee accountability.',
                'allowed',
                Asset::class,
                $lockedAsset->id,
            );

            return $lockedAsset->fresh(['currentDepartment', 'accountableEmployee']);
        });
    }

    private function assertPropertyManager(User $actor): void
    {
        $actor->loadMissing('employee.department');
        $isGsoManager = $actor->employee?->department?->code === 'GSO'
            && $actor->isRole('department_head', 'department_staff');
        $allowed = $actor->isRole('system_admin') || $isGsoManager;

        if (! $allowed) {
            throw ValidationException::withMessages(['authorization' => 'Authorized GSO property personnel or system administration is required for property mutations.']);
        }
    }
}
