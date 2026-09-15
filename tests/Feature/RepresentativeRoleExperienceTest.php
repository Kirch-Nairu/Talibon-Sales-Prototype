<?php

namespace Tests\Feature;

use App\Domain\Correspondence\CorrespondenceClassification;
use App\Domain\Correspondence\CorrespondenceLifecycleState;
use App\Http\Middleware\RequireMfaAssurance;
use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\TransactionEvent;
use App\Models\User;
use App\Models\WorkflowTransaction;
use App\Services\CorrespondenceAccessDecider;
use App\Services\DashboardExperienceResolver;
use App\Services\WorkQueueExperienceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RepresentativeRoleExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_representative_roles_resolve_to_server_owned_dashboard_and_work_queue_scopes(): void
    {
        $roles = $this->representativeUsers();
        $expected = [
            'system_admin' => ['system_administration', false, false, true, true],
            'mayor_approver' => ['executive_oversight', true, false, true, false],
            'department_head' => ['department_head', true, true, false, false],
            'department_staff' => ['employee', true, false, false, false],
            'hr_officer' => ['employee', true, false, false, false],
            'legislative_staff' => ['employee', true, false, false, false],
            'mayor_staff' => ['executive_oversight', true, false, true, false],
        ];

        foreach ($roles as $role => $actor) {
            [$profile, $personal, $office, $municipal, $system] = $expected[$role];
            $dashboard = app(DashboardExperienceResolver::class)->resolve($actor);
            $workQueue = app(WorkQueueExperienceResolver::class)->resolve($actor);

            $this->assertSame($profile, $dashboard['key'], $role);
            $this->assertSame($personal, $dashboard['scopes']['personal'], $role);
            $this->assertSame($office, $dashboard['scopes']['office'], $role);
            $this->assertSame($municipal, $dashboard['scopes']['municipal'], $role);
            $this->assertSame($system, $dashboard['scopes']['system'], $role);
            $this->assertSame($profile, $workQueue['profile'], $role);

            foreach (['office_queue', 'unassigned', 'staff_workload', 'escalations'] as $officeView) {
                $office === true
                    ? $this->assertContains($officeView, $workQueue['allowedViews'], $role)
                    : $this->assertNotContains($officeView, $workQueue['allowedViews'], $role);
            }
        }
    }

    public function test_admin_and_department_routes_keep_representative_roles_in_their_existing_authority(): void
    {
        $this->withoutMiddleware(RequireMfaAssurance::class);
        $roles = $this->representativeUsers();
        $head = $roles['department_head'];
        $staff = $this->human('department_staff', $head->employee->department);
        $staff->employee->forceFill([
            'home_address' => 'ROLE-MATRIX-PRIVATE-ADDRESS',
            'gsis_number' => 'ROLE-MATRIX-PRIVATE-GSIS',
        ])->save();

        $transaction = $this->transaction(
            'Role matrix office work',
            $head->employee->department,
            $head->employee->department,
            $head,
            $staff->employee,
        );
        TransactionEvent::query()->create([
            'transaction_id' => $transaction->id,
            'actor_user_id' => $head->id,
            'from_department_id' => $head->employee->department_id,
            'to_department_id' => $head->employee->department_id,
            'action' => 'assign',
            'previous_status' => 'submitted',
            'new_status' => 'for_review',
            'created_at' => now(),
        ]);

        $headResponse = $this->actingAs($head)->get('/departments')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Departments/Workspace')
                ->where('department.id', $head->employee->department_id)
                ->has('staffWorkload', 1)
                ->where('staffWorkload.0.employee', $staff->name)
                ->missing('staffWorkload.0.home_address')
                ->missing('staffWorkload.0.gsis_number')
                ->where('activityLimit', 15)
                ->has('recentActivity', 1)
                ->where('recentActivity.0.reference', $transaction->reference_no));

        $this->assertStringNotContainsString('ROLE-MATRIX-PRIVATE-ADDRESS', $headResponse->getContent());
        $this->assertStringNotContainsString('ROLE-MATRIX-PRIVATE-GSIS', $headResponse->getContent());
        $this->actingAs($head)->get('/admin')->assertForbidden();

        foreach ($roles as $role => $actor) {
            if ($role === 'department_head') {
                continue;
            }

            $this->actingAs($actor)->get('/departments')
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Departments/Index')
                    ->missing('staffWorkload')
                    ->missing('recentActivity'));

            if ($role === 'system_admin') {
                $this->actingAs($actor)->get('/admin')
                    ->assertOk()
                    ->assertInertia(fn (Assert $page) => $page
                        ->component('Admin/Index')
                        ->missing('staffWorkload')
                        ->missing('recentActivity'));
            } else {
                $this->actingAs($actor)->get('/admin')->assertForbidden();
            }
        }
    }

    public function test_other_office_restricted_correspondence_stays_independent_of_dashboard_role(): void
    {
        $roles = $this->representativeUsers();
        $isolated = $this->department('RESTRICTED-TARGET');
        $record = CorrespondenceRecord::query()->create([
            'public_id' => (string) Str::uuid(),
            'external_reference_no' => 'ROLE-EXT-'.Str::upper(Str::random(8)),
            'source' => 'email',
            'channel' => 'representative_role_test',
            'sender_name' => 'Restricted Sender',
            'sender_organization' => 'Restricted Source',
            'subject' => 'ROLE-MATRIX-RESTRICTED-SENTINEL',
            'summary' => 'Must remain independently protected.',
            'received_at' => now()->subHour(),
            'receiving_department_id' => $isolated->id,
            'municipal_reference_no' => 'ROLE-COR-'.Str::upper(Str::random(8)),
            'classification' => CorrespondenceClassification::Restricted->value,
            'lifecycle_state' => CorrespondenceLifecycleState::Classified->value,
        ]);
        $access = app(CorrespondenceAccessDecider::class);

        foreach ($roles as $role => $actor) {
            $this->assertFalse($access->canViewInWorkspace($actor, $record), $role);
            $this->assertFalse(
                $access->scopeVisibleTo(CorrespondenceRecord::query(), $actor)
                    ->whereKey($record->id)
                    ->exists(),
                $role,
            );
        }
    }

    public function test_my_work_does_not_turn_non_head_roles_into_office_or_municipal_document_lists(): void
    {
        $this->withoutMiddleware(RequireMfaAssurance::class);
        $roles = $this->representativeUsers();
        $other = $this->department('UNRELATED-ORIGIN');
        $target = $this->department('UNRELATED-TARGET');
        $otherHead = $this->human('department_head', $other);
        $hidden = $this->transaction('ROLE-MATRIX-MUNICIPAL-HIDDEN-SENTINEL', $other, $target, $otherHead);

        $adminResponse = $this->actingAs($roles['system_admin'])->get('/transactions')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('experience.profile', 'system_administration')
                ->where('experience.hasOfficeScope', false)
                ->where('records.total', 0)
                ->has('scopeGroups', 1));
        $this->assertStringNotContainsString($hidden->title, $adminResponse->getContent());
        $this->assertStringNotContainsString((string) $hidden->reference_no, $adminResponse->getContent());

        foreach ($roles as $role => $actor) {
            if ($role === 'department_head') {
                $this->actingAs($actor)->get('/transactions?view=office_queue')->assertOk();
                continue;
            }

            $this->actingAs($actor)->get('/transactions?view=office_queue')->assertForbidden();
        }
    }

    public function test_public_frontend_and_mfa_route_contracts_remain_server_authoritative(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Home')
                ->missing('auth')
                ->missing('permissions')
                ->missing('notifications')
                ->missing('department')
                ->missing('officeOverview')
                ->missing('executiveOverview')
                ->missing('systemOverview'));

        foreach (['dashboard', 'transactions.index', 'departments.index'] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, $routeName);
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth', $middleware, $routeName);
            $this->assertContains('active', $middleware, $routeName);
            $this->assertContains('mfa.assured', $middleware, $routeName);
        }

        $internalSource = implode("\n", [
            file_get_contents(resource_path('js/pages/Dashboard.tsx')),
            file_get_contents(resource_path('js/pages/Transactions/Index.tsx')),
            file_get_contents(resource_path('js/pages/Departments/Workspace.tsx')),
        ]);
        $publicSource = file_get_contents(resource_path('js/pages/Public/Home.tsx'));

        $this->assertIsString($internalSource);
        $this->assertIsString($publicSource);
        foreach (['auth.user.role', 'user.role', 'role ===', 'roles.includes', 'router.reload', 'setInterval'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $internalSource);
        }
        foreach (['fetch(', '/api/', 'router.reload', 'setInterval'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $publicSource);
        }
    }

    /** @return array<string, User> */
    private function representativeUsers(): array
    {
        $mayor = $this->department('MAYOR', 'MAYOR');

        return [
            'system_admin' => $this->human('system_admin', $this->department('SYSTEM')),
            'mayor_approver' => $this->human('mayor_approver', $mayor),
            'department_head' => $this->human('department_head', $this->department('HEAD')),
            'department_staff' => $this->human('department_staff', $this->department('STAFF')),
            'hr_officer' => $this->human('hr_officer', $this->department('HR', 'HRMO')),
            'legislative_staff' => $this->human('legislative_staff', $this->department('LEGISLATIVE', branch: 'legislative')),
            'mayor_staff' => $this->human('mayor_staff', $mayor),
        ];
    }

    private function department(string $suffix, ?string $code = null, string $branch = 'executive'): Department
    {
        return Department::query()->create([
            'code' => $code ?? 'ROLE-'.Str::upper(Str::random(5)).'-'.$suffix,
            'name' => 'Role Matrix '.$suffix,
            'short_name' => 'RM-'.$suffix,
            'branch' => $branch,
            'office_type' => 'department',
            'sort_order' => 10,
            'is_routable' => true,
            'is_active' => true,
        ]);
    }

    private function human(string $role, Department $department): User
    {
        $user = User::query()->create([
            'name' => 'Role Matrix '.$role.' '.Str::random(5),
            'email' => Str::lower(Str::random(10)).'@example.test',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);

        Employee::query()->create([
            'employee_number' => 'ROLE-EMP-'.Str::upper(Str::random(10)),
            'full_name' => $user->name,
            'work_email' => $user->email,
            'user_id' => $user->id,
            'department_id' => $department->id,
            'position_title' => 'Role Matrix Officer',
            'employment_status' => 'active',
        ]);

        return $user->fresh('employee.department');
    }

    private function transaction(
        string $title,
        Department $origin,
        Department $current,
        User $creator,
        ?Employee $assignee = null,
    ): WorkflowTransaction {
        return WorkflowTransaction::query()->create([
            'reference_no' => 'ROLE-TX-'.Str::upper(Str::random(10)),
            'transaction_type' => 'internal_request',
            'title' => $title,
            'description' => 'Synthetic representative-role regression transaction.',
            'priority' => 'normal',
            'origin_department_id' => $origin->id,
            'current_department_id' => $current->id,
            'created_by_user_id' => $creator->id,
            'assigned_employee_id' => $assignee?->id,
            'status' => 'submitted',
            'received_at' => now()->subHour(),
            'due_at' => now()->addDay(),
        ]);
    }
}
