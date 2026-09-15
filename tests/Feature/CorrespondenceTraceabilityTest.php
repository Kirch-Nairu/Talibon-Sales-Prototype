<?php

namespace Tests\Feature;

use App\Domain\Correspondence\CorrespondenceLifecycleState;
use App\Models\CorrespondenceEvent;
use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CorrespondenceTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_incoming_document_trace_combines_lifecycle_and_later_workflow_movements_with_evidence(): void
    {
        $receivingOffice = $this->department('RECEIVING');
        $reviewOffice = $this->department('REVIEW');
        $finalOffice = $this->department('FINAL');
        $receivingHead = $this->human('department_head', $receivingOffice);
        $reviewHead = $this->human('department_head', $reviewOffice);
        $finalHead = $this->human('department_head', $finalOffice);
        $record = $this->record('Incoming document trace acceptance');

        CorrespondenceEvent::query()->create([
            'correspondence_record_id' => $record->id,
            'event' => 'received',
            'previous_lifecycle_state' => null,
            'new_lifecycle_state' => CorrespondenceLifecycleState::Received->value,
            'remarks' => 'Received through official intake.',
            'metadata' => [],
            'correlation_id' => (string) Str::uuid(),
            'occurred_at' => now()->subHour(),
        ]);

        $this->actingAs($receivingHead)
            ->post('/correspondence/'.$record->public_id.'/workspace/register')
            ->assertRedirect();

        $this->actingAs($receivingHead)
            ->post('/correspondence/'.$record->public_id.'/workspace/classify', [
                'classification' => 'internal',
                'remarks' => 'Validated for internal routing.',
            ])
            ->assertRedirect();

        $this->actingAs($receivingHead)
            ->post('/correspondence/'.$record->public_id.'/workspace/route', [
                'target_department_id' => $reviewOffice->id,
                'priority' => 'normal',
                'remarks' => 'Forwarded for technical review.',
                'evidence' => [UploadedFile::fake()->createWithContent(
                    'initial-routing.pdf',
                    "%PDF-1.7\ninitial route evidence\n%%EOF",
                )],
            ])
            ->assertRedirect();

        $workflow = $record->fresh('workflowTransaction')->workflowTransaction;
        $this->assertNotNull($workflow);

        $this->actingAs($reviewHead)
            ->post('/transactions/'.$workflow->id.'/transition', [
                'action' => 'forward',
                'target_department_id' => $finalOffice->id,
                'remarks' => 'Review complete; forwarded to final office.',
                'evidence' => [UploadedFile::fake()->createWithContent(
                    'review-routing.pdf',
                    "%PDF-1.7\nreview route evidence\n%%EOF",
                )],
            ])
            ->assertRedirect();

        $this->actingAs($finalHead)
            ->post('/transactions/'.$workflow->id.'/transition', [
                'action' => 'assign',
                'assigned_employee_id' => $finalHead->employee->id,
                'remarks' => 'Assigned for final action.',
            ])
            ->assertRedirect();

        $this->actingAs($finalHead)
            ->get('/correspondence/'.$record->public_id.'/workspace')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('correspondence.dates.receivedAt', $record->received_at?->toISOString())
                ->where('correspondence.accountability.receivingOffice.code', $receivingOffice->code)
                ->where('correspondence.accountability.currentOffice.code', $finalOffice->code)
                ->where('correspondence.accountability.workflow.assignedEmployee.name', $finalHead->employee->full_name)
                ->has('timeline', 6)
                ->where('timeline.0.event', 'received')
                ->where('timeline.1.event', 'registered')
                ->where('timeline.2.event', 'classified')
                ->where('timeline.3.event', 'routed')
                ->where('timeline.3.source', 'correspondence')
                ->where('timeline.3.fromOffice.code', $receivingOffice->code)
                ->where('timeline.3.toOffice.code', $reviewOffice->code)
                ->where('timeline.3.actor.name', $receivingHead->name)
                ->where('timeline.3.remarks', 'Forwarded for technical review.')
                ->where('timeline.3.evidence.0.name', 'initial-routing.pdf')
                ->where('timeline.3.evidence.0.relationship', 'route_evidence')
                ->where('timeline.4.event', 'forward')
                ->where('timeline.4.source', 'workflow')
                ->where('timeline.4.fromOffice.code', $reviewOffice->code)
                ->where('timeline.4.toOffice.code', $finalOffice->code)
                ->where('timeline.4.actor.name', $reviewHead->name)
                ->where('timeline.4.remarks', 'Review complete; forwarded to final office.')
                ->where('timeline.4.evidence.0.name', 'review-routing.pdf')
                ->where('timeline.4.evidence.0.relationship', 'route_evidence')
                ->where('timeline.5.event', 'assign')
                ->where('timeline.5.fromOffice.code', $finalOffice->code)
                ->where('timeline.5.toOffice.code', $finalOffice->code));
    }

    public function test_restricted_correspondence_trace_cannot_expose_linked_workflow_movements_to_system_admin(): void
    {
        $receivingOffice = $this->department('RESTRICTED');
        $targetOffice = $this->department('RESTRICTED-TARGET');
        $adminOffice = $this->department('ADMIN');
        $head = $this->human('department_head', $receivingOffice);
        $admin = $this->human('system_admin', $adminOffice);
        $record = $this->record('Restricted traced correspondence');

        $this->actingAs($head)->post('/correspondence/'.$record->public_id.'/workspace/register')->assertRedirect();
        $this->actingAs($head)->post('/correspondence/'.$record->public_id.'/workspace/classify', [
            'classification' => 'restricted',
        ])->assertRedirect();
        $this->actingAs($head)->post('/correspondence/'.$record->public_id.'/workspace/route', [
            'target_department_id' => $targetOffice->id,
            'priority' => 'normal',
            'remarks' => 'Restricted movement must not leak.',
        ])->assertRedirect();

        $response = $this->actingAs($admin)
            ->get('/correspondence/'.$record->public_id.'/workspace')
            ->assertForbidden();

        $this->assertStringNotContainsString('Restricted movement must not leak.', $response->getContent());
    }

    private function department(string $suffix): Department
    {
        return Department::query()->create([
            'code' => 'TRACE-'.Str::upper(Str::random(5)).'-'.$suffix,
            'name' => 'Trace '.$suffix,
            'short_name' => 'TR-'.$suffix,
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
            'name' => 'Trace '.$role.' '.Str::random(5),
            'email' => Str::lower(Str::random(10)).'@example.test',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);

        Employee::query()->create([
            'employee_number' => 'TRACE-EMP-'.Str::upper(Str::random(10)),
            'full_name' => $user->name,
            'work_email' => $user->email,
            'user_id' => $user->id,
            'department_id' => $department->id,
            'position_title' => 'Traceability Test Officer',
            'employment_status' => 'active',
        ]);

        return $user->fresh('employee.department');
    }

    private function record(string $subject): CorrespondenceRecord
    {
        return CorrespondenceRecord::query()->create([
            'public_id' => (string) Str::uuid(),
            'external_reference_no' => 'TRACE-EXT-'.Str::upper(Str::random(14)),
            'source' => 'email',
            'channel' => 'official_email',
            'sender_name' => 'Trace Sender',
            'sender_organization' => 'Trace Origin',
            'subject' => $subject,
            'summary' => 'Incoming document traceability acceptance fixture.',
            'received_at' => now()->subHour(),
            'lifecycle_state' => CorrespondenceLifecycleState::Received->value,
        ]);
    }
}
