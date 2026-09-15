<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CurrentPortalNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_prototype_navigation_keeps_current_scope_links_and_hides_parked_modules(): void
    {
        $layout = file_get_contents(resource_path('js/layouts/AppLayout.tsx'));
        $navigation = file_get_contents(resource_path('js/navigation/portalNavigation.ts'));
        $this->assertIsString($layout);
        $this->assertIsString($navigation);
        $activeNavigation = $layout."\n".$navigation;

        foreach (['/admin', '/dashboard', '/transactions', '/correspondence', '/records', '/travel-orders', '/reports', '/mayor-office', '/memoranda', '/departments', '/audit'] as $href) {
            $this->assertStringContainsString("href: '{$href}'", $activeNavigation);
        }

        $this->assertStringContainsString('pageProps.permissions.navigation', $layout);
        $this->assertStringContainsString('pageProps.permissions.reports && navigation.reports', $layout);
        $this->assertStringContainsString('permissions[item.permission]', $navigation);
        $this->assertStringContainsString('requiresReports', $navigation);
        $this->assertStringContainsString('workspaceExperience', $layout);
        $this->assertStringNotContainsString('role', strtolower($navigation));

        foreach (['/operations', '/legislation', '/hris', '/employees'] as $href) {
            $this->assertStringNotContainsString("href: '{$href}'", $activeNavigation);
        }
    }

    public function test_grouped_navigation_preserves_task_oriented_labels_without_inventing_routes(): void
    {
        $navigation = file_get_contents(resource_path('js/navigation/portalNavigation.ts'));
        $this->assertIsString($navigation);

        foreach ([
            'Office Overview',
            'Executive Overview',
            'System Overview',
            'Inbox & Routing',
            'Travel Orders',
            'Municipal Offices',
            'Accounts & Access',
            'For Decision',
            'Audit & Security',
        ] as $label) {
            $this->assertStringContainsString($label, $navigation);
        }

        foreach (['/admin/accounts', '/admin/offices', '/admin/mfa', '/department/current', '/executive/decisions'] as $href) {
            $this->assertStringNotContainsString($href, $navigation);
        }
    }

    public function test_parked_routes_remain_registered_even_when_not_advertised(): void
    {
        foreach ([
            'operations.index',
            'legislation.index',
            'legislative.workspace',
            'hris',
            'hris.dtr',
            'hris.payroll',
            'property.index',
            'property.lifecycle.index',
            'reports.index',
            'employees.index',
        ] as $routeName) {
            $this->assertTrue(Route::has($routeName), "Expected parked route {$routeName} to remain registered.");
        }

        $this->assertTrue(Route::has('admin.index'));
        $this->assertTrue(Route::has('correspondence.index'));
        $this->assertTrue(Route::has('records.index'));
    }

    public function test_public_routes_are_separate_while_internal_routes_keep_security_middleware(): void
    {
        $this->assertTrue(Route::has('public.home'));
        $this->assertTrue(Route::has('public.activate-account'));

        foreach (['admin.index', 'dashboard', 'transactions.index', 'correspondence.index', 'records.index', 'reports.index'] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route);
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth', $middleware, "{$routeName} must remain authenticated.");
            $this->assertContains('active', $middleware, "{$routeName} must preserve active-account enforcement.");
            $this->assertContains('mfa.assured', $middleware, "{$routeName} must preserve MFA assurance.");
        }
    }

    public function test_dashboard_response_does_not_serialize_parked_module_rollups(): void
    {
        $this->seed();
        $mayor = User::query()->where('email', 'mayor@talibon.demo')->firstOrFail();

        $this->actingAs($mayor)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->missing('centralRecords')
                ->missing('operationsSnapshot')
                ->missing('workspace.canAccessHris')
                ->missing('workspace.canManageLegislation'));
    }
}
