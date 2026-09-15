<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Department;
use App\Models\OnboardingCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1LifecyclePropertyHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_lifecycle_workspace_is_restricted_to_authorized_hr_administration(): void
    {
        $this->seed();

        $engineering = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
        $hr = User::query()->where('email', 'hr@talibon.demo')->firstOrFail();

        $this->actingAs($engineering)->get('/hris/admin/lifecycle')->assertForbidden();
        $this->actingAs($hr)->get('/hris/admin/lifecycle')->assertOk();
    }

    public function test_hr_can_start_onboarding_but_cannot_complete_case_with_open_blockers(): void
    {
        $this->seed();

        $hr = User::query()->where('email', 'hr@talibon.demo')->firstOrFail();
        $engineering = Department::query()->where('code', 'ENG')->firstOrFail();

        $this->actingAs($hr)->post('/hris/admin/lifecycle/onboarding', [
            'full_name' => 'HTTP Lifecycle Employee',
            'work_email' => 'http.lifecycle.employee@talibon.demo',
            'department_id' => $engineering->id,
            'position_title' => 'Administrative Officer',
            'employment_type' => 'regular',
            'planned_start_date' => now()->addDays(3)->toDateString(),
            'appointment_reference' => 'HTTP-APPT-001',
        ])->assertRedirect();

        $case = OnboardingCase::query()->whereHas('employee', fn ($query) => $query->where('work_email', 'http.lifecycle.employee@talibon.demo'))->firstOrFail();

        $this->actingAs($hr)
            ->from('/hris/admin/lifecycle/onboarding/'.$case->id)
            ->post('/hris/admin/lifecycle/onboarding/'.$case->id.'/complete')
            ->assertRedirect('/hris/admin/lifecycle/onboarding/'.$case->id)
            ->assertSessionHasErrors('onboarding');
    }

    public function test_property_workspace_is_read_restricted_and_mutation_is_server_side_restricted(): void
    {
        $this->seed();

        $engineering = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
        $hr = User::query()->where('email', 'hr@talibon.demo')->firstOrFail();
        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();

        $this->actingAs($engineering)->get('/property')->assertForbidden();
        $this->actingAs($hr)->get('/property')->assertOk();
        $this->actingAs($admin)->get('/property')->assertOk();

        $this->actingAs($engineering)->post('/property', [
            'property_number' => 'HTTP-DENIED-001',
            'category' => 'ICT Equipment',
            'description' => 'Denied write attempt',
        ])->assertForbidden();

        $this->actingAs($admin)->post('/property', [
            'property_number' => 'HTTP-PROP-001',
            'category' => 'ICT Equipment',
            'description' => 'Phase 1 HTTP property record',
            'condition' => 'good',
        ])->assertRedirect('/property');

        $this->assertTrue(Asset::query()->where('property_number', 'HTTP-PROP-001')->exists());
        $this->assertFalse(Asset::query()->where('property_number', 'HTTP-DENIED-001')->exists());
    }
}
