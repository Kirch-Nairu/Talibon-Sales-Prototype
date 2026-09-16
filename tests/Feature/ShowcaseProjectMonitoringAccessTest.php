<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShowcaseProjectMonitoringAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('showcase.enabled', true);
    }

    public function test_department_head_showcase_persona_can_open_project_monitoring(): void
    {
        $this->createCuratedUser('engineering_head', 'department_head');

        $this->post('/showcase/session', ['persona' => 'engineering_head'])
            ->assertRedirect(route('dashboard'));

        $this->get('/operations')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('ProjectMonitoring/Index'));
    }

    public function test_employee_showcase_persona_remains_denied_project_monitoring(): void
    {
        $this->createCuratedUser('employee', 'employee');

        $this->post('/showcase/session', ['persona' => 'employee'])
            ->assertRedirect(route('dashboard'));

        $this->get('/operations')->assertForbidden();
    }

    private function createCuratedUser(string $personaKey, string $role): User
    {
        $persona = config("showcase.personas.{$personaKey}");

        return User::query()->create([
            'name' => $persona['label'].' Access Test',
            'email' => $persona['email'],
            'password' => Hash::make('not-used-by-showcase'),
            'role' => $role,
            'is_active' => true,
        ]);
    }
}
