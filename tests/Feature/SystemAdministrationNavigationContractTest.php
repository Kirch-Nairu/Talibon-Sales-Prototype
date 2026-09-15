<?php

namespace Tests\Feature;

use Tests\TestCase;

class SystemAdministrationNavigationContractTest extends TestCase
{
    public function test_system_navigation_remains_server_authoritative_and_reports_fail_closed(): void
    {
        $layout = file_get_contents(resource_path('js/layouts/AppLayout.tsx'));
        $navigation = file_get_contents(resource_path('js/navigation/portalNavigation.ts'));

        $this->assertIsString($layout);
        $this->assertIsString($navigation);

        $activeNavigation = $layout."\n".$navigation;

        $this->assertStringContainsString('pageProps.permissions.navigation', $layout);
        $this->assertStringContainsString('pageProps.permissions.reports', $layout);
        $this->assertStringContainsString('pageProps.permissions.reports && navigation.reports', $layout);
        $this->assertStringContainsString('permissions[item.permission]', $navigation);
        $this->assertStringContainsString("href: '/admin'", $activeNavigation);
        $this->assertStringNotContainsString('includes(user?.role', $activeNavigation);
        $this->assertStringNotContainsString("user?.role ===", $activeNavigation);

        foreach (['/operations', '/legislation', '/hris', '/employees'] as $parkedHref) {
            $this->assertStringNotContainsString("href: '{$parkedHref}'", $activeNavigation);
        }
    }
}
