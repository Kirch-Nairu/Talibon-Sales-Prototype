<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Models\AssetEvent;
use App\Models\AssetInventoryScan;
use App\Models\AssetInventorySession;
use App\Models\AssetMaintenanceRecord;
use App\Models\AssetReconciliation;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AssetLifecycleService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function startMaintenance(User $actor, Asset $asset, array $data): AssetMaintenanceRecord
    {
        $this->assertPropertyManager($actor);

        return DB::transaction(function () use ($actor, $asset, $data): AssetMaintenanceRecord {
            $locked = Asset::query()->lockForUpdate()->findOrFail($asset->id);
            if ($locked->status === 'disposed') {
                throw ValidationException::withMessages(['asset' => 'Disposed property cannot enter maintenance.']);
            }
            if (AssetMaintenanceRecord::query()->where('asset_id', $locked->id)->whereIn('status', ['open', 'in_progress'])->exists()) {
                throw ValidationException::withMessages(['maintenance' => 'This asset already has an open maintenance record.']);
            }

            $record = AssetMaintenanceRecord::query()->create([
                'asset_id' => $locked->id,
                'maintenance_type' => $data['maintenance_type'],
                'status' => 'in_progress',
                'issue_description' => $data['issue_description'],
                'service_provider' => $data['service_provider'] ?? null,
                'estimated_cost' => $data['estimated_cost'] ?? null,
                'started_on' => $data['started_on'] ?? now()->toDateString(),
                'condition_before' => $locked->condition,
                'created_by_user_id' => $actor->id,
            ]);

            $locked->update(['status' => 'under_repair', 'condition' => 'needs_repair']);
            $this->event($locked, $actor, 'maintenance_started', $data['issue_description'], ['maintenance_record_id' => $record->id]);
            $this->audit->record($actor, 'property.maintenance.started', 'Started maintenance for '.$locked->property_number.'.', 'allowed', Asset::class, $locked->id);

            return $record->fresh('asset');
        });
    }

    public function completeMaintenance(User $actor, AssetMaintenanceRecord $record, array $data): AssetMaintenanceRecord
    {
        $this->assertPropertyManager($actor);

        return DB::transaction(function () use ($actor, $record, $data): AssetMaintenanceRecord {
            $lockedRecord = AssetMaintenanceRecord::query()->lockForUpdate()->findOrFail($record->id);
            if ($lockedRecord->status === 'completed') {
                return $lockedRecord;
            }
            $asset = Asset::query()->lockForUpdate()->findOrFail($lockedRecord->asset_id);
            $condition = $data['condition_after'];

            $lockedRecord->update([
                'status' => 'completed',
                'actual_cost' => $data['actual_cost'] ?? null,
                'completed_on' => $data['completed_on'] ?? now()->toDateString(),
                'condition_after' => $condition,
            ]);
            $asset->update([
                'condition' => $condition,
                'status' => $asset->accountable_employee_id ? 'assigned' : ($condition === 'unserviceable' ? 'for_disposal' : 'available'),
            ]);

            $this->event($asset, $actor, 'maintenance_completed', $data['remarks'] ?? null, ['maintenance_record_id' => $lockedRecord->id]);
            $this->audit->record($actor, 'property.maintenance.completed', 'Completed maintenance for '.$asset->property_number.'.', 'allowed', Asset::class, $asset->id);

            return $lockedRecord->fresh('asset');
        });
    }

    public function startInventory(User $actor, array $data): AssetInventorySession
    {
        $this->assertPropertyManager($actor);

        return DB::transaction(function () use ($actor, $data): AssetInventorySession {
            do {
                $code = 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
            } while (AssetInventorySession::query()->where('session_code', $code)->exists());

            $session = AssetInventorySession::query()->create([
                'session_code' => $code,
                'title' => $data['title'],
                'department_id' => $data['department_id'] ?? null,
                'status' => 'open',
                'inventory_date' => $data['inventory_date'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'started_by_user_id' => $actor->id,
            ]);

            $this->audit->record($actor, 'property.inventory.started', 'Started inventory session '.$code.'.', 'allowed', AssetInventorySession::class, $session->id);
            return $session->fresh('department');
        });
    }

    public function scanInventory(User $actor, AssetInventorySession $session, Asset $asset, array $data): AssetInventoryScan
    {
        $this->assertPropertyManager($actor);

        return DB::transaction(function () use ($actor, $session, $asset, $data): AssetInventoryScan {
            $lockedSession = AssetInventorySession::query()->lockForUpdate()->findOrFail($session->id);
            if ($lockedSession->status !== 'open') {
                throw ValidationException::withMessages(['inventory' => 'Only an open inventory session can accept scans.']);
            }
            $lockedAsset = Asset::query()->lockForUpdate()->findOrFail($asset->id);
            if ($lockedSession->department_id && (int) $lockedSession->department_id !== (int) $lockedAsset->current_department_id) {
                throw ValidationException::withMessages(['asset' => 'The asset is outside the office scope of this inventory session.']);
            }

            $observedLocation = $data['observed_location'] ?? $lockedAsset->physical_location;
            $observedCondition = $data['observed_condition'] ?? $lockedAsset->condition;
            $status = $data['verification_status'] ?? 'verified';
            if ($observedLocation && $lockedAsset->physical_location && $observedLocation !== $lockedAsset->physical_location && $status === 'verified') {
                $status = 'location_mismatch';
            }

            $scan = AssetInventoryScan::query()->updateOrCreate(
                ['asset_inventory_session_id' => $lockedSession->id, 'asset_id' => $lockedAsset->id],
                [
                    'scan_value' => $data['scan_value'] ?? $lockedAsset->qr_value,
                    'observed_location' => $observedLocation,
                    'observed_condition' => $observedCondition,
                    'verification_status' => $status,
                    'remarks' => $data['remarks'] ?? null,
                    'scanned_by_user_id' => $actor->id,
                    'scanned_at' => now(),
                ],
            );

            if ($status === 'verified') {
                $lockedAsset->update(['physical_location' => $observedLocation, 'condition' => $observedCondition]);
            }
            if (in_array($status, ['missing', 'location_mismatch', 'condition_mismatch'], true)) {
                $lockedAsset->update(['status' => $status === 'missing' ? 'missing' : $lockedAsset->status]);
            }

            $this->event($lockedAsset, $actor, 'inventory_scanned', $data['remarks'] ?? null, ['session_id' => $lockedSession->id, 'verification_status' => $status]);
            return $scan->fresh(['asset.currentDepartment', 'session']);
        });
    }

    public function closeInventory(User $actor, AssetInventorySession $session): AssetInventorySession
    {
        $this->assertPropertyManager($actor);

        return DB::transaction(function () use ($actor, $session): AssetInventorySession {
            $locked = AssetInventorySession::query()->lockForUpdate()->findOrFail($session->id);
            if ($locked->status === 'closed') {
                return $locked;
            }
            $locked->update(['status' => 'closed', 'closed_by_user_id' => $actor->id, 'closed_at' => now()]);
            $this->audit->record($actor, 'property.inventory.closed', 'Closed inventory session '.$locked->session_code.'.', 'allowed', AssetInventorySession::class, $locked->id);
            return $locked->fresh(['department', 'scans']);
        });
    }

    public function reconcile(User $actor, Asset $asset, array $data): AssetReconciliation
    {
        $this->assertReconciler($actor);

        return DB::transaction(function () use ($actor, $asset, $data): AssetReconciliation {
            $locked = Asset::query()->lockForUpdate()->findOrFail($asset->id);
            $record = AssetReconciliation::query()->create([
                'asset_id' => $locked->id,
                'status' => $data['status'],
                'accounting_reference' => $data['accounting_reference'] ?? null,
                'book_value' => $data['book_value'] ?? null,
                'notes' => $data['notes'] ?? null,
                'reconciled_by_user_id' => $actor->id,
                'reconciled_at' => now(),
            ]);
            $locked->update(['reconciliation_status' => $data['status']]);
            $this->event($locked, $actor, 'accounting_reconciled', $data['notes'] ?? null, ['reconciliation_id' => $record->id, 'status' => $data['status']]);
            $this->audit->record($actor, 'property.reconciled', 'Recorded Accounting reconciliation for '.$locked->property_number.'.', 'allowed', Asset::class, $locked->id);
            return $record->fresh('asset');
        });
    }

    public function recommendDisposal(User $actor, Asset $asset, array $data): AssetDisposal
    {
        $this->assertPropertyManager($actor);

        return DB::transaction(function () use ($actor, $asset, $data): AssetDisposal {
            $locked = Asset::query()->lockForUpdate()->findOrFail($asset->id);
            if ($locked->accountable_employee_id) {
                throw ValidationException::withMessages(['asset' => 'Return the asset from employee accountability before disposal processing.']);
            }
            if ($locked->status === 'disposed') {
                throw ValidationException::withMessages(['asset' => 'This asset is already disposed.']);
            }
            if (AssetDisposal::query()->where('asset_id', $locked->id)->whereIn('status', ['recommended', 'approved'])->exists()) {
                throw ValidationException::withMessages(['disposal' => 'An active disposal record already exists for this asset.']);
            }

            $record = AssetDisposal::query()->create([
                'asset_id' => $locked->id,
                'status' => 'recommended',
                'method' => $data['method'] ?? null,
                'reason' => $data['reason'],
                'recommended_by_user_id' => $actor->id,
                'recommended_at' => now(),
            ]);
            $locked->update(['status' => 'for_disposal']);
            $this->event($locked, $actor, 'disposal_recommended', $data['reason'], ['disposal_id' => $record->id]);
            return $record->fresh('asset');
        });
    }

    public function decideDisposal(User $actor, AssetDisposal $disposal, array $data): AssetDisposal
    {
        $this->assertPropertyManager($actor);

        return DB::transaction(function () use ($actor, $disposal, $data): AssetDisposal {
            $locked = AssetDisposal::query()->lockForUpdate()->findOrFail($disposal->id);
            if ($locked->status !== 'recommended') {
                throw ValidationException::withMessages(['disposal' => 'Only a recommended disposal can receive a decision.']);
            }
            if (empty($data['authority_reference'])) {
                throw ValidationException::withMessages(['authority_reference' => 'An authority/reference document is required to record a disposal decision.']);
            }
            $asset = Asset::query()->lockForUpdate()->findOrFail($locked->asset_id);
            if ($asset->accountable_employee_id) {
                throw ValidationException::withMessages(['asset' => 'Asset accountability must be cleared before a disposal decision is finalized.']);
            }

            $decision = $data['decision'];
            $locked->update([
                'status' => $decision,
                'method' => $data['method'] ?? $locked->method,
                'authority_reference' => $data['authority_reference'],
                'proceeds' => $data['proceeds'] ?? null,
                'decided_by_user_id' => $actor->id,
                'decided_at' => now(),
            ]);
            $asset->update(['status' => $decision === 'approved' ? 'disposed' : 'available']);
            $this->event($asset, $actor, 'disposal_'.$decision, $data['remarks'] ?? null, ['disposal_id' => $locked->id, 'authority_reference' => $data['authority_reference']]);
            $this->audit->record($actor, 'property.disposal.'.$decision, 'Recorded disposal decision for '.$asset->property_number.'.', 'allowed', Asset::class, $asset->id);
            return $locked->fresh('asset');
        });
    }

    private function event(Asset $asset, User $actor, string $type, ?string $remarks = null, array $metadata = []): void
    {
        AssetEvent::query()->create([
            'asset_id' => $asset->id,
            'actor_user_id' => $actor->id,
            'event_type' => $type,
            'to_department_id' => $asset->current_department_id,
            'to_employee_id' => $asset->accountable_employee_id,
            'remarks' => $remarks,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    private function assertPropertyManager(User $actor): void
    {
        $actor->loadMissing('employee.department');
        $isGso = $actor->employee?->department?->code === 'GSO' && $actor->isRole('department_head', 'department_staff');
        if (! $actor->isRole('system_admin') && ! $isGso) {
            throw ValidationException::withMessages(['authorization' => 'Authorized GSO property personnel or system administration is required.']);
        }
    }

    private function assertReconciler(User $actor): void
    {
        $actor->loadMissing('employee.department');
        $isAccounting = $actor->employee?->department?->code === 'ACCOUNTING' && $actor->isRole('department_head', 'department_staff');
        if (! $actor->isRole('system_admin') && ! $isAccounting) {
            throw ValidationException::withMessages(['authorization' => 'Authorized Accounting personnel or system administration is required for reconciliation.']);
        }
    }
}
