<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PortalNavigationAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalNavigationAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_permissions_are_server_derived_by_existing_authority(): void
    {
        $this->seed();
        $navigation = app(PortalNavigationAccess::class);

        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();
        $adminPermissions = $navigation->for($admin);
        $this->assertTrue($adminPermissions['systemAdministration']);
        $this->assertTrue($adminPermissions['dashboard']);
        $this->assertTrue($adminPermissions['reports']);
        $this->assertTrue($adminPermissions['mayorOffice']);
        $this->assertTrue($adminPermissions['audit']);

        $departmentHead = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
        $headPermissions = $navigation->for($departmentHead);
        $this->assertFalse($headPermissions['systemAdministration']);
        $this->assertTrue($headPermissions['dashboard']);
        $this->assertTrue($headPermissions['transactions']);
        $this->assertTrue($headPermissions['correspondence']);
        $this->assertTrue($headPermissions['records']);
        $this->assertTrue($headPermissions['reports']);
        $this->assertTrue($headPermissions['departments']);
        $this->assertFalse($headPermissions['mayorOffice']);
        $this->assertFalse($headPermissions['audit']);

        $staff = User::query()->where('email', 'employee@talibon.demo')->firstOrFail();
        $staff->forceFill(['role' => 'department_staff'])->save();
        $staffPermissions = $navigation->for($staff->fresh());
        $this->assertFalse($staffPermissions['systemAdministration']);
        $this->assertTrue($staffPermissions['dashboard']);
        $this->assertTrue($staffPermissions['transactions']);
        $this->assertTrue($staffPermissions['correspondence']);
        $this->assertTrue($staffPermissions['records']);
        $this->assertFalse($staffPermissions['audit']);

        $mayor = User::query()->where('email', 'mayor@talibon.demo')->firstOrFail();
        $mayor->forceFill(['role' => 'mayor_staff'])->save();
        $mayorStaffPermissions = $navigation->for($mayor->fresh());
        $this->assertTrue($mayorStaffPermissions['mayorOffice']);
        $this->assertFalse($mayorStaffPermissions['systemAdministration']);
        $this->assertFalse($mayorStaffPermissions['audit']);

        $mayor->forceFill(['role' => 'mayor_approver'])->save();
        $mayorApproverPermissions = $navigation->for($mayor->fresh());
        $this->assertTrue($mayorApproverPermissions['mayorOffice']);
        $this->assertTrue($mayorApproverPermissions['audit']);
        $this->assertFalse($mayorApproverPermissions['systemAdministration']);
    }

    public function test_inactive_accounts_receive_no_navigation_permissions(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'employee@talibon.demo')->firstOrFail();
        $user->forceFill(['is_active' => false])->save();

        $this->assertSame(
            PortalNavigationAccess::none(),
            app(PortalNavigationAccess::class)->for($user->fresh()),
        );
    }
}
