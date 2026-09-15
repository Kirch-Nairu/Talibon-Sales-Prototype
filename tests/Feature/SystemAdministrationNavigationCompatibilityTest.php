<?php

namespace Tests\Feature;

use Tests\TestCase;

class SystemAdministrationNavigationCompatibilityTest extends TestCase
{
    public function test_reports_navigation_uses_server_shared_permissions_without_role_checks(): void
    {
        $layout = file_get_contents(resource_path('js/layouts/AppLayout.tsx'));

        $this->assertIsString($layout);
        $this->assertStringContainsString('pageProps.permissions.reports', $layout);
        $this->assertStringContainsString('pageProps.permissions.navigation', $layout);
        $this->assertStringContainsString('navigation.reports', $layout);
        $this->assertStringNotContainsString('includes(user?.role', $layout);
        $this->assertStringNotContainsString("user?.role ===", $layout);
    }
}
