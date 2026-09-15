<?php

namespace Tests\Feature;

use App\Domain\Correspondence\CorrespondenceClassification;
use App\Domain\Correspondence\CorrespondenceLifecycleState;
use App\Models\CorrespondenceEvent;
use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkflowTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CorrespondenceDetailWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_human_can_open_detail_workspace_with_key_record_props(): void
    {
        $office = $this->department('DETAIL');
        $head = $this->human('department_head', $office);
        $record = $this->record(
            'Department endorsement request',
            CorrespondenceLifecycleState::Classified,
            $office,
            CorrespondenceClassification::Internal,
        );

        $this->actingAs($head)->get('/correspondence/'.$record->public_id.'/workspace')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Correspondence/Show')
                ->where('correspondence.publicId', $record->public_id)
                ->where('correspondence.municipalReference', $record->municipal_reference_no)
                ->where('correspondence.lifecycleState', 'classified')
                ->where('correspondence.classification', 'internal')
                ->where('correspondence.source.senderName', 'Synthetic Sender')
                ->where('correspondence.content.subject', 'Department endorsement request')
                ->where('correspondence.accountability.currentOffice.code', $office->code));
    }

    public function test_valid_registrar_can_open_fresh_received_intake_before_office_assignment(): void
    {
        $office = $this->department('INTAKE');
        $registrar = $this->human('department_staff', $office);
        $record = $this->record('Fresh intake', CorrespondenceLifecycleState::Received);

        $this->actingAs($registrar)->get('/correspondence/'.$record->public_id.'/workspace')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Correspondence/Show')
                ->where('correspondence.accountability.currentOffice', null)
                ->where('capabilities.canRegister', true)
                ->where('capabilities.canClassify', false)
                ->where('capabilities.canRoute', false)
                ->where('capabilities.canAct', false));

        $this->actingAs($registrar)->getJson('/correspondence/'.$record->public_id)
            ->assertForbidden();
    }

    public function test_wrong_office_cannot_open_workspace_or_receive_subject_content(): void
    {
        $own = $this->department('OWN');
        $other = $this->department('OTHER');
        $viewer = $this->human('department_head', $own);
        $record = $this->record(
            'Other office confidential operational matter',
            CorrespondenceLifecycleState::Classified,
            $other,
            CorrespondenceClassification::Internal,
        );

        $response = $this->actingAs($viewer)
            ->get('/correspondence/'.$record->public_id.'/workspace')
            ->assertForbidden();

        $this->assertStringNotContainsString($record->subject, $response->getContent());
    }

    public function test_department_staff_cannot_open_confidential_workspace(): void
    {
        $office = $this->department('CONF');
        $staff = $this->human('department_staff', $office);
        $record = $this->record(
            'Confidential correspondence',
            CorrespondenceLifecycleState::Classified,
            $office,
            CorrespondenceClassification::Confidential,
        );

        $this->actingAs($staff)
            ->get('/correspondence/'.$record->public_id.'/workspace')
            ->assertForbidden();
    }

    public function test_user_without_restricted_authority_cannot_open_restricted_workspace(): void
    {
        $office = $this->department('REST');
        $hr = $this->human('hr_officer', $office);
        $record = $this->record(
            'Restricted correspondence',
            CorrespondenceLifecycleState::Classified,
            $office,
            CorrespondenceClassification::Restricted,
        );

        $this->actingAs($hr)
            ->get('/correspondence/'.$record->public_id.'/workspace')
            ->assertForbidden();
    }

    public function test_system_admin_does_not_gain_automatic_restricted_workspace_access(): void
    {
        $office = $this->department('ADMIN');
        $admin = $this->human('system_admin', $office);
        $record = $this->record(
            'Restricted infrastructure-adjacent correspondence',
            CorrespondenceLifecycleState::Classified,
            $office,
            CorrespondenceClassification::Restricted,
        );

        $this->actingAs($admin)
            ->get('/correspondence/'.$record->public_id.'/workspace')
            ->assertForbidden();
    }

    public function test_timeline_uses_persisted_event_chronology_and_maps_actor_office_and_remarks(): void
    {
        $office = $this->department('TIME');
        $head = $this->human('department_head', $office);
        $record = $this->record(
            'Timeline record',
            CorrespondenceLifecycleState::Classified,
            $office,
            CorrespondenceClassification::Internal,
        );

        $this->event(
            $record,
            'classified',
            CorrespondenceLifecycleState::Registered,
            CorrespondenceLifecycleState::Classified,
            $head,
            $office,
            now()->subHour(),
            'Classified after review.',
        );
        $this->event(
            $record,
            'received',
            null,
            CorrespondenceLifecycleState::Received,
            null,
            null,
            now()->subHours(3),
            'Original intake.',
        );
        $this->event(
            $record,
            'registered',
            CorrespondenceLifecycleState::Received,
            CorrespondenceLifecycleState::Registered,
            $head,
            $office,
            now()->subHours(2),
            'Registered by records desk.',
        );

        $this->actingAs($head)->get('/correspondence/'.$record->public_id.'/workspace')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('timeline', 3)
                ->where('timeline.0.event', 'received')
                ->where('timeline.1.event', 'registered')
                ->where('timeline.2.event', 'classified')
                ->where('timeline.1.actor.name', $head->name)
                ->where('timeline.1.office.code', $office->code)
                ->where('timeline.1.remarks', 'Registered by records desk.'));
    }

    public function test_routed_workspace_projects_current_workflow_office_assignee_and_authorized_transaction_link(): void
    {
        $origin = $this->department('WF-ORIGIN');
        $target = $this->department('WF-TARGET');
        $creator = $this->human('department_head', $origin);
        $recipient = $this->human('department_head', $target);
        $workflow = $this->workflow($creator, $target, $recipient->employee, 'for_review');
        $record = $this->record(
            'Routed workflow record',
            CorrespondenceLifecycleState::Routed,
            $origin,
            CorrespondenceClassification::Internal,
            $workflow,
        );

        $this->actingAs($recipient)->get('/correspondence/'.$record->public_id.'/workspace')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('correspondence.accountability.currentOffice.code', $target->code)
                ->where('correspondence.accountability.workflow.reference', $workflow->reference_no)
                ->where('correspondence.accountability.workflow.status', 'for_review')
                ->where('correspondence.accountability.workflow.assignedEmployee.name', $recipient->employee->full_name)
                ->where('correspondence.accountability.workflow.assignedEmployee.position', 'Correspondence Officer')
                ->where('correspondence.accountability.workflow.detailUrl', '/transactions/'.$workflow->id));
    }

    public function test_capability_props_match_backend_state_and_actionability_boundaries(): void
    {
        $office = $this->department('CAP');
        $head = $this->human('department_head', $office);

        $received = $this->record('Capability receive', CorrespondenceLifecycleState::Received);
        $registered = $this->record('Capability classify', CorrespondenceLifecycleState::Registered, $office);
        $classified = $this->record(
            'Capability route',
            CorrespondenceLifecycleState::Classified,
            $office,
            CorrespondenceClassification::Internal,
        );
        $actionableWorkflow = $this->workflow($head, $office, $head->employee, 'submitted');
        $actionable = $this->record(
            'Capability act',
            CorrespondenceLifecycleState::Routed,
            $office,
            CorrespondenceClassification::Internal,
            $actionableWorkflow,
        );
        $waitingWorkflow = $this->workflow($head, $office, null, 'submitted');
        $waiting = $this->record(
            'Capability wait',
            CorrespondenceLifecycleState::Routed,
            $office,
            CorrespondenceClassification::Internal,
            $waitingWorkflow,
        );

        $this->assertCapability($head, $received, 'canRegister');
        $this->assertCapability($head, $registered, 'canClassify');
        $this->assertCapability($head, $classified, 'canRoute');
        $this->assertCapability($head, $actionable, 'canAct');

        $this->actingAs($head)->get('/correspondence/'.$waiting->public_id.'/workspace')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('capabilities.canAct', false));
    }

    public function test_existing_human_json_detail_contract_remains_json_and_authorized_by_existing_rules(): void
    {
        $office = $this->department('JSON');
        $head = $this->human('department_head', $office);
        $staff = $this->human('department_staff', $office);
        $record = $this->record(
            'JSON contract correspondence',
            CorrespondenceLifecycleState::Classified,
            $office,
            CorrespondenceClassification::Confidential,
        );
        $this->event(
            $record,
            'classified',
            CorrespondenceLifecycleState::Registered,
            CorrespondenceLifecycleState::Classified,
            $head,
            $office,
            now()->subHour(),
        );

        $this->actingAs($head)
            ->getJson('/correspondence/'.$record->public_id)
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonPath('correspondence.public_id', $record->public_id)
            ->assertJsonPath('correspondence.classification', 'confidential')
            ->assertJsonPath('correspondence.history.0.event', 'classified');

        $this->actingAs($staff)
            ->getJson('/correspondence/'.$record->public_id)
            ->assertForbidden();
    }

    private function assertCapability(User $actor, CorrespondenceRecord $record, string $capability): void
    {
        $this->actingAs($actor)->get('/correspondence/'.$record->public_id.'/workspace')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('capabilities.'.$capability, true));
    }

    private function department(string $suffix): Department
    {
        return Department::query()->create([
            'code' => 'DETAIL-'.Str::upper(Str::random(5)).'-'.$suffix,
            'name' => 'Detail '.$suffix,
            'short_name' => 'DT-'.$suffix,
            'branch' => 'executive',
            'office_type' => 'department',
            'is_routable' => true,
            'is_active' => true,
        ]);
    }

    private function human(string $role, Department $department): User
    {
        $user = User::query()->create([
            'name' => 'Detail '.$role.' '.Str::random(5),
            'email' => Str::lower(Str::random(10)).'@example.test',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);

        Employee::query()->create([
            'employee_number' => 'DETAIL-EMP-'.Str::upper(Str::random(10)),
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
        $registered = $state !== CorrespondenceLifecycleState::Received;
        $classified = in_array($state, [
            CorrespondenceLifecycleState::Classified,
            CorrespondenceLifecycleState::Routed,
            CorrespondenceLifecycleState::InAction,
        ], true);
        $routed = in_array($state, [CorrespondenceLifecycleState::Routed, CorrespondenceLifecycleState::InAction], true);

        return CorrespondenceRecord::query()->create([
            'public_id' => (string) Str::uuid(),
            'external_reference_no' => 'EXT-'.Str::upper(Str::random(18)),
            'source' => 'email',
            'channel' => 'official_email',
            'sender_name' => 'Synthetic Sender',
            'sender_organization' => 'Synthetic Office',
            'sender_contact' => ['email' => 'not-exposed@example.test'],
            'subject' => $subject,
            'summary' => 'Synthetic correspondence detail workspace test record.',
            'received_at' => now()->subHours(5),
            'receiving_department_id' => $department?->id,
            'registered_at' => $registered ? now()->subHours(4) : null,
            'municipal_reference_no' => $registered ? 'TAL-COR-TEST-'.Str::upper(Str::random(12)) : null,
            'classification' => $classification?->value,
            'classified_at' => $classified ? now()->subHours(3) : null,
            'routed_at' => $routed ? now()->subHours(2) : null,
            'action_started_at' => $state === CorrespondenceLifecycleState::InAction ? now()->subHour() : null,
            'lifecycle_state' => $state->value,
            'workflow_transaction_id' => $workflow?->id,
        ]);
    }

    private function workflow(
        User $creator,
        Department $currentOffice,
        ?Employee $assignee,
        string $status,
    ): WorkflowTransaction {
        return WorkflowTransaction::query()->create([
            'reference_no' => 'TX-'.Str::upper(Str::random(12)),
            'transaction_type' => 'document_review',
            'title' => 'Correspondence detail linked workflow',
            'priority' => 'normal',
            'origin_department_id' => $creator->employee->department_id,
            'current_department_id' => $currentOffice->id,
            'created_by_user_id' => $creator->id,
            'assigned_employee_id' => $assignee?->id,
            'status' => $status,
            'received_at' => now()->subHours(2),
            'due_at' => now()->addDay(),
        ]);
    }

    private function event(
        CorrespondenceRecord $record,
        string $event,
        ?CorrespondenceLifecycleState $previous,
        CorrespondenceLifecycleState $next,
        ?User $actor,
        ?Department $office,
        $occurredAt,
        ?string $remarks = null,
    ): CorrespondenceEvent {
        return CorrespondenceEvent::query()->create([
            'correspondence_record_id' => $record->id,
            'event' => $event,
            'previous_lifecycle_state' => $previous?->value,
            'new_lifecycle_state' => $next->value,
            'actor_user_id' => $actor?->id,
            'office_department_id' => $office?->id,
            'remarks' => $remarks,
            'correlation_id' => (string) Str::uuid(),
            'occurred_at' => $occurredAt,
        ]);
    }
}
