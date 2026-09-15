<?php

namespace Tests\Feature;

use App\Domain\Correspondence\CorrespondenceClassification;
use App\Domain\Correspondence\CorrespondenceLifecycleState;
use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkflowTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CorrespondenceWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_human_sees_unregistered_intake_and_eligible_office_correspondence(): void
    {
        $department = $this->department('VISIBLE');
        $staff = $this->human('department_staff', $department);
        $this->record('Fresh external intake', CorrespondenceLifecycleState::Received);
        $this->record('Registered office item', CorrespondenceLifecycleState::Registered, $department);

        $this->actingAs($staff)->get('/correspondence')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Correspondence/Index')
                ->where('records.total', 2)
                ->has('records.data', 2)
                ->where('filterOptions.classifications', ['public', 'internal']));
    }

    public function test_other_office_cannot_enumerate_restricted_correspondence(): void
    {
        $own = $this->department('OWN');
        $other = $this->department('OTHER');
        $viewer = $this->human('department_head', $own);
        $hidden = $this->record(
            'Restricted matter must stay hidden',
            CorrespondenceLifecycleState::Classified,
            $other,
            CorrespondenceClassification::Restricted,
        );

        $response = $this->actingAs($viewer)->get('/correspondence')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 0)
                ->has('records.data', 0));

        $this->assertStringNotContainsString($hidden->subject, $response->getContent());
        $this->assertStringNotContainsString($hidden->public_id, $response->getContent());
    }

    public function test_classification_filter_cannot_leak_hidden_records_through_totals(): void
    {
        $own = $this->department('CLASS-OWN');
        $other = $this->department('CLASS-OTHER');
        $viewer = $this->human('department_head', $own);
        $this->record('Visible internal matter', CorrespondenceLifecycleState::Classified, $own, CorrespondenceClassification::Internal);
        $hidden = $this->record('Hidden restricted matter', CorrespondenceLifecycleState::Classified, $other, CorrespondenceClassification::Restricted);

        $restricted = $this->actingAs($viewer)->get('/correspondence?classification=restricted')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 0)
                ->has('records.data', 0));
        $this->assertStringNotContainsString($hidden->subject, $restricted->getContent());

        $this->actingAs($viewer)->get('/correspondence?classification=internal')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 1)
                ->where('records.data.0.subject', 'Visible internal matter'));
    }

    public function test_search_and_lifecycle_filters_are_applied_server_side(): void
    {
        $department = $this->department('SEARCH');
        $head = $this->human('department_head', $department);
        $this->record('Budget endorsement alpha', CorrespondenceLifecycleState::Registered, $department);
        $this->record('Engineering endorsement beta', CorrespondenceLifecycleState::Registered, $department);
        $this->record('Budget classified item', CorrespondenceLifecycleState::Classified, $department, CorrespondenceClassification::Internal);

        $this->actingAs($head)->get('/correspondence?search=budget&lifecycle=registered')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 1)
                ->where('records.data.0.subject', 'Budget endorsement alpha')
                ->where('filters.search', 'budget')
                ->where('filters.lifecycle', 'registered'));
    }

    public function test_assigned_to_me_and_overdue_filters_use_linked_workflow_data(): void
    {
        $department = $this->department('ASSIGN');
        $head = $this->human('department_head', $department);
        $other = $this->human('department_staff', $department);

        $mine = $this->workflow($head, $department, $head->employee, now()->subDay());
        $theirs = $this->workflow($head, $department, $other->employee, now()->addDays(2));
        $this->record('My overdue routed item', CorrespondenceLifecycleState::Routed, $department, CorrespondenceClassification::Internal, $mine);
        $this->record('Other assigned routed item', CorrespondenceLifecycleState::Routed, $department, CorrespondenceClassification::Internal, $theirs);

        $this->actingAs($head)->get('/correspondence?assigned_to_me=1')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 1)
                ->where('records.data.0.subject', 'My overdue routed item'));

        $this->actingAs($head)->get('/correspondence?aging=overdue')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 1)
                ->where('records.data.0.overdue', true));
    }

    public function test_action_required_filter_uses_existing_correspondence_capabilities(): void
    {
        $department = $this->department('ACTION');
        $head = $this->human('department_head', $department);
        $this->record('Needs classification', CorrespondenceLifecycleState::Registered, $department);
        $workflow = $this->workflow($head, $department, $head->employee, now()->addDay());
        $this->record('Already in action', CorrespondenceLifecycleState::InAction, $department, CorrespondenceClassification::Internal, $workflow);

        $this->actingAs($head)->get('/correspondence?action_required=1')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 1)
                ->where('records.data.0.subject', 'Needs classification')
                ->where('records.data.0.actionRequired', true));
    }

    public function test_pagination_combines_with_authorized_search_and_lifecycle_filters(): void
    {
        $department = $this->department('PAGE');
        $head = $this->human('department_head', $department);

        foreach (range(1, 21) as $number) {
            $this->record("Queue item {$number}", CorrespondenceLifecycleState::Registered, $department);
        }
        $this->record('Different lifecycle', CorrespondenceLifecycleState::Classified, $department, CorrespondenceClassification::Internal);

        $this->actingAs($head)->get('/correspondence?search=queue&lifecycle=registered&page=2')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('records.total', 21)
                ->where('records.current_page', 2)
                ->where('records.last_page', 2)
                ->has('records.data', 1));
    }

    private function department(string $suffix): Department
    {
        return Department::query()->create([
            'code' => 'INBOX-'.Str::upper(Str::random(5)).'-'.$suffix,
            'name' => 'Inbox '.$suffix,
            'short_name' => 'IB-'.$suffix,
            'branch' => 'executive',
            'office_type' => 'department',
            'is_routable' => true,
            'is_active' => true,
        ]);
    }

    private function human(string $role, Department $department): User
    {
        $user = User::query()->create([
            'name' => 'Inbox '.$role.' '.Str::random(5),
            'email' => Str::lower(Str::random(10)).'@example.test',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);

        Employee::query()->create([
            'employee_number' => 'INBOX-EMP-'.Str::upper(Str::random(10)),
            'full_name' => $user->name,
            'work_email' => $user->email,
            'user_id' => $user->id,
            'department_id' => $department->id,
            'position_title' => 'Correspondence Officer',
            'employment_status' => 'active',
        ]);

        return $user->fresh('employee');
    }

    private function record(
        string $subject,
        CorrespondenceLifecycleState $state,
        ?Department $department = null,
        ?CorrespondenceClassification $classification = null,
        ?WorkflowTransaction $workflow = null,
    ): CorrespondenceRecord {
        return CorrespondenceRecord::query()->create([
            'public_id' => (string) Str::uuid(),
            'external_reference_no' => 'EXT-'.Str::upper(Str::random(18)),
            'source' => 'email',
            'channel' => 'portal_test',
            'sender_name' => 'Synthetic Sender',
            'sender_organization' => 'Synthetic Office',
            'subject' => $subject,
            'summary' => 'Synthetic correspondence workspace test record.',
            'received_at' => now()->subHours(3),
            'receiving_department_id' => $department?->id,
            'municipal_reference_no' => $state === CorrespondenceLifecycleState::Received
                ? null
                : 'TAL-COR-TEST-'.Str::upper(Str::random(12)),
            'classification' => $classification?->value,
            'lifecycle_state' => $state->value,
            'workflow_transaction_id' => $workflow?->id,
        ]);
    }

    private function workflow(
        User $creator,
        Department $department,
        Employee $assignee,
        $dueAt,
    ): WorkflowTransaction {
        return WorkflowTransaction::query()->create([
            'reference_no' => 'TX-'.Str::upper(Str::random(12)),
            'transaction_type' => 'document_review',
            'title' => 'Correspondence workspace linked workflow',
            'priority' => 'normal',
            'origin_department_id' => $department->id,
            'current_department_id' => $department->id,
            'created_by_user_id' => $creator->id,
            'assigned_employee_id' => $assignee->id,
            'status' => 'for_review',
            'received_at' => now()->subDay(),
            'due_at' => $dueAt,
        ]);
    }
}
