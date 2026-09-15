<?php

namespace Tests\Feature;

use App\Models\CorrespondenceEvent;
use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentLink;
use App\Models\Employee;
use App\Models\TransactionEvent;
use App\Models\User;
use App\Models\WorkflowTransaction;
use App\Services\CorrespondenceTraceQuery;
use App\Services\Reports\CorePortalReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CorePortalReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_catalog_and_shared_navigation_permission_are_server_authoritative(): void
    {
        $office = $this->department('ACCESS');
        $head = $this->human('department_head', $office);
        $outsider = User::query()->create([
            'name' => 'No municipal identity', 'email' => 'outside@example.test',
            'password' => 'password', 'role' => 'employee', 'is_active' => true,
        ]);

        $this->actingAs($head)->get('/reports')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Index')
            ->where('catalog', fn ($catalog) => $catalog->pluck('key')->all() === [
                'office-workload', 'transaction-aging', 'correspondence-status',
                'document-movement', 'completed-work', 'overdue-action-required',
            ])
            ->where('permissions.reports', true));
        $this->actingAs($outsider)->get('/reports')->assertForbidden();
        $this->actingAs($outsider)->get('/reports/export/transaction-aging')->assertForbidden();
        $this->actingAs($head)->get('/reports/export/payroll-summary')->assertNotFound();
    }

    public function test_office_workload_is_grouped_from_only_authorized_transactions(): void
    {
        Carbon::setTestNow('2026-08-25 12:00:00');
        $own = $this->department('OWN');
        $other = $this->department('OTHER');
        $head = $this->human('department_head', $own);
        $otherUser = $this->human('department_head', $other);
        $this->transaction($head, $own, $own, ['due_at' => now()->subDay(), 'assigned_employee_id' => $head->employee->id]);
        $this->transaction($head, $own, $own, ['due_at' => now()->addDay(), 'assigned_employee_id' => null]);
        $this->transaction($head, $own, $own, ['status' => 'approved', 'completed_at' => now()->subDays(2)]);
        $hidden = $this->transaction($otherUser, $other, $other, ['due_at' => now()->subDay()]);

        $response = $this->actingAs($head)->get('/reports?report=office-workload&date_from=2026-08-20&date_to=2026-08-25')->assertOk();
        $rows = $this->props($response)['result']['data'];
        $ownRow = collect($rows)->firstWhere('officeId', $own->id);

        $this->assertSame(2, $ownRow['active']);
        $this->assertSame(1, $ownRow['overdue']);
        $this->assertSame(1, $ownRow['completed']);
        $this->assertSame(1, $ownRow['assigned']);
        $this->assertSame(1, $ownRow['unassigned']);
        $this->assertFalse(collect($rows)->contains('officeId', $other->id));
        $this->assertStringNotContainsString($hidden->reference_no, $response->getContent());
    }

    public function test_global_transaction_capability_sees_municipal_workload_without_expanding_correspondence(): void
    {
        $adminOffice = $this->department('ADMIN');
        $other = $this->department('GLOBAL');
        $admin = $this->human('system_admin', $adminOffice);
        $head = $this->human('department_head', $other);
        $this->transaction($head, $other, $other);
        $this->correspondence($other, ['classification' => 'restricted', 'subject' => 'Hidden Restricted Brief']);

        $workload = $this->props($this->actingAs($admin)->get('/reports')->assertOk());
        $this->assertTrue(collect($workload['result']['data'])->contains('officeId', $other->id));
        $status = $this->props($this->actingAs($admin)->get('/reports?report=correspondence-status')->assertOk());
        $this->assertSame(0, $status['result']['total']);
        $this->assertStringNotContainsString('Hidden Restricted Brief', json_encode($status));
    }

    public function test_transaction_aging_calculates_authoritative_age_due_state_and_scope(): void
    {
        Carbon::setTestNow('2026-08-25 12:00:00');
        $own = $this->department('AGING');
        $other = $this->department('AGING-HIDDEN');
        $head = $this->human('department_head', $own);
        $otherHead = $this->human('department_head', $other);
        $visible = $this->transaction($head, $own, $own, [
            'title' => 'Visible aging row', 'received_at' => now()->subDays(3),
            'due_at' => now()->subDay(), 'priority' => 'urgent', 'assigned_employee_id' => $head->employee->id,
        ]);
        $hidden = $this->transaction($otherHead, $other, $other, ['title' => 'Hidden aging row']);

        $props = $this->props($this->actingAs($head)->get('/reports?report=transaction-aging')->assertOk());
        $row = collect($props['result']['data'])->firstWhere('id', $visible->id);
        $this->assertSame('Visible aging row', $row['title']);
        $this->assertSame('Urgent', $row['priority']);
        $this->assertSame('Overdue', $row['dueState']);
        $this->assertSame('1 day', $row['overdueBy']);
        $this->assertSame('3 days', $row['age']);
        $this->assertFalse(collect($props['result']['data'])->contains('id', $hidden->id));
    }

    public function test_transaction_filters_validate_and_cannot_widen_office_scope(): void
    {
        $own = $this->department('FILTER');
        $hidden = $this->department('FILTER-HIDDEN');
        $head = $this->human('department_head', $own);
        $hiddenHead = $this->human('department_head', $hidden);
        $this->transaction($head, $own, $own, ['priority' => 'high', 'transaction_type' => 'funding_request']);
        $this->transaction($hiddenHead, $hidden, $hidden, ['priority' => 'urgent']);

        $this->actingAs($head)->get('/reports?report=transaction-aging&priority=high&transaction_type=funding_request')->assertOk();
        $this->actingAs($head)->get('/reports?report=transaction-aging&office='.$hidden->id)->assertForbidden();
        $this->actingAs($head)->get('/reports?report=transaction-aging&priority=extreme')->assertSessionHasErrors('priority');
        $this->actingAs($head)->get('/reports?report=transaction-aging&date_from=2026-08-26&date_to=2026-08-25')->assertSessionHasErrors('date_to');
        $this->actingAs($head)->get('/reports?report=correspondence-status&priority=high')->assertSessionHasErrors('priority');
    }

    public function test_correspondence_status_uses_restricted_scope_and_current_accountability(): void
    {
        $own = $this->department('CORR');
        $target = $this->department('CORR-TARGET');
        $other = $this->department('CORR-OTHER');
        $head = $this->human('department_head', $own);
        $assignee = $this->human('department_head', $target);
        $staff = $this->human('department_staff', $target);
        $otherHead = $this->human('department_head', $other);
        $transaction = $this->transaction($head, $own, $target, ['assigned_employee_id' => $assignee->employee->id]);
        $visible = $this->correspondence($own, [
            'classification' => 'restricted', 'workflow_transaction_id' => $transaction->id,
            'subject' => 'Authorized restricted record', 'municipal_reference_no' => 'MUNI-001',
        ]);
        CorrespondenceEvent::query()->create([
            'correspondence_record_id' => $visible->id, 'event' => 'routed',
            'new_lifecycle_state' => 'routed', 'actor_user_id' => $head->id,
            'office_department_id' => $own->id, 'occurred_at' => now()->subHour(),
        ]);
        $hidden = $this->correspondence($other, ['classification' => 'restricted', 'subject' => 'Other restricted record']);

        $props = $this->props($this->actingAs($staff)->get('/reports?report=correspondence-status')->assertOk());
        $this->assertSame(0, $props['result']['total']);
        $props = $this->props($this->actingAs($assignee)->get('/reports?report=correspondence-status')->assertOk());
        $row = collect($props['result']['data'])->firstWhere('id', $visible->public_id);
        $this->assertSame('CORR-TARGET', $row['accountableOffice']);
        $this->assertSame($assignee->name, $row['assignee']);
        $this->assertNotNull($row['lastMovementAt']);
        $this->assertFalse(collect($props['result']['data'])->contains('id', $hidden->public_id));
        $this->assertSame(1, $props['result']['total']);
    }

    public function test_document_movement_merges_trace_suppresses_duplicate_route_and_preserves_evidence(): void
    {
        Carbon::setTestNow('2026-08-25 12:00:00');
        $origin = $this->department('MOVE-FROM');
        $target = $this->department('MOVE-TO');
        $final = $this->department('MOVE-FINAL');
        $head = $this->human('department_head', $origin);
        $transaction = $this->transaction($head, $origin, $origin);
        $record = $this->correspondence($origin, [
            'workflow_transaction_id' => $transaction->id, 'municipal_reference_no' => 'MOVE-001',
            'subject' => 'Movement parity record', 'classification' => 'internal',
        ]);
        $route = CorrespondenceEvent::query()->create([
            'correspondence_record_id' => $record->id, 'event' => 'routed',
            'new_lifecycle_state' => 'routed', 'actor_user_id' => $head->id,
            'office_department_id' => $origin->id, 'remarks' => 'Initial authoritative route.',
            'metadata' => ['target_department_id' => $target->id], 'occurred_at' => now()->subHours(2),
        ]);
        $submitted = TransactionEvent::query()->create([
            'transaction_id' => $transaction->id, 'actor_user_id' => $head->id,
            'from_department_id' => $origin->id, 'to_department_id' => $target->id,
            'action' => 'submitted', 'new_status' => 'submitted', 'remarks' => 'Duplicate handoff.',
            'created_at' => now()->subHours(2),
        ]);
        $forward = TransactionEvent::query()->create([
            'transaction_id' => $transaction->id, 'actor_user_id' => $head->id,
            'from_department_id' => $target->id, 'to_department_id' => $final->id,
            'action' => 'forward', 'new_status' => 'submitted', 'remarks' => 'Later forward.',
            'created_at' => now()->subHour(),
        ]);
        $document = Document::query()->create(['title' => 'Route evidence']);
        DocumentLink::query()->create([
            'document_id' => $document->id, 'linkable_type' => $submitted->getMorphClass(),
            'linkable_id' => $submitted->id, 'relationship' => 'route_evidence', 'created_by_user_id' => $head->id,
        ]);

        $props = $this->props($this->actingAs($head)->get('/reports?report=document-movement')->assertOk());
        $rows = collect($props['result']['data']);
        $routeRow = $rows->firstWhere('id', 'correspondence:'.$route->id);
        $forwardRow = $rows->firstWhere('id', 'workflow:'.$forward->id);
        $this->assertFalse($rows->contains('id', 'workflow:'.$submitted->id));
        $this->assertSame('MOVE-FROM', $routeRow['fromOffice']);
        $this->assertSame('MOVE-TO', $routeRow['toOffice']);
        $this->assertSame('Yes', $routeRow['hasEvidence']);
        $this->assertSame('MOVE-TO', $forwardRow['fromOffice']);
        $this->assertSame('MOVE-FINAL', $forwardRow['toOffice']);
        $this->assertSame('Later forward.', $forwardRow['remarks']);
        $this->assertSame($head->name, $forwardRow['actor']);
    }

    public function test_document_movement_route_fallback_and_equal_time_order_match_detail_trace(): void
    {
        $time = Carbon::parse('2026-08-25 09:15:00');
        $origin = $this->department('TIE-FROM');
        $target = $this->department('TIE-TO');
        $head = $this->human('department_head', $origin);
        $transaction = $this->transaction($head, $origin, $origin);
        $record = $this->correspondence($origin, [
            'workflow_transaction_id' => $transaction->id, 'municipal_reference_no' => 'TIE-001',
        ]);
        CorrespondenceEvent::query()->create([
            'correspondence_record_id' => $record->id, 'event' => 'routed', 'new_lifecycle_state' => 'routed',
            'actor_user_id' => $head->id, 'office_department_id' => $origin->id, 'occurred_at' => $time,
        ]);
        TransactionEvent::query()->create([
            'transaction_id' => $transaction->id, 'actor_user_id' => $head->id,
            'from_department_id' => $origin->id, 'to_department_id' => $target->id,
            'action' => 'submitted', 'new_status' => 'submitted', 'created_at' => $time,
        ]);
        TransactionEvent::query()->create([
            'transaction_id' => $transaction->id, 'actor_user_id' => $head->id,
            'from_department_id' => $target->id, 'to_department_id' => $origin->id,
            'action' => 'forward', 'new_status' => 'submitted', 'created_at' => $time,
        ]);
        CorrespondenceEvent::query()->create([
            'correspondence_record_id' => $record->id, 'event' => 'in_action',
            'previous_lifecycle_state' => 'routed', 'new_lifecycle_state' => 'in_action',
            'actor_user_id' => $head->id, 'office_department_id' => $origin->id, 'occurred_at' => $time,
        ]);

        $props = $this->props($this->actingAs($head)->get('/reports?report=document-movement')->assertOk());
        $rows = collect($props['result']['data']);
        $this->assertSame(['Routed', 'Forward', 'In Action'], $rows->pluck('event')->all());
        $this->assertSame('TIE-FROM', $rows[0]['fromOffice']);
        $this->assertSame('TIE-TO', $rows[0]['toOffice']);

        $trace = app(CorrespondenceTraceQuery::class)->forRecord($record->fresh('workflowTransaction'))['timeline'];
        $this->assertSame(['routed', 'forward', 'in_action'], collect($trace)->pluck('event')->all());
    }

    public function test_correspondence_filters_and_csv_share_the_same_restricted_scope(): void
    {
        $office = $this->department('CORR-CSV');
        $staff = $this->human('department_staff', $office);
        $visible = $this->correspondence($office, [
            'subject' => 'Visible internal correspondence', 'classification' => 'internal',
            'lifecycle_state' => 'registered', 'municipal_reference_no' => 'CCSV-001',
        ]);
        $this->correspondence($office, [
            'subject' => 'Hidden restricted correspondence', 'classification' => 'restricted',
            'lifecycle_state' => 'registered', 'municipal_reference_no' => 'CCSV-002',
        ]);

        $url = '/reports?report=correspondence-status&classification=internal&lifecycle=registered';
        $props = $this->props($this->actingAs($staff)->get($url)->assertOk());
        $this->assertSame(1, $props['result']['total']);
        $this->assertSame($visible->public_id, $props['result']['data'][0]['id']);
        $this->assertSame(['internal'], $props['filterOptions']['classifications']);

        $response = $this->actingAs($staff)->get('/reports/export/correspondence-status?classification=internal&lifecycle=registered')->assertOk();
        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();
        $this->assertStringContainsString('Visible internal correspondence', $csv);
        $this->assertStringNotContainsString('Hidden restricted correspondence', $csv);
    }

    public function test_completed_and_overdue_reports_use_current_domain_semantics(): void
    {
        Carbon::setTestNow('2026-08-25 12:00:00');
        $office = $this->department('FINAL');
        $head = $this->human('department_head', $office);
        $complete = $this->transaction($head, $office, $office, [
            'status' => 'approved', 'received_at' => now()->subDays(4), 'completed_at' => now()->subDay(),
            'due_at' => now()->subDays(2), 'title' => 'Completed factual work',
        ]);
        $overdue = $this->transaction($head, $office, $office, [
            'status' => 'for_review', 'due_at' => now()->subHours(6), 'title' => 'Outstanding overdue work',
        ]);
        $future = $this->transaction($head, $office, $office, ['due_at' => now()->addDay()]);

        $completed = $this->props($this->actingAs($head)->get('/reports?report=completed-work&date_from=2026-08-24&date_to=2026-08-25')->assertOk());
        $this->assertSame([$complete->id], collect($completed['result']['data'])->pluck('id')->all());
        $this->assertSame('3 days', $completed['result']['data'][0]['processingDuration']);
        $overdueProps = $this->props($this->actingAs($head)->get('/reports?report=overdue-action-required')->assertOk());
        $ids = collect($overdueProps['result']['data'])->pluck('id');
        $this->assertTrue($ids->contains($overdue->id));
        $this->assertFalse($ids->contains($complete->id));
        $this->assertFalse($ids->contains($future->id));
        $this->assertSame('6 hours', collect($overdueProps['result']['data'])->firstWhere('id', $overdue->id)['overdueBy']);
    }

    public function test_row_reports_paginate_and_retain_filters(): void
    {
        $office = $this->department('PAGE');
        $head = $this->human('department_head', $office);
        foreach (range(1, 27) as $index) {
            $this->transaction($head, $office, $office, ['title' => 'Paged '.$index, 'priority' => 'high']);
        }

        $props = $this->props($this->actingAs($head)->get('/reports?report=transaction-aging&priority=high')->assertOk());
        $this->assertCount(25, $props['result']['data']);
        $this->assertSame(27, $props['result']['total']);
        $this->assertSame(2, $props['result']['last_page']);
        $this->assertStringContainsString('priority=high', $props['result']['next_page_url']);
        $this->assertStringContainsString('report=transaction-aging', $props['result']['next_page_url']);
    }

    public function test_office_workload_query_count_does_not_grow_per_office(): void
    {
        $adminOffice = $this->department('QUERY-ADMIN');
        $admin = $this->human('system_admin', $adminOffice);
        foreach (range(1, 8) as $index) {
            $office = $this->department('QUERY-'.$index);
            $creator = $this->human('department_head', $office);
            $this->transaction($creator, $office, $office);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        app(CorePortalReportService::class)->page('office-workload', $admin, []);
        $transactionQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], '"transactions"'));
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(6, $transactionQueries->count());
        $this->assertTrue($transactionQueries->contains(
            fn (array $query): bool => str_contains(strtolower($query['query']), 'group by'),
        ));
    }

    public function test_reports_frontend_uses_shared_permission_and_has_no_report_polling(): void
    {
        $layout = file_get_contents(resource_path('js/layouts/AppLayout.tsx'));
        $page = file_get_contents(resource_path('js/pages/Reports/Index.tsx'));

        $this->assertStringContainsString('pageProps.permissions.reports', $layout);
        $this->assertStringNotContainsString("user?.role === 'department_head'", $layout);
        $this->assertStringNotContainsString('setInterval', $page);
        $this->assertStringNotContainsString('router.reload', $page);
        $this->assertLessThanOrEqual(400, count(file(resource_path('js/pages/Reports/Index.tsx'))));
    }

    public function test_csv_matches_scope_and_filters_and_neutralizes_formula_cells(): void
    {
        $own = $this->department('CSV');
        $other = $this->department('CSV-HIDDEN');
        $head = $this->human('department_head', $own);
        $otherHead = $this->human('department_head', $other);
        foreach (['=SUM(1,1)', '+cmd', '-10+20', '@SUM(A1:A2)', ' =HYPERLINK("x")', 'Safe title'] as $title) {
            $this->transaction($head, $own, $own, ['title' => $title, 'priority' => 'urgent']);
        }
        $this->transaction($otherHead, $other, $other, ['title' => '=HIDDEN']);

        $response = $this->actingAs($head)->get('/reports/export/transaction-aging?priority=urgent')->assertOk();
        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString("'=SUM(1,1)", $csv);
        $this->assertStringContainsString("'+cmd", $csv);
        $this->assertStringContainsString("'-10+20", $csv);
        $this->assertStringContainsString("'@SUM(A1:A2)", $csv);
        $this->assertStringContainsString("' =HYPERLINK", $csv);
        $this->assertStringContainsString('Safe title', $csv);
        $this->assertStringNotContainsString('=HIDDEN', $csv);
    }

    private function props($response): array
    {
        return $response->viewData('page')['props'];
    }

    private function department(string $code): Department
    {
        return Department::query()->create([
            'code' => $code, 'name' => $code.' Office', 'short_name' => $code,
            'branch' => 'executive', 'office_type' => 'department', 'is_routable' => true,
            'sort_order' => 10, 'is_active' => true,
        ]);
    }

    private function human(string $role, Department $department): User
    {
        $user = User::query()->create([
            'name' => $department->code.' '.$role, 'email' => Str::lower(Str::random(10)).'@example.test',
            'password' => 'password', 'role' => $role, 'is_active' => true,
        ]);
        Employee::query()->create([
            'employee_number' => 'EMP-'.Str::upper(Str::random(10)), 'full_name' => $user->name,
            'work_email' => $user->email, 'user_id' => $user->id, 'department_id' => $department->id,
            'position_title' => 'Test Officer', 'employment_status' => 'active',
        ]);

        return $user->fresh('employee.department');
    }

    private function transaction(User $creator, Department $origin, Department $current, array $overrides = []): WorkflowTransaction
    {
        $transaction = WorkflowTransaction::query()->create([
            'transaction_type' => 'internal_request', 'title' => 'Report transaction '.Str::random(5),
            'priority' => 'normal', 'origin_department_id' => $origin->id,
            'current_department_id' => $current->id, 'created_by_user_id' => $creator->id,
            'status' => 'submitted', 'received_at' => now()->subDay(), 'due_at' => now()->addDay(),
            ...$overrides,
        ]);
        $transaction->update(['reference_no' => 'RPT-'.str_pad((string) $transaction->id, 6, '0', STR_PAD_LEFT)]);

        return $transaction->fresh();
    }

    private function correspondence(Department $office, array $overrides = []): CorrespondenceRecord
    {
        return CorrespondenceRecord::query()->create([
            'public_id' => (string) Str::uuid(), 'external_reference_no' => 'EXT-'.Str::upper(Str::random(12)),
            'source' => 'email', 'sender_name' => 'Report Sender', 'sender_organization' => 'Report Origin',
            'subject' => 'Report correspondence', 'received_at' => now()->subDay(),
            'receiving_department_id' => $office->id, 'lifecycle_state' => 'routed',
            'classification' => 'internal', ...$overrides,
        ]);
    }
}
