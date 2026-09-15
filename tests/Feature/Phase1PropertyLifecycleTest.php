<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Models\AssetInventorySession;
use App\Models\AssetMaintenanceRecord;
use App\Models\User;
use App\Services\AssetAccountabilityService;
use App\Services\AssetLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Phase1PropertyLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_maintenance_preserves_history_and_restores_asset_state(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();
        $asset = app(AssetAccountabilityService::class)->register($admin, [
            'property_number' => 'M8-MAINT-001',
            'category' => 'ICT Equipment',
            'description' => 'Synthetic lifecycle laptop',
            'condition' => 'good',
        ]);

        $record = app(AssetLifecycleService::class)->startMaintenance($admin, $asset, [
            'maintenance_type' => 'repair',
            'issue_description' => 'Synthetic display issue.',
            'estimated_cost' => 2500,
        ]);
        $asset->refresh();
        $this->assertSame('under_repair', $asset->status);
        $this->assertSame('needs_repair', $asset->condition);

        app(AssetLifecycleService::class)->completeMaintenance($admin, $record, [
            'condition_after' => 'good',
            'actual_cost' => 2300,
        ]);
        $asset->refresh();
        $this->assertSame('available', $asset->status);
        $this->assertSame('good', $asset->condition);
        $this->assertDatabaseHas('asset_events', ['asset_id' => $asset->id, 'event_type' => 'maintenance_started']);
        $this->assertDatabaseHas('asset_events', ['asset_id' => $asset->id, 'event_type' => 'maintenance_completed']);
    }

    public function test_inventory_session_records_verification_and_location_mismatch(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();
        $asset = app(AssetAccountabilityService::class)->register($admin, [
            'property_number' => 'M8-INV-001',
            'category' => 'Office Equipment',
            'description' => 'Synthetic inventory monitor',
            'physical_location' => 'GSO Storage A',
        ]);
        $service = app(AssetLifecycleService::class);
        $session = $service->startInventory($admin, ['title' => 'Phase 1 inventory validation']);

        $scan = $service->scanInventory($admin, $session, $asset, [
            'observed_location' => 'Mayor Annex',
            'observed_condition' => 'good',
        ]);
        $this->assertSame('location_mismatch', $scan->verification_status);
        $this->assertDatabaseHas('asset_inventory_scans', [
            'asset_inventory_session_id' => $session->id,
            'asset_id' => $asset->id,
            'verification_status' => 'location_mismatch',
        ]);

        $closed = $service->closeInventory($admin, $session);
        $this->assertSame('closed', $closed->status);

        $this->expectException(ValidationException::class);
        $service->scanInventory($admin, $session, $asset, ['observed_location' => 'GSO Storage A']);
    }

    public function test_reconciliation_and_disposal_require_explicit_authority_and_preserve_events(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();
        $asset = app(AssetAccountabilityService::class)->register($admin, [
            'property_number' => 'M8-DISP-001',
            'category' => 'Office Equipment',
            'description' => 'Synthetic unserviceable printer',
            'condition' => 'unserviceable',
        ]);
        $service = app(AssetLifecycleService::class);

        $service->reconcile($admin, $asset, [
            'status' => 'reconciled',
            'accounting_reference' => 'ACC-TEST-001',
            'book_value' => 1000,
        ]);
        $asset->refresh();
        $this->assertSame('reconciled', $asset->reconciliation_status);

        $disposal = $service->recommendDisposal($admin, $asset, [
            'method' => 'destruction',
            'reason' => 'Beyond economical repair in synthetic test.',
        ]);
        $asset->refresh();
        $this->assertSame('for_disposal', $asset->status);

        try {
            $service->decideDisposal($admin, $disposal, ['decision' => 'approved']);
            $this->fail('Disposal was approved without an authority reference.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('authority_reference', $exception->errors());
        }

        $approved = $service->decideDisposal($admin, $disposal, [
            'decision' => 'approved',
            'method' => 'destruction',
            'authority_reference' => 'AUTH-TEST-001',
        ]);
        $this->assertSame('approved', $approved->status);
        $this->assertSame('disposed', Asset::query()->findOrFail($asset->id)->status);
        $this->assertDatabaseHas('asset_events', ['asset_id' => $asset->id, 'event_type' => 'disposal_approved']);
    }

    public function test_normal_employee_cannot_mutate_property_lifecycle(): void
    {
        $this->seed();
        $employee = User::query()->where('email', 'employee@talibon.demo')->firstOrFail();
        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();
        $asset = app(AssetAccountabilityService::class)->register($admin, [
            'property_number' => 'M8-AUTH-001',
            'category' => 'ICT Equipment',
            'description' => 'Synthetic authorization asset',
        ]);

        $this->expectException(ValidationException::class);
        app(AssetLifecycleService::class)->startMaintenance($employee, $asset, [
            'maintenance_type' => 'inspection',
            'issue_description' => 'Unauthorized attempt.',
        ]);
    }
}
