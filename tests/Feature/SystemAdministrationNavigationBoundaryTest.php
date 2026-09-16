<?php

namespace Tests\Feature;

use Tests\TestCase;

class SystemAdministrationNavigationBoundaryTest extends TestCase
{
    public function test_admin_navigation_contract_uses_current_authority_without_exposing_security_navigation(): void
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

        $this->assertStringContainsString("systemAdministration: '/admin'", $routePlan);
        $this->assertStringContainsString('pageProps.permissions.navigation', $layout);
        $this->assertStringContainsString('pageProps.permissions.reports && navigation.reports', $layout);
        $this->assertStringContainsString('isPortalDestinationVisible(destination, experience, permissions)', $navigation);
        $this->assertStringContainsString('permissions[destination.permission]', $access);
        $this->assertStringContainsString("systemAdministration: { key: 'systemAdministration'", $destinations);
        $this->assertStringContainsString("readiness: 'wired', permission: 'systemAdministration'", $destinations);
        $this->assertStringNotContainsString('Audit & Security', $destinations);
        $this->assertStringNotContainsString("audit: {", $destinations);
        $this->assertStringNotContainsString('includes(user?.role', $activeNavigation);
        $this->assertStringNotContainsString("user?.role ===", $activeNavigation);

        foreach (['/hris', '/property', '/reports', '/audit'] as $href) {
            $this->assertStringNotContainsString($href, $routePlan);
        }
    }
}
