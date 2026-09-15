<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkflowTransaction;
use App\Services\WorkQueueQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WorkQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_head_default_is_personal_active_work_with_separate_office_scope(): void
    {
        $office = $this->department('ALL-OWN');
        $other = $this->department('ALL-OTHER');
        $third = $this->department('ALL-THIRD');
        $actor = $this->human('department_head', $office);
        $otherHead = $this->human('department_head', $other);

        $this->transaction('Assigned local work', $office, $office, $actor, $actor->employee, now()->addDays(2));
        $this->transaction('Unassigned incoming work', $other, $office, $actor, dueAt: now()->addDays(3));
        $this->transaction('Waiting on another office', $office, $other, $actor, dueAt: now()->addDays(4));
        $this->transaction(
            'Recently completed local work',
            $office,
            $office,
            $actor,
            $actor->employee,
            now()->subDay(),
            status: 'approved',
            completedAt: now()->subDays(2),
        );
        $hidden = $this->transaction('Unrelated hidden work', $other, $third, $otherHead, dueAt: now()->addDay());

        $response = $this->actingAs($actor)->get('/transactions')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->where('filters.view', 'all')
                ->where('records.total', 3)
                ->has('records.data', 3)
                ->where('experience.profile', 'department_head')
                ->where('experience.hasOfficeScope', true)
                ->where('experience.department.id', $office->id)
                ->has('scopeGroups', 2)
                ->where('scopeGroups.0.key', 'personal')
                ->has('scopeGroups.0.views', 8)
                ->where('scopeGroups.0.views.0.key', 'all')
                ->where('scopeGroups.0.views.0.label', 'My Work')
                ->where('scopeGroups.0.views.0.count', 3)
                ->where('scopeGroups.0.views.1.key', 'needs_my_action')
                ->where('scopeGroups.0.views.2.key', 'assigned_to_me')
                ->where('scopeGroups.0.views.3.key', 'due_soon')
                ->where('scopeGroups.0.views.4.key', 'overdue')
                ->where('scopeGroups.0.views.5.key', 'recently_updated')
                ->where('scopeGroups.0.views.6.key', 'waiting_on_others')
                ->where('scopeGroups.0.views.7.key', 'recently_completed')
                ->where('scopeGroups.1.key', 'office')
                ->has('scopeGroups.1.views', 4)
                ->where('scopeGroups.1.views.0.key', 'office_queue')
                ->where('scopeGroups.1.views.1.key', 'unassigned')
                ->where('scopeGroups.1.views.2.key', 'staff_workload')
                ->where('scopeGroups.1.views.3.key', 'escalations'));

        $this->assertStringNotContainsString($hidden->title, $response->getContent());
        $this->assertStringNotContainsString((string) $hidden->reference_no, $response->getContent());
    }

    public function test_needs_action_and_assigned_to_me_are_active_while_completed_has_its_own_category(): void
    {
        $office = $this->department('ASSIGN');
        $other = $this->department('ASSIGN-OTHER');
        $actor = $this->human('department_head', $office);
        $otherStaff = $this->human('department_staff', $office);

        $mine = $this->transaction('Active assigned to actor', $other, $office, $actor, $actor->employee, now()->addDays(2));
        $this->transaction('Active unassigned office work', $other, $office, $actor, dueAt: now()->addDays(3));
        $this->transaction('Active assigned to another employee', $other, $office, $actor, $otherStaff->employee, now()->addDays(4));
        $terminalMine = $this->transaction(
            'Terminal still assigned to actor',
            $office,
            $office,
            $actor,
            $actor->employee,
            now()->subDay(),
            status: 'approved',
            completedAt: now()->subDay(),
        );

        $this->actingAs($actor)->get('/transactions?view=needs_my_action')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.view', 'needs_my_action')
                ->where('records.total', 1)
                ->where('records.data.0.id', $mine->id)
                ->where('records.data.0.requiresAction', true)
                ->where('records.data.0.expectedAction', 'Open and take the next workflow action'));

        $this->actingAs($actor)->get('/transactions?view=assigned_to_me')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 1)
                ->where('scopeGroups.0.views.2.count', 1));

        $this->actingAs($actor)->get('/transactions?view=assigned_to_me&status=approved')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('records.total', 0));

        $this->actingAs($actor)->get('/transactions?view=recently_completed')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 1)
                ->where('records.data.0.id', $terminalMine->id)
                ->where('records.data.0.expectedAction', 'No further workflow action'));
    }

    public function test_unassigned_and_office_queue_remain_active_projections(): void
    {
        $office = $this->department('OFFICE');
        $other = $this->department('OFFICE-OTHER');
        $actor = $this->human('department_head', $office);

        $unassigned = $this->transaction('Active unassigned', $other, $office, $actor, dueAt: now()->addDays(2));
        $assigned = $this->transaction('Active assigned', $other, $office, $actor, $actor->employee, now()->addDays(3));
        $this->transaction(
            'Terminal office work',
            $office,
            $office,
            $actor,
            dueAt: now()->subDay(),
            status: 'approved',
            completedAt: now()->subDay(),
        );

        $this->assertSingleView($actor, 'unassigned', $unassigned);

        $this->actingAs($actor)->get('/transactions?view=office_queue')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 2)
                ->has('records.data', 2)
                ->where('records.data.1.id', $assigned->id)
                ->where('scopeGroups.1.views.0.count', 2));
    }

    public function test_employee_receives_only_personal_categories_and_cannot_select_office_head_views(): void
    {
        $office = $this->department('STAFF-OWN');
        $other = $this->department('STAFF-OTHER');
        $actor = $this->human('department_staff', $office);
        $head = $this->human('department_head', $office);

        $mine = $this->transaction('Staff assigned work', $other, $office, $head, $actor->employee, now()->addHours(6));
        $outgoing = $this->transaction('Staff initiated outgoing work', $office, $other, $actor, dueAt: now()->addDays(3));
        $officeOnly = $this->transaction('Office-only unassigned work', $other, $office, $head, dueAt: now()->subDay());

        $response = $this->actingAs($actor)->get('/transactions')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('experience.profile', 'employee')
                ->where('experience.hasOfficeScope', false)
                ->has('scopeGroups', 1)
                ->has('scopeGroups.0.views', 8)
                ->where('records.total', 2)
                ->has('records.data', 2));

        $this->assertStringContainsString($mine->title, $response->getContent());
        $this->assertStringContainsString($outgoing->title, $response->getContent());
        $this->assertStringNotContainsString($officeOnly->title, $response->getContent());

        $this->actingAs($actor)->get('/transactions?view=overdue')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('records.total', 0));

        foreach (['office_queue', 'unassigned', 'staff_workload', 'escalations'] as $officeView) {
            $this->actingAs($actor)->get('/transactions?view='.$officeView)->assertForbidden();
        }
    }

    public function test_department_head_staff_workload_and_escalations_are_bounded_office_projections(): void
    {
        $office = $this->department('LEAD-OWN');
        $other = $this->department('LEAD-OTHER');
        $head = $this->human('department_head', $office);
        $first = $this->human('department_staff', $office);
        $second = $this->human('department_staff', $office);
        $otherHead = $this->human('department_head', $other);

        $overdue = $this->transaction('Assigned overdue escalation', $other, $office, $head, $first->employee, now()->subDay());
        $urgent = $this->transaction('Assigned urgent escalation', $other, $office, $head, $second->employee, now()->addDays(3), priority: 'urgent');
        $unassigned = $this->transaction('Unassigned overdue escalation', $other, $office, $head, dueAt: now()->subHours(2));
        $hidden = $this->transaction('Other office escalation', $other, $other, $otherHead, dueAt: now()->subDay(), priority: 'urgent');

        $workloadResponse = $this->actingAs($head)->get('/transactions?view=staff_workload')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.view', 'staff_workload')
                ->where('records.total', 0)
                ->has('staffWorkload', 2)
                ->where('staffWorkload.0.employee', $first->employee->full_name)
                ->where('staffWorkload.0.active', 1)
                ->where('staffWorkload.0.overdue', 1)
                ->where('staffWorkload.0.requiresAction', 1)
                ->missing('staffWorkload.0.employeeNumber')
                ->missing('staffWorkload.0.workEmail'));

        $this->assertStringNotContainsString($hidden->title, $workloadResponse->getContent());

        $this->actingAs($head)->get('/transactions?view=staff_workload&priority=urgent')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.priority', 'urgent')
                ->has('staffWorkload', 1)
                ->where('staffWorkload.0.employee', $second->employee->full_name));

        $escalationResponse = $this->actingAs($head)->get('/transactions?view=escalations')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 3)
                ->where('scopeGroups.1.views.3.count', 3));

        foreach ([$overdue, $urgent, $unassigned] as $visible) {
            $this->assertStringContainsString($visible->title, $escalationResponse->getContent());
        }
        $this->assertStringContainsString('Assign an office owner', $escalationResponse->getContent());
        $this->assertStringNotContainsString($hidden->title, $escalationResponse->getContent());
    }

    public function test_recently_updated_is_active_personal_work_and_items_include_expected_action_and_update_time(): void
    {
        $office = $this->department('UPDATED');
        $actor = $this->human('department_staff', $office);

        $recent = $this->transaction('Recently updated assigned work', $office, $office, $actor, $actor->employee, now()->addDays(2), status: 'for_review');
        $old = $this->transaction('Old assigned work', $office, $office, $actor, $actor->employee, now()->addDays(2));
        $old->forceFill(['updated_at' => now()->subDays(10)])->saveQuietly();
        $terminal = $this->transaction('Recently touched completed work', $office, $office, $actor, $actor->employee, now()->subDay(), status: 'approved', completedAt: now()->subDay());
        $terminal->touch();

        $response = $this->actingAs($actor)->get('/transactions?view=recently_updated')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 1)
                ->where('records.data.0.id', $recent->id)
                ->where('records.data.0.expectedAction', 'Review and take the next workflow action')
                ->where('records.data.0.requiresAction', true)
                ->where('records.data.0.originOffice.id', $office->id)
                ->where('records.data.0.currentOffice.id', $office->id)
                ->where('records.data.0.assignedEmployee.name', $actor->employee->full_name)
                ->where('records.data.0.updatedAt', $recent->fresh()->updated_at->toIso8601String())
                ->where('records.data.0.dueAt', $recent->due_at->toIso8601String()));

        $this->assertStringNotContainsString($old->title, $response->getContent());
        $this->assertStringNotContainsString($terminal->title, $response->getContent());
    }

    public function test_overdue_due_soon_waiting_and_recently_completed_use_correct_existing_fields(): void
    {
        $office = $this->department('TIMING');
        $other = $this->department('TIMING-OTHER');
        $actor = $this->human('department_head', $office);
        $otherStaff = $this->human('department_staff', $other);

        $overdue = $this->transaction('Overdue active', $other, $office, $actor, $actor->employee, now()->subDay());
        $dueSoon = $this->transaction('Due soon active', $other, $office, $actor, $actor->employee, now()->addHours(6));
        $this->transaction('Waiting elsewhere', $office, $other, $actor, $otherStaff->employee, now()->addDays(3));

        $recent = $this->transaction(
            'Recent completed',
            $office,
            $office,
            $actor,
            $actor->employee,
            now()->subDay(),
            status: 'approved',
            completedAt: now()->subDays(3),
        );
        $nullCompleted = $this->transaction(
            'Terminal with no completion timestamp',
            $office,
            $office,
            $actor,
            $actor->employee,
            now()->subDay(),
            status: 'approved',
            completedAt: null,
        );
        $nullCompleted->touch();

        $this->transaction(
            'Old completed timestamp',
            $office,
            $office,
            $actor,
            $actor->employee,
            now()->subDays(60),
            status: 'approved',
            completedAt: now()->subDays(60),
        );
        $this->transaction(
            'Active with completion timestamp',
            $office,
            $office,
            $actor,
            $actor->employee,
            now()->addDays(4),
            status: 'submitted',
            completedAt: now()->subDay(),
        );

        $this->assertSingleView($actor, 'overdue', $overdue);
        $this->assertSingleView($actor, 'due_soon', $dueSoon);

        $this->actingAs($actor)->get('/transactions?view=waiting_on_others')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('records.total', 1));

        $response = $this->actingAs($actor)->get('/transactions?view=recently_completed')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 1)
                ->where('records.data.0.id', $recent->id));

        $this->assertStringNotContainsString($nullCompleted->title, $response->getContent());
    }

    public function test_search_includes_transaction_type_and_existing_search_fields(): void
    {
        $office = $this->department('SEARCH');
        $actor = $this->human('department_head', $office);

        $typeMatch = $this->transaction(
            'Neutral title',
            $office,
            $office,
            $actor,
            dueAt: now()->addDays(2),
            transactionType: 'funding_request',
        );
        $this->transaction(
            'Another neutral title',
            $office,
            $office,
            $actor,
            dueAt: now()->addDays(3),
            transactionType: 'internal_request',
        );

        $this->actingAs($actor)->get('/transactions?view=all&search=funding_request')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', 'funding_request')
                ->where('records.total', 1)
                ->where('records.data.0.id', $typeMatch->id)
                ->where('records.data.0.transactionType', 'funding_request'));
    }

    public function test_search_status_priority_and_current_office_filters_intersect_authorized_scope(): void
    {
        $own = $this->department('FILTER-OWN');
        $budget = $this->department('FILTER-BUDGET');
        $engineering = $this->department('FILTER-ENG');
        $actor = $this->human('department_head', $own);
        $otherHead = $this->human('department_head', $engineering);
        $budgetStaff = $this->human('department_staff', $budget);

        $match = $this->transaction(
            'Budget Alpha Review',
            $own,
            $budget,
            $actor,
            $budgetStaff->employee,
            now()->addDays(2),
            priority: 'high',
            status: 'for_review',
        );
        $this->transaction(
            'Budget Local Review',
            $budget,
            $own,
            $actor,
            dueAt: now()->addDays(3),
            priority: 'high',
            status: 'for_review',
        );
        $hidden = $this->transaction(
            'Budget Hidden Review',
            $engineering,
            $budget,
            $otherHead,
            $budgetStaff->employee,
            now()->addDay(),
            priority: 'high',
            status: 'for_review',
        );

        $url = '/transactions?view=waiting_on_others&search=budget&status=for_review&priority=high&office_id='.$budget->id;
        $response = $this->actingAs($actor)->get($url)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 1)
                ->where('records.data.0.id', $match->id)
                ->where('filters.search', 'budget')
                ->where('filters.status', 'for_review')
                ->where('filters.priority', 'high')
                ->where('filters.office_id', $budget->id));

        $this->assertStringNotContainsString($hidden->title, $response->getContent());
    }

    public function test_high_priority_view_is_rejected_but_priority_filters_continue_to_work(): void
    {
        $office = $this->department('PRIORITY');
        $actor = $this->human('department_head', $office);

        $high = $this->transaction('High priority work', $office, $office, $actor, dueAt: now()->addDays(2), priority: 'high');
        $urgent = $this->transaction('Urgent priority work', $office, $office, $actor, dueAt: now()->addDay(), priority: 'urgent');
        $this->transaction('Normal priority work', $office, $office, $actor, dueAt: now()->addDays(3), priority: 'normal');

        $this->actingAs($actor)
            ->getJson('/transactions?view=high_priority')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('view');

        $this->actingAs($actor)->get('/transactions?view=all&priority=high')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 1)
                ->where('records.data.0.id', $high->id));

        $this->actingAs($actor)->get('/transactions?view=all&priority=urgent')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 1)
                ->where('records.data.0.id', $urgent->id));
    }

    public function test_system_admin_my_work_does_not_become_a_municipality_wide_document_reader(): void
    {
        $adminOffice = $this->department('GLOBAL-ADMIN');
        $other = $this->department('GLOBAL-OTHER');
        $target = $this->department('GLOBAL-TARGET');
        $admin = $this->human('system_admin', $adminOffice);
        $creator = $this->human('department_head', $other);
        $targetStaff = $this->human('department_staff', $target);

        $municipal = $this->transaction(
            'Municipality wide urgent item',
            $other,
            $target,
            $creator,
            $targetStaff->employee,
            now()->addDays(2),
            priority: 'urgent',
        );

        $this->actingAs($admin)->get('/transactions?view=all&priority=urgent&office_id='.$target->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('experience.profile', 'system_administration')
                ->where('experience.hasOfficeScope', false)
                ->has('scopeGroups', 1)
                ->where('records.total', 0)
                ->has('filterOptions.offices', 1));

        $this->assertDatabaseHas('transactions', ['id' => $municipal->id]);
    }

    public function test_queue_paginates_server_side_after_authorization_and_all_projection(): void
    {
        $office = $this->department('PAGE');
        $actor = $this->human('department_head', $office);

        foreach (range(1, 26) as $number) {
            $this->transaction(
                "Queue page {$number}",
                $office,
                $office,
                $actor,
                dueAt: now()->addDays(2)->addMinutes($number),
            );
        }

        $this->actingAs($actor)->get('/transactions?view=all&page=2')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 26)
                ->where('records.current_page', 2)
                ->where('records.last_page', 2)
                ->has('records.data', 1));
    }

    public function test_office_work_queue_query_count_does_not_grow_per_staff_row(): void
    {
        $office = $this->department('QUERY');
        $head = $this->human('department_head', $office);
        $first = $this->human('department_staff', $office);
        $this->transaction('Queue query baseline', $office, $office, $head, $first->employee, now()->addDays(2));

        $baseline = $this->queueQueryCount($head->fresh());

        foreach (range(1, 15) as $number) {
            $staff = $this->human('department_staff', $office);
            $this->transaction("Queue query {$number}", $office, $office, $head, $staff->employee, now()->addDays(2));
        }

        $expanded = $this->queueQueryCount($head->fresh());

        $this->assertSame($baseline, $expanded);
        $this->assertLessThanOrEqual(30, $expanded);
    }

    public function test_existing_transaction_detail_and_transition_actions_remain_unchanged(): void
    {
        $office = $this->department('DETAIL');
        $actor = $this->human('department_head', $office);
        $transaction = $this->transaction(
            'Existing detail action',
            $office,
            $office,
            $actor,
            dueAt: now()->addDays(2),
        );

        $this->actingAs($actor)
            ->get('/transactions/'.$transaction->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Transactions/Show')
                ->where('transaction.id', $transaction->id));

        $this->actingAs($actor)
            ->post('/transactions/'.$transaction->id.'/transition', [
                'action' => 'mark_review',
                'remarks' => 'Existing transition contract remains authoritative.',
            ])
            ->assertRedirect('/transactions/'.$transaction->id);

        $this->assertSame('for_review', $transaction->fresh()->status);
        $this->assertDatabaseHas('transaction_events', [
            'transaction_id' => $transaction->id,
            'action' => 'mark_review',
            'remarks' => 'Existing transition contract remains authoritative.',
        ]);
    }

    private function assertSingleView(User $actor, string $view, WorkflowTransaction $expected): void
    {
        $this->actingAs($actor)->get('/transactions?view='.$view)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.view', $view)
                ->where('records.total', 1)
                ->where('records.data.0.id', $expected->id));
    }

    private function queueQueryCount(User $actor): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        app(WorkQueueQuery::class)->workspace($actor, ['view' => 'office_queue']);
        $count = count(DB::getQueryLog());

        DB::disableQueryLog();

        return $count;
    }

    private function department(string $suffix): Department
    {
        return Department::query()->create([
            'code' => 'QUEUE-'.Str::upper(Str::random(5)).'-'.$suffix,
            'name' => 'Queue '.$suffix,
            'short_name' => 'Q-'.$suffix,
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
            'name' => 'Queue '.$role.' '.Str::random(5),
            'email' => Str::lower(Str::random(10)).'@example.test',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);

        Employee::query()->create([
            'employee_number' => 'QUEUE-EMP-'.Str::upper(Str::random(10)),
            'full_name' => $user->name,
            'work_email' => $user->email,
            'user_id' => $user->id,
            'department_id' => $department->id,
            'position_title' => 'Queue Officer',
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
        string $priority = 'normal',
        string $status = 'submitted',
        $completedAt = null,
        string $transactionType = 'internal_request',
    ): WorkflowTransaction {
        return WorkflowTransaction::query()->create([
            'reference_no' => 'QUEUE-'.Str::upper(Str::random(12)),
            'transaction_type' => $transactionType,
            'title' => $title,
            'description' => 'Synthetic work queue test transaction.',
            'priority' => $priority,
            'origin_department_id' => $origin->id,
            'current_department_id' => $current->id,
            'created_by_user_id' => $creator->id,
            'assigned_employee_id' => $assignee?->id,
            'status' => $status,
            'received_at' => now()->subHours(4),
            'due_at' => $dueAt,
            'completed_at' => $completedAt,
        ]);
    }
}
