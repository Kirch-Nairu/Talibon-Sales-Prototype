<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Phase1EmployeeProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_view_own_private_and_employment_profile(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'employee@talibon.demo')->firstOrFail();
        $employee = $user->employee;
        $employee->update([
            'employment_type' => 'regular',
            'employment_start_date' => '2024-01-15',
            'personal_email' => 'employee.private@example.test',
            'home_address' => 'Synthetic test address',
            'gsis_number' => 'TEST-GSIS-001',
        ]);

        $this->actingAs($user)->get('/employees/'.$employee->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Employees/Show')
                ->where('permissions.isSelf', true)
                ->where('permissions.canViewPrivate', true)
                ->where('privateProfile.personal_email', 'employee.private@example.test')
                ->where('employmentProfile.employment_type', 'regular'));
    }

    public function test_department_head_does_not_receive_another_employees_private_or_201_profile(): void
    {
        $this->seed();

        $head = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
        $target = Employee::query()
            ->where('department_id', $head->employee->department_id)
            ->whereKeyNot($head->employee->id)
            ->firstOrFail();
        $target->update([
            'personal_email' => 'must-not-leak@example.test',
            'gsis_number' => 'PRIVATE-GSIS',
        ]);

        $this->actingAs($head)->get('/employees/'.$target->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Employees/Show')
                ->where('permissions.canViewPrivate', false)
                ->where('permissions.canViewHrRecord', false)
                ->where('privateProfile', null)
                ->where('employmentProfile', null));
    }

    public function test_hr_can_view_employee_private_profile_but_health_vault_is_not_implicitly_granted(): void
    {
        $this->seed();

        $hr = User::query()->where('email', 'hr@talibon.demo')->firstOrFail();
        $target = User::query()->where('email', 'employee@talibon.demo')->firstOrFail()->employee;
        $target->update([
            'emergency_contact_name' => 'Synthetic Emergency Contact',
            'philhealth_number' => 'TEST-PHILHEALTH-001',
        ]);

        $this->actingAs($hr)->get('/employees/'.$target->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Employees/Show')
                ->where('permissions.canViewPrivate', true)
                ->where('permissions.canViewHrRecord', true)
                ->where('permissions.healthVaultAccess', false)
                ->where('privateProfile.emergency_contact_name', 'Synthetic Emergency Contact'));
    }
}
