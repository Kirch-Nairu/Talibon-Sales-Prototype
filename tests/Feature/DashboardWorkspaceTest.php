<?php

namespace Tests\Feature;

use App\Domain\Correspondence\CorrespondenceClassification;
use App\Domain\Correspondence\CorrespondenceLifecycleState;
use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkflowTransaction;
use App\Services\DashboardWorkspaceQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_normal_portal_auth_active_and_mfa_assurance(): void
    {
        $office = $this->department('ACCESS');

        $this->get('/dashboard')->assertRedirect('/login');

        $inactive = $this->human('department_head', $office, false);
        Auth::guard('web')->login($inactive);
        $this->get('/dashboard')->assertRedirect('/login');

        $mfaPending = $this->human('department_head', $office);
        Auth::guard('web')->login($mfaPending);
        $this->get('/dashboard')->assertRedirect(route('mfa.enroll'));
    }

    public function test_department_head_receives_separate_personal_and_office_dashboard_contracts(): void
    {
        $own = $this->department('OWN');
        $other = $this->department('OTHER');
        $third = $this->department('THIRD');
        $actor = $this->human('department_head', $own);
        $officeStaff = $this->human('department_staff', $own);
        $otherStaff = $this->human('department_staff', $other);
        $otherHead = $this->human('department_head', $other);

        $this->transaction('Mine active', $own, $own, $actor, $actor->employee, now()->addDays(2));
        $this->transaction('Office unassigned', $other, $own, $actor, null, now()->addDays(2));
        $this->transaction('Due soon office item', $other, $own, $actor, $officeStaff->employee, now()->addHours(6));
        $this->transaction('Waiting and overdue', $own, $other, $actor, $otherStaff->employee, now()->subDay());
        $this->transaction(
            'Completed current month',
            $own,
            $own,
            $actor,
            $actor->employee,
            now()->subDays(2),
            status: 'approved',
            completedAt: now()->subDay(),
        );
        $legacyTerminal = $this->transaction(
            'Terminal without completion timestamp',
            $own,
            $own,
            $actor,
            $actor->employee,
            now()->subDays(2),
            status: 'approved',
            completedAt: null,
        );
        $legacyTerminal->touch();

        $this->transaction(
            'Unrelated overdue',
            $other,
            $third,
            $otherHead,
            $otherStaff->employee,
            now()->subDays(2),
        );

        $this->actingAs($actor)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('experience.key', 'department_head')
                ->where('experience.scopes.personal', true)
                ->where('experience.scopes.office', true)
                ->where('experience.scopes.municipal', false)
                ->where('experience.capabilities.openOfficeWorkspace', true)
                ->where('experience.department.name', $own->name)
                ->where('metricGroups.0.key', 'personal')
                ->where('metricGroups.0.metrics.0.label', 'Needs Action')
                ->where('metricGroups.0.metrics.0.value', 1)
                ->where('metricGroups.1.key', 'office')
                ->where('officeOverview.metrics.active.value', 3)
                ->where('officeOverview.metrics.incoming.value', 2)
                ->where('officeOverview.metrics.outgoing.value', 1)
                ->where('officeOverview.metrics.overdue.value', 0)
                ->where('officeOverview.metrics.unassigned.value', 1)
                ->where('officeOverview.metrics.recentlyCompleted.value', 1)
                ->where('officeOverview.metrics.escalations.value', 0)
                ->has('officeOverview.staffWorkload', 2)
                ->has('officeOverview.oldestUnresolved', 3)
                ->missing('executiveOverview')
                ->missing('systemOverview'));
    }

    public function test_employee_dashboard_contains_personal_work_without_office_leadership_aggregates(): void
    {
        $own = $this->department('EMPLOYEE-OWN');
        $other = $this->department('EMPLOYEE-OTHER');
        $third = $this->department('EMPLOYEE-THIRD');
        $actor = $this->human('department_staff', $own);
        $head = $this->human('department_head', $own);
        $otherHead = $this->human('department_head', $other);

        $mine = $this->transaction('My assigned action', $other, $own, $head, $actor->employee, now()->addHours(6));
        $outgoing = $this->transaction('My outgoing request', $own, $other, $actor, dueAt: now()->addDays(3));
        $officeOnly = $this->transaction('Office-only unassigned work', $other, $own, $head, dueAt: now()->addDays(2));
        $unrelated = $this->transaction('Unrelated other-office work', $other, $third, $otherHead);

        $response = $this->actingAs($actor)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('experience.key', 'employee')
                ->where('experience.scopes.personal', true)
                ->where('experience.scopes.office', false)
                ->where('experience.scopes.municipal', false)
                ->where('experience.scopes.system', false)
                ->where('experience.capabilities.openOfficeWorkspace', false)
                ->has('metricGroups', 1)
                ->where('metricGroups.0.key', 'personal')
                ->where('metricGroups.0.metrics.0.value', 1)
                ->where('metricGroups.0.metrics.2.value', 1)
                ->where('metricGroups.0.metrics.5.value', 1)
                ->has('recentWork', 2)
                ->missing('officeOverview')
                ->missing('executiveOverview')
                ->missing('systemOverview')
                ->missing('correspondenceAttention')
                ->missing('departmentMetrics'));

        $this->assertStringContainsString($mine->title, $response->getContent());
        $this->assertStringContainsString($outgoing->title, $response->getContent());
        $this->assertStringNotContainsString($officeOnly->title, $response->getContent());
        $this->assertStringNotContainsString($unrelated->title, $response->getContent());
    }

    public function test_specialist_staff_remain_employee_profile_while_mayor_staff_requires_mayor_office_context(): void
    {
        $hr = $this->human('hr_officer', $this->department('HR', code: 'HRMO'));
        $legislative = $this->human('legislative_staff', $this->department('LEG', code: 'SB'));
        $mayorStaff = $this->human('mayor_staff', $this->department('MAYOR-STAFF', code: 'MAYOR'));
        $misplacedMayorStaff = $this->human('mayor_staff', $this->department('MAYOR-OTHER'));

        foreach ([$hr, $legislative, $misplacedMayorStaff] as $employeeProfile) {
            $this->actingAs($employeeProfile)->get('/dashboard')
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('experience.key', 'employee')
                    ->where('experience.scopes.office', false)
                    ->where('experience.scopes.municipal', false));
        }

        $this->actingAs($mayorStaff)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('experience.key', 'executive_oversight')
                ->where('experience.scopes.municipal', true)
                ->where('experience.department.code', 'MAYOR'));
    }

    public function test_executive_aggregate_does_not_expose_other_office_restricted_correspondence(): void
    {
        $mayor = $this->department('EXEC-CORR-MAYOR', code: 'MAYOR');
        $other = $this->department('EXEC-CORR-OTHER');
        $actor = $this->human('mayor_approver', $mayor);
        $restricted = $this->correspondence(
            'Other office restricted executive aggregate sentinel',
            $other,
            CorrespondenceClassification::Restricted,
            CorrespondenceLifecycleState::Classified,
        );

        $response = $this->actingAs($actor)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('experience.key', 'executive_oversight')
                ->where('correspondenceOverview.status.2.count', 0)
                ->has('correspondenceOverview.recentlyReceived', 0)
                ->has('correspondenceOverview.recentlyRouted', 0));

        $this->assertStringNotContainsString($restricted->subject, $response->getContent());
        $this->assertStringNotContainsString($restricted->public_id, $response->getContent());
    }

    public function test_department_dashboard_query_count_is_constant_as_staff_workload_grows(): void
    {
        $office = $this->department('QUERY-COUNT');
        $head = $this->human('department_head', $office);
        $creator = $head;
        $firstStaff = $this->human('department_staff', $office);
        $this->transaction(
            'Query workload baseline',
            $office,
            $office,
            $creator,
            $firstStaff->employee,
            now()->addDays(2),
        );

        $baseline = $this->dashboardQueryCount($head->fresh());

        foreach (range(1, 15) as $number) {
            $staff = $this->human('department_staff', $office);
            $this->transaction(
                "Query workload {$number}",
                $office,
                $office,
                $creator,
                $staff->employee,
                now()->addDays(2),
            );
        }

        $expanded = $this->dashboardQueryCount($head->fresh());

        $this->assertLessThanOrEqual($baseline + 1, $expanded);
        $this->assertLessThanOrEqual(35, $expanded);
    }

    public function test_correspondence_status_attention_and_recent_lists_respect_existing_visibility(): void
    {
        $own = $this->department('CORR-OWN');
        $other = $this->department('CORR-OTHER');
        $staff = $this->human('department_staff', $own);

        $intake = $this->correspondence(
            'Fresh RECEIVE intake',
            null,
            null,
            CorrespondenceLifecycleState::Received,
            receivedAt: now()->subMinutes(5),
        );
        $registered = $this->correspondence(
            'Registered office matter',
            $own,
            null,
            CorrespondenceLifecycleState::Registered,
            receivedAt: now()->subMinutes(15),
        );
        $routedWorkflow = $this->transaction(
            'Routed correspondence workflow',
            $own,
            $own,
            $staff,
            $staff->employee,
            now()->addDay(),
            transactionType: 'document_review',
            status: 'for_review',
        );
        $routed = $this->correspondence(
            'Recently routed visible matter',
            $own,
            CorrespondenceClassification::Internal,
            CorrespondenceLifecycleState::InAction,
            $routedWorkflow,
            now()->subMinutes(30),
            now()->subMinutes(10),
        );
        $awaitingWorkflow = $this->transaction(
            'Awaiting correspondence action',
            $own,
            $own,
            $staff,
            $staff->employee,
            now()->addDay(),
            transactionType: 'document_review',
            status: 'for_review',
        );
        $this->correspondence(
            'Routed awaiting action',
            $own,
            CorrespondenceClassification::Internal,
            CorrespondenceLifecycleState::Routed,
            $awaitingWorkflow,
            now()->subMinutes(25),
            now()->subMinutes(20),
        );
        $confidential = $this->correspondence(
            'Confidential hidden matter',
            $own,
            CorrespondenceClassification::Confidential,
            CorrespondenceLifecycleState::Classified,
            receivedAt: now()->subMinute(),
        );
        $restricted = $this->correspondence(
            'Restricted hidden matter',
            $own,
            CorrespondenceClassification::Restricted,
            CorrespondenceLifecycleState::Classified,
            receivedAt: now()->subMinutes(2),
        );
        $otherOffice = $this->correspondence(
            'Other office hidden routed matter',
            $other,
            CorrespondenceClassification::Internal,
            CorrespondenceLifecycleState::Routed,
            receivedAt: now()->subMinutes(3),
            routedAt: now()->subMinute(),
        );

        $response = $this->actingAs($staff)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('correspondenceOverview.attention.value', 2)
                ->where('correspondenceOverview.attention.link', '/correspondence?action_required=1')
                ->where('correspondenceOverview.status.0.lifecycle', 'received')
                ->where('correspondenceOverview.status.0.count', 1)
                ->where('correspondenceOverview.status.1.lifecycle', 'registered')
                ->where('correspondenceOverview.status.1.count', 1)
                ->where('correspondenceOverview.status.2.lifecycle', 'classified')
                ->where('correspondenceOverview.status.2.count', 0)
                ->where('correspondenceOverview.status.3.lifecycle', 'routed')
                ->where('correspondenceOverview.status.3.count', 1)
                ->where('correspondenceOverview.status.4.lifecycle', 'in_action')
                ->where('correspondenceOverview.status.4.count', 1)
                ->has('correspondenceOverview.status', 5)
                ->where('correspondenceOverview.status.0.link', '/correspondence?lifecycle=received')
                ->where('correspondenceOverview.recentlyReceived.0.subject', $intake->subject)
                ->where('correspondenceOverview.recentlyRouted.0.subject', $routed->subject)
                ->where('correspondenceOverview.recentlyRouted.0.detailUrl', '/correspondence/'.$routed->public_id.'/workspace'));

        foreach ([$confidential, $restricted, $otherOffice] as $hidden) {
            $this->assertStringNotContainsString($hidden->subject, $response->getContent());
            $this->assertStringNotContainsString($hidden->public_id, $response->getContent());
        }

        $this->assertStringContainsString($registered->subject, $response->getContent());
    }

    public function test_system_admin_transaction_oversight_does_not_expand_correspondence_visibility(): void
    {
        $adminOffice = $this->department('ADMIN');
        $other = $this->department('ADMIN-OTHER');
        $mayor = $this->department('MAYOR-OFFICE', code: 'MAYOR');
        $admin = $this->human('system_admin', $adminOffice);
        $otherHead = $this->human('department_head', $other);

        $this->transaction('Municipal visible transaction', $other, $mayor, $otherHead, dueAt: now()->addDays(2));
        $restricted = $this->correspondence(
            'Restricted other-office correspondence',
            $other,
            CorrespondenceClassification::Restricted,
            CorrespondenceLifecycleState::Classified,
        );

        $response = $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('experience.key', 'system_administration')
                ->where('experience.scopes.system', true)
                ->where('experience.scopes.personal', false)
                ->where('experience.capabilities.openSystemAdministration', true)
                ->has('metricGroups.0.metrics', 6)
                ->has('systemOverview.security.recentEvents', 0)
                ->where('systemOverview.operations.summary.activeMunicipalWork', 1)
                ->where('systemOverview.operations.summary.executiveQueue', 1)
                ->has('recentWork', 0)
                ->missing('correspondenceOverview')
                ->missing('officeOverview')
                ->missing('executiveOverview'));

        $this->assertStringNotContainsString($restricted->subject, $response->getContent());
    }

    public function test_recent_work_is_authorized_and_uses_existing_detail_urls_and_due_states(): void
    {
        $own = $this->department('RECENT-OWN');
        $other = $this->department('RECENT-OTHER');
        $third = $this->department('RECENT-THIRD');
        $actor = $this->human('department_head', $own);
        $otherHead = $this->human('department_head', $other);

        $visible = $this->transaction(
            'Visible recent work',
            $own,
            $other,
            $actor,
            dueAt: now()->subHour(),
            transactionType: 'funding_request',
        );
        $hidden = $this->transaction(
            'Hidden recent work',
            $other,
            $third,
            $otherHead,
            dueAt: now()->subHour(),
        );

        $response = $this->actingAs($actor)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('recentWork.0.reference', $visible->reference_no)
                ->where('recentWork.0.transactionType', 'Funding Request')
                ->where('recentWork.0.dueState', 'overdue')
                ->where('recentWork.0.detailUrl', '/transactions/'.$visible->id));

        $this->assertStringNotContainsString($hidden->title, $response->getContent());
    }

    public function test_view_all_actor_receives_exact_municipal_metrics_and_grouped_office_workload(): void
    {
        $adminOffice = $this->department('EXEC-ADMIN');
        $mayor = $this->department('EXEC-MAYOR', code: 'MAYOR');
        $budget = $this->department('EXEC-BUDGET');
        $engineering = $this->department('EXEC-ENG');
        $admin = $this->human('mayor_approver', $mayor);
        $creator = $this->human('department_head', $budget);
        $engineer = $this->human('department_staff', $engineering);

        $this->transaction('Mayor queue item', $budget, $mayor, $creator, null, now()->addHours(8));
        $this->transaction('Engineering overdue', $budget, $engineering, $creator, $engineer->employee, now()->subDay());
        $this->transaction('Engineering unassigned', $budget, $engineering, $creator, null, now()->addDays(3));
        $this->transaction(
            'Completed this month',
            $engineering,
            $budget,
            $creator,
            $engineer->employee,
            now()->subDays(2),
            status: 'approved',
            completedAt: now()->startOfMonth(),
        );
        $legacy = $this->transaction(
            'Legacy terminal no completion',
            $engineering,
            $budget,
            $creator,
            $engineer->employee,
            now()->subDays(2),
            status: 'approved',
            completedAt: null,
        );
        $legacy->touch();

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('experience.key', 'executive_oversight')
                ->where('experience.scopes.municipal', true)
                ->where('experience.scopes.system', false)
                ->where('experience.department.code', 'MAYOR')
                ->where('executiveOverview.summary.activeMunicipalWork', 3)
                ->where('executiveOverview.summary.municipalOverdue', 1)
                ->where('executiveOverview.summary.municipalUnassigned', 2)
                ->where('executiveOverview.summary.dueSoon', 1)
                ->where('executiveOverview.summary.executiveQueue', 1)
                ->where('executiveOverview.summary.completedThisMonth', 1)
                ->where('executiveOverview.departmentWorkload.0.code', $engineering->code)
                ->where('executiveOverview.departmentWorkload.0.active', 2)
                ->where('executiveOverview.departmentWorkload.0.unassigned', 1)
                ->where('executiveOverview.departmentWorkload.0.overdue', 1)
                ->where('executiveOverview.departmentWorkload.1.code', 'MAYOR')
                ->where('executiveOverview.departmentWorkload.1.active', 1)
                ->where('executiveOverview.departmentWorkload.1.unassigned', 1)
                ->where('executiveOverview.departmentWorkload.1.dueSoon', 1)
                ->where('executiveOverview.metrics.pendingExecutiveAction.value', 0)
                ->has('executiveOverview.oldestUnresolved', 3)
                ->has('executiveOverview.recentlyCompleted', 1)
                ->missing('officeOverview')
                ->missing('systemOverview'));
    }

    public function test_dashboard_deep_links_and_parked_scope_remain_current_only(): void
    {
        $office = $this->department('LINKS');
        $actor = $this->human('department_head', $office);

        $this->actingAs($actor)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('metricGroups.0.metrics.0.link', '/transactions?view=needs_my_action')
                ->where('metricGroups.0.metrics.4.link', '/transactions?view=recently_updated')
                ->where('officeOverview.metrics.active.link', '/transactions?view=office_queue')
                ->where('officeOverview.metrics.overdue.link', '/transactions?view=escalations')
                ->where('officeOverview.metrics.waitingExternally.link', '/transactions?view=office_queue')
                ->where('officeOverview.metrics.unassigned.link', '/transactions?view=unassigned')
                ->where('correspondenceOverview.attention.link', '/correspondence?action_required=1'));

        $pageSource = file_get_contents(resource_path('js/pages/Dashboard.tsx'));
        $querySource = file_get_contents(app_path('Services/DashboardWorkspaceQuery.php'))
            .file_get_contents(app_path('Services/DashboardExperienceResolver.php'))
            .file_get_contents(app_path('Services/DashboardTransactionQuery.php'))
            .file_get_contents(app_path('Services/DashboardCorrespondenceQuery.php'))
            .file_get_contents(app_path('Services/DashboardPersonalQuery.php'))
            .file_get_contents(app_path('Services/DashboardOfficeQuery.php'))
            .file_get_contents(app_path('Services/DashboardExecutiveQuery.php'));

        $this->assertIsString($pageSource);
        $this->assertStringContainsString("'url' => '/records'", $querySource);
        $this->assertStringContainsString("'url' => '/correspondence'", $querySource);
        $this->assertStringContainsString("'url' => '/transactions?view=needs_my_action'", $querySource);

        foreach ([
            'Hris',
            'Legislative',
            'Property',
            'Payroll',
            'Dtr',
            'Asset',
            'Procurement',
            'ProjectMonitoring',
        ] as $parked) {
            $this->assertStringNotContainsString($parked, $querySource);
        }

        foreach (['For Review', 'Incoming', 'High Priority', 'Approved Today'] as $oldMetric) {
            $this->assertStringNotContainsString($oldMetric, $pageSource);
        }
    }

    private function dashboardQueryCount(User $actor): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        app(DashboardWorkspaceQuery::class)->workspace($actor);
        $count = count(DB::getQueryLog());

        DB::disableQueryLog();

        return $count;
    }

    private function department(string $suffix, ?string $code = null): Department
    {
        return Department::query()->create([
            'code' => $code ?? 'DASH-'.Str::upper(Str::random(5)).'-'.$suffix,
            'name' => 'Dashboard '.$suffix,
            'short_name' => 'D-'.$suffix,
            'branch' => 'executive',
            'office_type' => 'department',
            'sort_order' => 10,
            'is_routable' => true,
            'is_active' => true,
        ]);
    }

    private function human(string $role, Department $department, bool $active = true): User
    {
        $user = User::query()->create([
            'name' => 'Dashboard '.$role.' '.Str::random(5),
            'email' => Str::lower(Str::random(10)).'@example.test',
            'password' => 'password',
            'role' => $role,
            'is_active' => $active,
        ]);

        Employee::query()->create([
            'employee_number' => 'DASH-EMP-'.Str::upper(Str::random(10)),
            'full_name' => $user->name,
            'work_email' => $user->email,
            'user_id' => $user->id,
            'department_id' => $department->id,
            'position_title' => 'Dashboard Officer',
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
        string $transactionType = 'internal_request',
        string $status = 'submitted',
        $completedAt = null,
    ): WorkflowTransaction {
        return WorkflowTransaction::query()->create([
            'reference_no' => 'DASH-TX-'.Str::upper(Str::random(10)),
            'transaction_type' => $transactionType,
            'title' => $title,
            'description' => 'Synthetic dashboard test transaction.',
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
        ?Department $department,
        ?CorrespondenceClassification $classification = CorrespondenceClassification::Internal,
        CorrespondenceLifecycleState $state = CorrespondenceLifecycleState::Classified,
        ?WorkflowTransaction $workflow = null,
        $receivedAt = null,
        $routedAt = null,
    ): CorrespondenceRecord {
        return CorrespondenceRecord::query()->create([
            'public_id' => (string) Str::uuid(),
            'external_reference_no' => 'DASH-EXT-'.Str::upper(Str::random(10)),
            'source' => 'email',
            'channel' => 'dashboard_test',
            'sender_name' => 'Dashboard Sender',
            'sender_organization' => 'Dashboard Source Office',
            'subject' => $subject,
            'summary' => 'Synthetic dashboard correspondence.',
            'received_at' => $receivedAt ?? now()->subHours(3),
            'receiving_department_id' => $department?->id,
            'municipal_reference_no' => $state === CorrespondenceLifecycleState::Received
                ? null
                : 'DASH-COR-'.Str::upper(Str::random(10)),
            'classification' => $classification?->value,
            'lifecycle_state' => $state->value,
            'workflow_transaction_id' => $workflow?->id,
            'routed_at' => $routedAt,
        ]);
    }
}
