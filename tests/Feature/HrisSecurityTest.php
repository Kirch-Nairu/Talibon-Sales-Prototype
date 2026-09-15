<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrisSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_hr_access_is_denied_and_audited_while_hr_is_allowed(): void
    {
        $this->seed();

        $engineering = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
        $hr = User::query()->where('email', 'hr@talibon.demo')->firstOrFail();

        $this->actingAs($engineering)
            ->get('/hris/admin')
            ->assertStatus(403);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $engineering->id,
            'action' => 'hris.admin.access',
            'outcome' => 'denied',
        ]);

        $this->actingAs($hr)
            ->get('/hris/admin')
            ->assertOk();
    }
}
