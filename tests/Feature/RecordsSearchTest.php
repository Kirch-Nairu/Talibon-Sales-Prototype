<?php

namespace Tests\Feature;

use App\Domain\Correspondence\CorrespondenceClassification;
use App\Domain\Correspondence\CorrespondenceLifecycleState;
use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LegislativeRecord;
use App\Models\User;
use App\Models\WorkflowTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RecordsSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_route_requires_auth_active_and_mfa_assurance(): void
    {
        $office = $this->department('ACCESS');

        $this->get('/records')->assertRedirect('/login');

        $inactive = $this->human('department_head', $office, false);
        Auth::guard('web')->login($inactive);
        $this->get('/records')->assertRedirect('/login');

        $mfaPending = $this->human('department_head', $office);
        Auth::guard('web')->login($mfaPending);
        $this->get('/records')->assertRedirect(route('mfa.enroll'));
    }

    public function test_authorized_sources_appear_and_unrelated_sources_do_not_leak(): void
    {
        $own = $this->department('VISIBLE');
        $other = $this->department('OTHER');
        $third = $this->department('THIRD');
        $actor = $this->human('department_head', $own);
        $otherHead = $this->human('department_head', $other);

        $visibleCorrespondence = $this->correspondence('Visible correspondence', $own);
        $hiddenCorrespondence = $this->correspondence(
            'Hidden restricted correspondence',
            $other,
            CorrespondenceClassification::Restricted,
        );
        $visibleTransaction = $this->transaction('Visible transaction', $other, $own, $actor);
        $hiddenTransaction = $this->transaction('Hidden transaction', $other, $third, $otherHead);

        $response = $this->actingAs($actor)->get('/records')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Records/Index')
                ->where('filters.record_type', 'all')
                ->where('records.total', 2)
                ->has('records.data', 2)
                ->has('filterOptions.offices', 1)
                ->where('filterOptions.offices.0.id', $own->id));

        $content = $response->getContent();
        $this->assertStringContainsString($visibleCorrespondence->subject, $content);
        $this->assertStringContainsString($visibleTransaction->title, $content);
        $this->assertStringNotContainsString($hiddenCorrespondence->subject, $content);
        $this->assertStringNotContainsString($hiddenTransaction->title, $content);
    }

    public function test_correspondence_classification_rules_and_receive_intake_visibility_are_preserved(): void
    {
        $office = $this->department('CLASS');
        $staff = $this->human('department_staff', $office);
        $head = $this->human('department_head', $office);

        $this->correspondence('Internal visible', $office, CorrespondenceClassification::Internal);
        $confidential = $this->correspondence('Confidential hidden from staff', $office, CorrespondenceClassification::Confidential);
        $restricted = $this->correspondence('Restricted hidden from staff', $office, CorrespondenceClassification::Restricted);
        $intake = $this->correspondence(
            'Fresh RECEIVE intake',
            null,
            null,
            CorrespondenceLifecycleState::Received,
        );

        $staffResponse = $this->actingAs($staff)->get('/records?record_type=correspondence')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('records.total', 2));

        $this->assertStringContainsString($intake->subject, $staffResponse->getContent());
        $this->assertStringNotContainsString($confidential->subject, $staffResponse->getContent());
        $this->assertStringNotContainsString($restricted->subject, $staffResponse->getContent());

        $this->actingAs($head)->get('/records?record_type=correspondence&search=Restricted')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 1)
                ->where('records.data.0.title', 'Restricted hidden from staff'));
    }

    public function test_system_admin_does_not_gain_restricted_correspondence_visibility(): void
    {
        $adminOffice = $this->department('ADMIN');
        $other = $this->department('ADMIN-OTHER');
        $admin = $this->human('system_admin', $adminOffice);
        $record = $this->correspondence(
            'Restricted admin-hidden record',
            $other,
            CorrespondenceClassification::Restricted,
        );

        $response = $this->actingAs($admin)
            ->get('/records?record_type=correspondence&search=admin-hidden')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('records.total', 0));

        $this->assertStringNotContainsString($record->subject, $response->getContent());
    }

    public function test_reference_title_subject_sender_and_source_search_contracts(): void
    {
        $office = $this->department('SEARCH');
        $actor = $this->human('department_head', $office);

        $correspondence = $this->correspondence(
            'Bridge inspection correspondence',
            $office,
            CorrespondenceClassification::Internal,
            CorrespondenceLifecycleState::Classified,
            municipalReference: 'TAL-COR-2026-000777',
            externalReference: 'EXT-BRIDGE-441',
            senderName: 'Maria Santos',
            senderOrganization: 'Provincial Engineering Office',
            source: 'email',
            summary: 'Unique bridge summary token',
        );
        $transaction = $this->transaction(
            'Road rehabilitation funding request',
            $office,
            $office,
            $actor,
            transactionType: 'funding_request',
            reference: 'TX-FUND-0099',
        );

        foreach ([
            ['TAL-COR-2026-000777', 'correspondence', $correspondence->subject],
            ['EXT-BRIDGE-441', 'correspondence', $correspondence->subject],
            ['Bridge inspection', 'correspondence', $correspondence->subject],
            ['Maria Santos', 'correspondence', $correspondence->subject],
            ['Provincial Engineering', 'correspondence', $correspondence->subject],
            ['email', 'correspondence', $correspondence->subject],
            ['Unique bridge summary token', 'correspondence', $correspondence->subject],
            ['TX-FUND-0099', 'transaction', $transaction->title],
            ['Road rehabilitation', 'transaction', $transaction->title],
            ['funding_request', 'transaction', $transaction->title],
        ] as [$search, $type, $title]) {
            $this->actingAs($actor)->get('/records?record_type='.$type.'&search='.urlencode($search))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('records.total', 1)
                    ->where('records.data.0.title', $title));
        }
    }

    public function test_office_and_assignee_search_use_existing_relationships(): void
    {
        $origin = $this->department('ORIGIN');
        $current = $this->department('CURRENT');
        $actor = $this->human('department_head', $origin);
        $recipient = $this->human('department_head', $current);

        $workflow = $this->transaction(
            'Linked document review',
            $origin,
            $current,
            $actor,
            $recipient->employee,
            transactionType: 'document_review',
        );
        $correspondence = $this->correspondence(
            'Linked correspondence',
            $origin,
            CorrespondenceClassification::Internal,
            CorrespondenceLifecycleState::Routed,
            workflow: $workflow,
        );

        $this->actingAs($actor)->get('/records?record_type=transaction&search='.urlencode($origin->code))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('records.total', 1));

        $this->actingAs($recipient)->get('/records?search='.urlencode($current->code))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 2)
                ->where('records.data', fn ($rows): bool => collect($rows)->contains(
                    fn ($row): bool => $row['title'] === $correspondence->subject,
                )));

        $this->actingAs($recipient)->get('/records?search='.urlencode($recipient->employee->full_name))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('records.total', 2));
    }

    public function test_record_type_and_state_filters_remain_source_specific(): void
    {
        $office = $this->department('TYPE');
        $actor = $this->human('department_head', $office);

        $correspondence = $this->correspondence(
            'Classified source record',
            $office,
            CorrespondenceClassification::Internal,
            CorrespondenceLifecycleState::Classified,
        );
        $transaction = $this->transaction(
            'Review transaction',
            $office,
            $office,
            $actor,
            status: 'for_review',
        );

        $this->actingAs($actor)->get('/records?record_type=correspondence')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 1)
                ->where('records.data.0.recordType', 'correspondence'));

        $this->actingAs($actor)->get('/records?record_type=transaction')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 1)
                ->where('records.data.0.recordType', 'transaction'));

        $this->actingAs($actor)->get('/records?record_type=all')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 2)
                ->where('filterOptions.states', function ($states): bool {
                    $options = collect($states)->keyBy('value');

                    return $options->get('classified')['label'] === 'Classified'
                        && $options->get('for_review')['label'] === 'For Review';
                }));

        $this->actingAs($actor)->get('/records?record_type=correspondence&state=classified')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 1)
                ->where('records.data.0.title', $correspondence->subject));

        $this->actingAs($actor)->get('/records?record_type=transaction&state=for_review')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 1)
                ->where('records.data.0.title', $transaction->title));

        $this->actingAs($actor)->get('/records?record_type=all&state=for_review')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('records.total', 1));
    }

    public function test_current_office_filter_only_narrows_authorized_results(): void
    {
        $own = $this->department('FILTER-OWN');
        $other = $this->department('FILTER-OTHER');
        $third = $this->department('FILTER-THIRD');
        $actor = $this->human('department_head', $own);
        $otherHead = $this->human('department_head', $other);

        $visible = $this->transaction('Visible waiting elsewhere', $own, $other, $actor);
        $hidden = $this->transaction('Hidden in same target', $third, $other, $otherHead);
        $this->correspondence('Own correspondence', $own);

        $response = $this->actingAs($actor)
            ->get('/records?office_id='.$other->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 1)
                ->where('records.data.0.title', $visible->title));

        $this->assertStringNotContainsString($hidden->title, $response->getContent());
    }

    public function test_inclusive_dates_and_legacy_transaction_created_at_fallback(): void
    {
        $office = $this->department('DATE');
        $actor = $this->human('department_head', $office);
        $from = now()->subDays(3)->toDateString();
        $to = now()->subDay()->toDateString();

        $this->correspondence('From boundary', $office, receivedAt: now()->subDays(3)->startOfDay());
        $this->correspondence('To boundary', $office, receivedAt: now()->subDay()->endOfDay());
        $this->correspondence('Outside boundary', $office, receivedAt: now()->subDays(4));

        $this->actingAs($actor)->get("/records?record_type=correspondence&date_from={$from}&date_to={$to}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('records.total', 2));

        $legacy = $this->transaction('Legacy received fallback', $office, $office, $actor);
        $legacy->forceFill([
            'received_at' => null,
            'created_at' => now()->subDays(2),
        ])->saveQuietly();

        $this->actingAs($actor)->get("/records?record_type=transaction&search=Legacy&date_from={$from}&date_to={$to}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 1)
                ->where('records.data.0.title', $legacy->title));
    }

    public function test_result_shape_uses_existing_detail_workspaces(): void
    {
        $office = $this->department('LINK');
        $actor = $this->human('department_head', $office);
        $correspondence = $this->correspondence('Linked record detail', $office);
        $transaction = $this->transaction('Linked transaction detail', $office, $office, $actor);

        $this->actingAs($actor)->get('/records?search=Linked')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 2)
                ->where('records.data', function ($rows) use ($correspondence, $transaction): bool {
                    $items = collect($rows);

                    return $items->contains(fn ($row) => $row['recordType'] === 'correspondence'
                            && $row['detailUrl'] === '/correspondence/'.$correspondence->public_id.'/workspace')
                        && $items->contains(fn ($row) => $row['recordType'] === 'transaction'
                            && $row['detailUrl'] === '/transactions/'.$transaction->id);
                }));
    }

    public function test_pagination_is_bounded_and_preserves_query_filters(): void
    {
        $office = $this->department('PAGE');
        $actor = $this->human('department_head', $office);

        foreach (range(1, 26) as $number) {
            $this->transaction(
                "Bounded registry {$number}",
                $office,
                $office,
                $actor,
                reference: 'PAGE-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
            );
        }

        $pageOne = $this->actingAs($actor)
            ->get('/records?record_type=transaction&search=Bounded')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 26)
                ->where('records.per_page', 25)
                ->where('records.current_page', 1)
                ->has('records.data', 25));

        $this->assertStringContainsString('record_type=transaction', $pageOne->getContent());
        $this->assertStringContainsString('search=Bounded', $pageOne->getContent());

        $this->actingAs($actor)
            ->get('/records?record_type=transaction&search=Bounded&page=2')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.current_page', 2)
                ->has('records.data', 1));
    }

    public function test_parked_record_domains_do_not_participate(): void
    {
        $office = $this->department('PARKED');
        $actor = $this->human('department_head', $office);

        $legislative = LegislativeRecord::query()->create([
            'record_type' => 'ordinance',
            'record_number' => 'ORD-PARKED-001',
            'title' => 'Parked ordinance registry record',
            'year' => (int) now()->year,
            'status' => 'active',
            'created_by_user_id' => $actor->id,
        ]);

        $response = $this->actingAs($actor)
            ->get('/records?search=Parked ordinance')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('records.total', 0));

        $this->assertStringNotContainsString($legislative->title, $response->getContent());

        $source = file_get_contents(app_path('Services/RecordsSearchQuery.php'));
        $this->assertIsString($source);
        foreach (['LegislativeRecord', 'Memorandum', 'Asset', 'PayrollEntry', 'EmployeeHealthRecord'] as $parkedModel) {
            $this->assertStringNotContainsString($parkedModel, $source);
        }
    }

    private function department(string $suffix): Department
    {
        return Department::query()->create([
            'code' => 'REC-'.Str::upper(Str::random(5)).'-'.$suffix,
            'name' => 'Records '.$suffix,
            'short_name' => 'R-'.$suffix,
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
            'name' => 'Records '.$role.' '.Str::random(5),
            'email' => Str::lower(Str::random(10)).'@example.test',
            'password' => 'password',
            'role' => $role,
            'is_active' => $active,
        ]);

        Employee::query()->create([
            'employee_number' => 'REC-EMP-'.Str::upper(Str::random(10)),
            'full_name' => $user->name,
            'work_email' => $user->email,
            'user_id' => $user->id,
            'department_id' => $department->id,
            'position_title' => 'Records Officer',
            'employment_status' => 'active',
        ]);

        return $user->fresh('employee.department');
    }

    private function correspondence(
        string $subject,
        ?Department $department,
        ?CorrespondenceClassification $classification = CorrespondenceClassification::Internal,
        CorrespondenceLifecycleState $state = CorrespondenceLifecycleState::Classified,
        ?WorkflowTransaction $workflow = null,
        ?string $municipalReference = null,
        ?string $externalReference = null,
        string $senderName = 'Synthetic Sender',
        string $senderOrganization = 'Synthetic Office',
        string $source = 'portal',
        string $summary = 'Synthetic records-search test correspondence.',
        $receivedAt = null,
    ): CorrespondenceRecord {
        return CorrespondenceRecord::query()->create([
            'public_id' => (string) Str::uuid(),
            'external_reference_no' => $externalReference ?? 'EXT-'.Str::upper(Str::random(12)),
            'source' => $source,
            'channel' => 'records_test',
            'sender_name' => $senderName,
            'sender_organization' => $senderOrganization,
            'subject' => $subject,
            'summary' => $summary,
            'received_at' => $receivedAt ?? now()->subHours(3),
            'receiving_department_id' => $department?->id,
            'municipal_reference_no' => $state === CorrespondenceLifecycleState::Received
                ? null
                : ($municipalReference ?? 'TAL-COR-TEST-'.Str::upper(Str::random(8))),
            'classification' => $classification?->value,
            'lifecycle_state' => $state->value,
            'workflow_transaction_id' => $workflow?->id,
        ]);
    }

    private function transaction(
        string $title,
        Department $origin,
        Department $current,
        User $creator,
        ?Employee $assignee = null,
        $receivedAt = null,
        string $transactionType = 'internal_request',
        string $status = 'submitted',
        ?string $reference = null,
    ): WorkflowTransaction {
        return WorkflowTransaction::query()->create([
            'reference_no' => $reference ?? 'REC-TX-'.Str::upper(Str::random(10)),
            'transaction_type' => $transactionType,
            'title' => $title,
            'description' => 'Synthetic records-search transaction description.',
            'priority' => 'normal',
            'origin_department_id' => $origin->id,
            'current_department_id' => $current->id,
            'created_by_user_id' => $creator->id,
            'assigned_employee_id' => $assignee?->id,
            'status' => $status,
            'received_at' => $receivedAt ?? now()->subHours(2),
            'due_at' => now()->addDays(3),
        ]);
    }
}
