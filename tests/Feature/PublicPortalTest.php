<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_public_home_without_internal_props_or_database_queries(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Home')
                ->where('appName', config('app.name'))
                ->where('authenticated', false)
                ->where('content.dataMode', 'prototype')
                ->where('content.sampleLabel', 'PROTOTYPE SAMPLE DATA')
                ->missing('auth')
                ->missing('permissions')
                ->missing('pendingMemo')
                ->missing('unreadMemoCount')
                ->missing('unreadPlatformNotificationCount')
                ->missing('notifications')
                ->missing('notificationCount'));

        $this->assertSame([], DB::getQueryLog());
    }

    public function test_authenticated_user_can_still_open_public_home_without_identity_leakage(): void
    {
        $user = User::query()->create([
            'name' => 'Public Boundary Test User',
            'email' => 'public-boundary@example.test',
            'password' => 'Public-Boundary-Test-Password!',
            'role' => 'system_admin',
            'is_active' => true,
        ]);

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Home')
                ->where('authenticated', true)
                ->missing('auth')
                ->missing('permissions')
                ->missing('notifications')
                ->missing('notificationCount'));
    }

    public function test_public_routes_have_no_auth_active_or_mfa_requirement(): void
    {
        $home = Route::getRoutes()->getByName('public.home');
        $activation = Route::getRoutes()->getByName('public.activate-account');

        $this->assertNotNull($home);
        $this->assertNotNull($activation);

        foreach (['auth', 'active', 'mfa.subject', 'mfa.assured'] as $middleware) {
            $this->assertNotContains($middleware, $home->gatherMiddleware());
            $this->assertNotContains($middleware, $activation->gatherMiddleware());
        }

        $this->assertContains('guest', $activation->gatherMiddleware());
    }

    public function test_activation_is_read_only_and_public_registration_is_absent(): void
    {
        $this->get('/activate-account')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/ActivateAccount')
                ->where('authenticated', false)
                ->missing('auth'));

        $this->post('/activate-account')->assertStatus(405);
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
        $this->assertFalse(Route::has('register'));
    }

    public function test_public_controller_is_config_backed_and_has_no_internal_model_query_dependency(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/PublicPortalController.php'));
        $this->assertIsString($source);
        $this->assertStringContainsString("config('public_portal')", $source);

        foreach (['WorkflowTransaction', 'Correspondence', 'Employee', 'AuditLog', 'Document::', 'Reports'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $source);
        }
    }
}
