<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetInventorySession;
use App\Models\User;
use App\Services\AssetAccountabilityService;
use App\Services\AssetLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1PropertyInventoryIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_rejects_a_reference_that_does_not_match_selected_asset(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();
        $asset = app(AssetAccountabilityService::class)->register($admin, [
            'property_number' => 'M8-INTEGRITY-001',
            'qr_value' => 'M8-QR-INTEGRITY-001',
            'category' => 'ICT Equipment',
            'description' => 'Inventory integrity test asset',
        ]);
        $session = app(AssetLifecycleService::class)->startInventory($admin, ['title' => 'Integrity test inventory']);

        $this->actingAs($admin)
            ->from('/property/lifecycle')
            ->post('/property/inventory/'.$session->id.'/assets/'.$asset->id.'/scan', [
                'scan_value' => 'WRONG-REFERENCE',
                'observed_condition' => 'good',
            ])
            ->assertRedirect('/property/lifecycle')
            ->assertSessionHasErrors('scan_value');

        $this->assertDatabaseMissing('asset_inventory_scans', [
            'asset_inventory_session_id' => $session->id,
            'asset_id' => $asset->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $admin->id,
            'action' => 'property.inventory.reference_mismatch',
            'outcome' => 'denied',
        ]);
    }

    public function test_inventory_accepts_exact_reference_and_disposed_asset_is_rejected(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();
        $asset = app(AssetAccountabilityService::class)->register($admin, [
            'property_number' => 'M8-INTEGRITY-002',
            'qr_value' => 'M8-QR-INTEGRITY-002',
            'category' => 'Office Equipment',
            'description' => 'Exact reference test asset',
        ]);
        $session = app(AssetLifecycleService::class)->startInventory($admin, ['title' => 'Exact reference inventory']);

        $this->actingAs($admin)->post('/property/inventory/'.$session->id.'/assets/'.$asset->id.'/scan', [
            'scan_value' => 'M8-QR-INTEGRITY-002',
            'observed_condition' => 'good',
        ])->assertRedirect();

        $this->assertDatabaseHas('asset_inventory_scans', [
            'asset_inventory_session_id' => $session->id,
            'asset_id' => $asset->id,
            'scan_value' => 'M8-QR-INTEGRITY-002',
        ]);

        $disposed = Asset::query()->findOrFail($asset->id);
        $disposed->update(['status' => 'disposed']);
        $second = AssetInventorySession::query()->create([
            'session_code' => 'INV-INTEGRITY-SECOND',
            'title' => 'Disposed rejection inventory',
            'status' => 'open',
            'inventory_date' => now()->toDateString(),
            'started_by_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->from('/property/lifecycle')
            ->post('/property/inventory/'.$second->id.'/assets/'.$disposed->id.'/scan', [
                'scan_value' => 'M8-QR-INTEGRITY-002',
                'observed_condition' => 'good',
            ])
            ->assertRedirect('/property/lifecycle')
            ->assertSessionHasErrors('asset');
    }
}
