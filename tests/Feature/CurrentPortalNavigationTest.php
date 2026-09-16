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

    public function test_current_one_talibon_navigation_uses_authority_modules_and_current_wired_scope(): void
    {
        $layout = file_get_contents(resource_path('js/layouts/AppLayout.tsx'));
        $navigation = file_get_contents(resource_path('js/navigation/portalNavigation.ts'));
        $access = file_get_contents(resource_path('js/navigation/navigationAccess.ts'));
        $destinations = file_get_contents(resource_path('js/navigation/navigationDestinations.ts'));
        $routePlan = file_get_contents(resource_path('js/navigation/navigationRoutePlan.ts'));

        $this->assertIsString($layout);
        $this->assertIsString($navigation);
        $this->assertIsString($access);
        $this->assertIsString($destinations);
        $this->assertIsString($routePlan);

        $activeNavigation = implode("\n", [$layout, $navigation, $access, $destinations, $routePlan]);

        $this->assertStringContainsString('pageProps.permissions.navigation', $layout);
        $this->assertStringContainsString('pageProps.permissions.reports && navigation.reports', $layout);
        $this->assertStringContainsString('isPortalDestinationVisible(destination, experience, permissions)', $navigation);
        $this->assertStringContainsString('permissions[destination.permission]', $access);
        $this->assertStringContainsString('workspaceExperience', $layout);
        $this->assertSame(18, substr_count($destinations, "readiness: 'wired'"));
        $this->assertSame(2, substr_count($destinations, "readiness: 'integration_pending'"));

        foreach ([
            "home: '/dashboard'",
            "myWork: '/transactions'",
            "correspondence: '/correspondence'",
            "records: '/records'",
            "memoranda: '/memoranda'",
            "announcements: '/announcements'",
            "calendar: '/calendar'",
            "meetings: '/meetings'",
            "messages: '/messages'",
            "executiveDepartments: '/departments'",
            "employeeDirectory: '/employees'",
            "legislative: '/legislation'",
            "localSpecialBodies: '/local-special-bodies'",
            "developmentPlans: '/development-plans'",
            "ppas: '/ppas'",
            "projectMonitoring: '/operations'",
            "systemAdministration: '/admin'",
            "municipalSystems: '/municipal-systems'",
        ] as $routeContract) {
            $this->assertStringContainsString($routeContract, $routePlan);
        }

        $this->assertStringContainsString("users: { key: 'users', label: 'Users'", $destinations);
        $this->assertStringContainsString("adminDepartments: { key: 'adminDepartments', label: 'Departments'", $destinations);
        $this->assertStringContainsString("readiness: 'integration_pending', permission: 'systemAdministration'", $destinations);
        $this->assertStringNotContainsString('Audit & Security', $destinations);
        $this->assertStringNotContainsString("audit: {", $destinations);
        $this->assertStringNotContainsString('includes(user?.role', $activeNavigation);
        $this->assertStringNotContainsString("user?.role ===", $activeNavigation);
    }

    public function test_grouped_navigation_preserves_current_municipal_labels_without_legacy_engineering_sections(): void
    {
        $destinations = file_get_contents(resource_path('js/navigation/navigationDestinations.ts'));
        $this->assertIsString($destinations);

        foreach ([
            'Home',
            'My Work',
            'Correspondence',
            'Records',
            'Memoranda',
            'Announcements',
            'Calendar',
            'Meetings',
            'Messages',
            'Executive Departments',
            'Employee Directory',
            'Legislative',
            'Local Special Bodies',
            'Development Plans',
            'PPAs',
            'Project Monitoring',
            'Users',
            'System Administration',
            'Municipal Systems',
        ] as $label) {
            $this->assertStringContainsString("label: '{$label}'", $destinations);
        }

        foreach ([
            'Office Overview',
            'Executive Overview',
            'System Overview',
            'Inbox & Routing',
            'Accounts & Access',
            'For Decision',
            'Audit & Security',
        ] as $legacyLabel) {
            $this->assertStringNotContainsString($legacyLabel, $destinations);
        }
    }

    public function test_current_and_hidden_backend_routes_remain_registered(): void
    {
        foreach ([
            'admin.index',
            'dashboard',
            'transactions.index',
            'correspondence.index',
            'records.index',
            'memoranda.index',
            'calendar.index',
            'operations.index',
            'legislation.index',
            'employees.index',
            'announcements.index',
            'meetings.index',
            'messages.index',
            'local-special-bodies.index',
            'development-plans.index',
            'ppas.index',
            'municipal-systems.index',
        ] as $routeName) {
            $this->assertTrue(Route::has($routeName), "Expected current route {$routeName} to remain registered.");
        }

        foreach ([
            'audit',
            'mfa.settings',
            'mfa.recovery.show',
            'mfa.recovery.regenerate',
            'mfa.reset',
            'mfa.disable',
            'hris',
            'hris.dtr',
            'hris.payroll',
            'property.index',
            'property.lifecycle.index',
            'reports.index',
        ] as $routeName) {
            $this->assertTrue(Route::has($routeName), "Expected hidden backend route {$routeName} to remain registered.");
        }
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

    public function test_dashboard_response_does_not_serialize_hidden_domain_rollups(): void
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
