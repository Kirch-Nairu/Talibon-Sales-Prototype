<?php

namespace Tests\Feature;

use Tests\TestCase;

class SystemAdministrationNavigationBoundaryTest extends TestCase
{
    public function test_admin_navigation_contract_does_not_advertise_parked_domains(): void
    {
        $layout = file_get_contents(resource_path('js/layouts/AppLayout.tsx'));
        $navigation = file_get_contents(resource_path('js/navigation/portalNavigation.ts'));

        $this->assertIsString($layout);
        $this->assertIsString($navigation);

        $activeNavigation = $layout."\n".$navigation;

        $this->assertStringContainsString("href: '/admin'", $activeNavigation);
        $this->assertStringContainsString('pageProps.permissions.navigation', $layout);
        $this->assertStringContainsString('pageProps.permissions.reports && navigation.reports', $layout);
        $this->assertStringContainsString('permissions[item.permission]', $navigation);
        $this->assertStringNotContainsString('includes(user?.role', $activeNavigation);
        $this->assertStringNotContainsString("user?.role ===", $activeNavigation);

        foreach (['/operations', '/legislation', '/hris', '/employees'] as $href) {
            $this->assertStringNotContainsString("href: '{$href}'", $activeNavigation);
        }
    }
}
