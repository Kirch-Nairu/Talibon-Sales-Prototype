<?php

namespace Tests\Feature;

use App\Domain\Correspondence\CorrespondenceClassification;
use App\Domain\Correspondence\CorrespondenceLifecycleState;
use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\TransactionEvent;
use App\Models\User;
use App\Models\WorkflowTransaction;
use App\Services\AuthenticationAssurance;
use App\Services\DepartmentWorkspaceQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DepartmentWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_head_receives_own_office_operational_workspace_with_safe_bounded_projection(): void
    {
        $own = $this->department('OWN');
        $other = $this->department('OTHER');
        $head = $this->human('department_head', $own);
        $staff = $this->human('department_staff', $own);

        $staff->employee->forceFill([
            'personal_email' => 'private.person@example.test',
            'home_address' => 'PRIVATE-HOME-ADDRESS-SENTINEL',
            'gsis_number' => 'PRIVATE-GSIS-SENTINEL',
        ])->save();

        $incoming = $this->transaction('Incoming own-office item', $other, $own, $head, $staff->employee, now()->addDay());
        $this->transaction('Own office unassigned overdue', $own, $own, $head, null, now()->subHour());
        $this->transaction(
            'Own office completed',
            $own,
            $own,
            $head,
            $staff->employee,
            now()->subDay(),
            'approved',
            now()->subHours(2),
        );

        foreach (range(1, 18) as $index) {
            TransactionEvent::query()->create([
                'transaction_id' => $incoming->id,
                'actor_user_id' => $head->id,
                'from_department_id' => $other->id,
                'to_department_id' => $own->id,
                'action' => $index === 18 ? 'assign' : 'route',
                'previous_status' => 'submitted',
                'new_status' => 'for_review',
                'remarks' => 'NOT-PROJECTED-REMARK-'.$index,
                'created_at' => now()->subMinutes(18 - $index),
            ]);
        }

        $response = $this->asAssured($head)->get('/departments')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Departments/Workspace')
                ->where('department.id', $own->id)
                ->where('department.name', $own->name)
                ->where('metrics.active.value', 2)
                ->where('metrics.incoming.value', 1)
                ->where('metrics.unassigned.value', 1)
                ->where('metrics.overdue.value', 1)
                ->where('metrics.recentlyCompleted.value', 1)
                ->has('staffWorkload', 1)
                ->where('staffWorkload.0.employee', $staff->name)
                ->where('staffWorkload.0.position', 'Department Officer')
                ->where('staffWorkload.0.active', 1)
                ->where('staffWorkload.0.requiresAction', 1)
                ->missing('staffWorkload.0.personal_email')
                ->missing('staffWorkload.0.home_address')
                ->missing('staffWorkload.0.gsis_number')
                ->where('activityLimit', 15)
                ->has('recentActivity', 15)
                ->where('recentActivity.0.action', 'assign')
                ->where('recentActivity.0.reference', $incoming->reference_no)
                ->missing('recentActivity.0.remarks')
                ->has('oldestUnresolved', 2));

        $this->assertStringNotContainsString('PRIVATE-HOME-ADDRESS-SENTINEL', $response->getContent());
        $this->assertStringNotContainsString('PRIVATE-GSIS-SENTINEL', $response->getContent());
        $this->assertStringNotContainsString('NOT-PROJECTED-REMARK-', $response->getContent());
    }

    public function test_normal_employee_and_system_admin_do_not_receive_department_head_projection(): void
    {
        $office = $this->department('ACCESS');
        $employee = $this->human('department_staff', $office);
        $admin = $this->human('system_admin', $office);

        $this->actingAs($employee)->get('/departments')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Departments/Index')
                ->has('departments')
                ->missing('staffWorkload')
                ->missing('recentActivity'));

        $this->asAssured($admin)->get('/departments')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Departments/Index')
                ->missing('metrics')
                ->missing('staffWorkload')
                ->missing('recentActivity'));
    }

    public function test_department_head_workspace_does_not_leak_other_office_private_activity(): void
    {
        $own = $this->department('SCOPE-OWN');
        $other = $this->department('SCOPE-OTHER');
        $third = $this->department('SCOPE-THIRD');
        $head = $this->human('department_head', $own);
        $otherHead = $this->human('department_head', $other);

        $visible = $this->transaction('Visible office activity', $other, $own, $head);
        TransactionEvent::query()->create([
            'transaction_id' => $visible->id,
            'actor_user_id' => $head->id,
            'from_department_id' => $other->id,
            'to_department_id' => $own->id,
            'action' => 'route',
            'previous_status' => 'submitted',
            'new_status' => 'for_review',
            'created_at' => now(),
        ]);

        $hidden = $this->transaction('OTHER-OFFICE-PRIVATE-SENTINEL', $other, $third, $otherHead);
        TransactionEvent::query()->create([
            'transaction_id' => $hidden->id,
            'actor_user_id' => $otherHead->id,
            'from_department_id' => $other->id,
            'to_department_id' => $third->id,
            'action' => 'route',
            'previous_status' => 'submitted',
            'new_status' => 'for_review',
            'created_at' => now()->addSecond(),
        ]);
        $this->correspondence(
            'RESTRICTED-OTHER-OFFICE-SENTINEL',
            $third,
            $hidden,
            CorrespondenceClassification::Restricted,
        );

        $response = $this->asAssured($head)->get('/departments')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('recentActivity.0.reference', $visible->reference_no)
                ->has('recentActivity', 1));

        $this->assertStringNotContainsString($hidden->title, $response->getContent());
        $this->assertStringNotContainsString('RESTRICTED-OTHER-OFFICE-SENTINEL', $response->getContent());
    }

    public function test_recent_activity_is_derived_from_transaction_events_and_not_transaction_updates_alone(): void
    {
        $own = $this->department('EVENTS-OWN');
        $other = $this->department('EVENTS-OTHER');
        $head = $this->human('department_head', $own);
        $withEvent = $this->transaction('With authoritative event', $other, $own, $head);
        $withoutEvent = $this->transaction('No event sentinel', $other, $own, $head);

        $event = TransactionEvent::query()->create([
            'transaction_id' => $withEvent->id,
            'actor_user_id' => $head->id,
            'from_department_id' => $other->id,
            'to_department_id' => $own->id,
            'action' => 'receive',
            'previous_status' => 'submitted',
            'new_status' => 'received',
            'created_at' => now(),
        ]);

        $workspace = app(DepartmentWorkspaceQuery::class)->workspace($head);

        $this->assertCount(1, $workspace['recentActivity']);
        $this->assertSame($event->id, $workspace['recentActivity'][0]['id']);
        $this->assertSame($withEvent->reference_no, $workspace['recentActivity'][0]['reference']);
        $this->assertNotSame($withoutEvent->reference_no, $workspace['recentActivity'][0]['reference']);
    }

    public function test_department_workspace_query_count_is_constant_as_staff_grows(): void
    {
        $office = $this->department('QUERY');
        $head = $this->human('department_head', $office);
        $first = $this->human('department_staff', $office);
        $this->transaction('Baseline assigned work', $office, $office, $head, $first->employee);

        $baseline = $this->workspaceQueryCount($head->fresh());

        foreach (range(1, 20) as $index) {
            $staff = $this->human('department_staff', $office);
            $this->transaction('Expanded assigned work '.$index, $office, $office, $head, $staff->employee);
        }

        $expanded = $this->workspaceQueryCount($head->fresh());

        $this->assertLessThanOrEqual($baseline + 1, $expanded);
        $this->assertLessThanOrEqual(35, $expanded);
    }

    public function test_public_root_receives_no_department_workspace_projection(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Home')
                ->missing('department')
                ->missing('metrics')
                ->missing('staffWorkload')
                ->missing('recentActivity'));
    }

    private function asAssured(User $user): static
    {
        $user->forceFill([
            'mfa_secret' => 'department-workspace-test-secret',
            'mfa_confirmed_at' => now(),
            'mfa_version' => 1,
        ])->save();

        return $this->actingAs($user)->withSession([
            AuthenticationAssurance::SESSION_USER_KEY => $user->id,
            AuthenticationAssurance::SESSION_VERSION_KEY => 1,
            AuthenticationAssurance::SESSION_VERIFIED_AT_KEY => now()->getTimestamp(),
        ]);
    }

    private function workspaceQueryCount(User $actor): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        app(DepartmentWorkspaceQuery::class)->workspace($actor);
        $count = count(DB::getQueryLog());

        DB::disableQueryLog();

        return $count;
    }

    private function department(string $suffix): Department
    {
        return Department::query()->create([
            'code' => 'DEPT-'.Str::upper(Str::random(5)).'-'.$suffix,
            'name' => 'Department '.$suffix,
            'short_name' => 'D-'.$suffix,
            'branch' => 'executive',
            'office_type' => 'department',
            'sort_order' => 10,
            'is_routable' => true,
            'is_active' => true,
        ]);
    }

    private function human(string $role, Department $department): User
    {
        $user = User::query()->create([
            'name' => 'Department '.$role.' '.Str::random(5),
            'email' => Str::lower(Str::random(10)).'@example.test',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);

        Employee::query()->create([
            'employee_number' => 'DEPT-EMP-'.Str::upper(Str::random(10)),
            'full_name' => $user->name,
            'work_email' => $user->email,
            'user_id' => $user->id,
            'department_id' => $department->id,
            'position_title' => 'Department Officer',
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
        $dueAt = null,
        string $status = 'submitted',
        $completedAt = null,
    ): WorkflowTransaction {
        return WorkflowTransaction::query()->create([
            'reference_no' => 'DEPT-TX-'.Str::upper(Str::random(10)),
            'transaction_type' => 'internal_request',
            'title' => $title,
            'description' => 'Synthetic department workspace transaction.',
            'priority' => 'normal',
            'origin_department_id' => $origin->id,
            'current_department_id' => $current->id,
            'created_by_user_id' => $creator->id,
            'assigned_employee_id' => $assignee?->id,
            'status' => $status,
            'received_at' => now()->subHours(2),
            'due_at' => $dueAt ?? now()->addDays(3),
            'completed_at' => $completedAt,
        ]);
    }

    private function correspondence(
        string $subject,
        Department $department,
        WorkflowTransaction $workflow,
        CorrespondenceClassification $classification,
    ): CorrespondenceRecord {
        return CorrespondenceRecord::query()->create([
            'public_id' => (string) Str::uuid(),
            'external_reference_no' => 'DEPT-EXT-'.Str::upper(Str::random(10)),
            'source' => 'email',
            'channel' => 'department_workspace_test',
            'sender_name' => 'Department Sender',
            'sender_organization' => 'Department Source Office',
            'subject' => $subject,
            'summary' => 'Synthetic department workspace correspondence.',
            'received_at' => now()->subHour(),
            'receiving_department_id' => $department->id,
            'municipal_reference_no' => 'DEPT-COR-'.Str::upper(Str::random(10)),
            'classification' => $classification->value,
            'lifecycle_state' => CorrespondenceLifecycleState::Routed->value,
            'workflow_transaction_id' => $workflow->id,
        ]);
    }
}
