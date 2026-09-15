<?php

namespace Tests\Feature;

use App\Domain\Correspondence\CorrespondenceClassification;
use App\Domain\Correspondence\CorrespondenceLifecycleState;
use App\Models\CorrespondenceEvent;
use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentLink;
use App\Models\Employee;
use App\Models\TransactionEvent;
use App\Models\User;
use App\Models\WorkflowTransaction;
use App\Services\TransactionWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CoreDocumentAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
    }

    public function test_transaction_creation_persists_private_hashed_evidence_and_exact_links(): void
    {
        $origin = $this->department('TX-ORIGIN');
        $target = $this->department('TX-TARGET');
        $creator = $this->human('department_head', $origin);
        $contents = "%PDF-1.7\nCore portal evidence\n%%EOF";
        $file = UploadedFile::fake()->createWithContent('official-request.pdf', $contents);

        $this->actingAs($creator)
            ->post('/transactions', [
                'transaction_type' => 'internal_request',
                'title' => 'Evidence-backed transaction',
                'description' => 'Transaction with protected evidence.',
                'priority' => 'normal',
                'target_department_id' => $target->id,
                'remarks' => 'Initial routing.',
                'evidence' => [$file],
            ])
            ->assertRedirect();

        $transaction = WorkflowTransaction::query()->sole();
        $event = TransactionEvent::query()
            ->where('transaction_id', $transaction->id)
            ->where('action', 'submitted')
            ->sole();
        $document = Document::query()->sole();

        $this->assertSame('documents', $document->storage_disk);
        $this->assertSame('internal', $document->classification);
        $this->assertSame('application/pdf', $document->mime_type);
        $this->assertSame(strlen($contents), $document->size_bytes);
        $this->assertSame(hash('sha256', $contents), $document->checksum_sha256);
        $this->assertSame($creator->id, $document->uploaded_by_user_id);
        $this->assertSame($origin->id, $document->owner_department_id);
        $this->assertSame('official-request.pdf', $document->original_name);
        $this->assertStringNotContainsString('official-request', (string) $document->storage_path);
        $this->assertMatchesRegularExpression('#^\d{4}/\d{2}/[0-9a-f-]{36}\.pdf$#', (string) $document->storage_path);
        Storage::disk('documents')->assertExists($document->storage_path);

        $this->assertDatabaseHas('document_links', [
            'document_id' => $document->id,
            'linkable_type' => $transaction->getMorphClass(),
            'linkable_id' => $transaction->id,
            'relationship' => 'supporting_document',
        ]);
        $this->assertDatabaseHas('document_links', [
            'document_id' => $document->id,
            'linkable_type' => $event->getMorphClass(),
            'linkable_id' => $event->id,
            'relationship' => 'route_evidence',
        ]);

        $this->actingAs($creator)
            ->get('/transactions/'.$transaction->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('evidence.record.0.publicId', $document->public_id)
                ->where('evidence.record.0.downloadUrl', '/documents/'.$document->public_id.'/download')
                ->where('evidence.events.'.$event->id.'.0.relationship', 'route_evidence'));
    }

    public function test_allowed_docx_and_image_content_are_accepted_with_canonical_metadata(): void
    {
        $office = $this->department('FILES');
        $target = $this->department('FILES-TARGET');
        $creator = $this->human('department_head', $office);
        $docx = UploadedFile::fake()->createWithContent(
            'brief.docx',
            "PK\x03\x04fake-package-[Content_Types].xml-word/document.xml",
        );
        $png = UploadedFile::fake()->createWithContent(
            'photo.png',
            "\x89PNG\r\n\x1A\n".str_repeat("\0", 24),
        );

        $this->actingAs($creator)
            ->post('/transactions', [
                'transaction_type' => 'document_review',
                'title' => 'Mixed evidence types',
                'priority' => 'normal',
                'target_department_id' => $target->id,
                'evidence' => [$docx, $png],
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('documents', 2);
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            Document::query()->where('original_name', 'brief.docx')->value('mime_type'),
        );
        $this->assertSame('image/png', Document::query()->where('original_name', 'photo.png')->value('mime_type'));
    }

    public function test_spoofed_pdf_and_non_docx_zip_are_rejected_before_transaction_mutation(): void
    {
        $origin = $this->department('SPOOF');
        $target = $this->department('SPOOF-TARGET');
        $creator = $this->human('department_head', $origin);
        $base = [
            'transaction_type' => 'internal_request',
            'title' => 'Must not persist',
            'priority' => 'normal',
            'target_department_id' => $target->id,
        ];

        $this->actingAs($creator)
            ->from('/transactions/create')
            ->post('/transactions', [
                ...$base,
                'evidence' => [UploadedFile::fake()->createWithContent('malware.pdf', "MZ\x90\x00executable")],
            ])
            ->assertSessionHasErrors('evidence.0');

        $this->actingAs($creator)
            ->from('/transactions/create')
            ->post('/transactions', [
                ...$base,
                'evidence' => [UploadedFile::fake()->createWithContent('renamed.docx', "PK\x03\x04ordinary-zip")],
            ])
            ->assertSessionHasErrors('evidence.0');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transaction_events', 0);
        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_links', 0);
        $this->assertSame([], Storage::disk('documents')->allFiles());
    }

    public function test_configured_upload_size_limit_is_enforced_before_mutation(): void
    {
        config(['documents.max_upload_mb' => 1]);
        $origin = $this->department('SIZE');
        $target = $this->department('SIZE-TARGET');
        $creator = $this->human('department_head', $origin);

        $this->actingAs($creator)
            ->from('/transactions/create')
            ->post('/transactions', [
                'transaction_type' => 'internal_request',
                'title' => 'Oversize evidence',
                'priority' => 'normal',
                'target_department_id' => $target->id,
                'evidence' => [UploadedFile::fake()->create('large.pdf', 1100, 'application/pdf')],
            ])
            ->assertSessionHasErrors('evidence.0');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_transaction_transition_evidence_is_linked_to_exact_append_only_event(): void
    {
        $origin = $this->department('ACTION-ORIGIN');
        $current = $this->department('ACTION-CURRENT');
        $creator = $this->human('department_head', $origin);
        $actor = $this->human('department_head', $current);
        $transaction = app(TransactionWorkflowService::class)->create($creator, [
            'transaction_type' => 'internal_request',
            'title' => 'Action evidence transaction',
            'priority' => 'normal',
            'target_department_id' => $current->id,
        ]);
        $file = UploadedFile::fake()->createWithContent('review.pdf', "%PDF-1.7\nreview\n%%EOF");

        $this->actingAs($actor)
            ->post('/transactions/'.$transaction->id.'/transition', [
                'action' => 'mark_review',
                'remarks' => 'Reviewed with attached proof.',
                'evidence' => [$file],
            ])
            ->assertRedirect('/transactions/'.$transaction->id);

        $event = TransactionEvent::query()
            ->where('transaction_id', $transaction->id)
            ->where('action', 'mark_review')
            ->sole();
        $document = Document::query()->sole();

        $this->assertDatabaseHas('document_links', [
            'document_id' => $document->id,
            'linkable_type' => $event->getMorphClass(),
            'linkable_id' => $event->id,
            'relationship' => 'action_evidence',
        ]);
        $this->assertDatabaseMissing('document_links', [
            'document_id' => $document->id,
            'linkable_type' => $transaction->getMorphClass(),
            'linkable_id' => $transaction->id,
        ]);
    }

    public function test_protected_download_reauthorizes_parent_and_guessed_storage_path_is_not_served(): void
    {
        $origin = $this->department('DOWNLOAD-ORIGIN');
        $target = $this->department('DOWNLOAD-TARGET');
        $other = $this->department('DOWNLOAD-OTHER');
        $creator = $this->human('department_head', $origin);
        $outsider = $this->human('department_head', $other);
        $file = UploadedFile::fake()->createWithContent('download.pdf', "%PDF-1.7\ndownload\n%%EOF");

        $this->actingAs($creator)->post('/transactions', [
            'transaction_type' => 'internal_request',
            'title' => 'Protected evidence download',
            'priority' => 'normal',
            'target_department_id' => $target->id,
            'evidence' => [$file],
        ])->assertRedirect();

        $document = Document::query()->sole();
        $this->assertFalse((bool) config('filesystems.disks.documents.serve'));

        $this->actingAs($creator)
            ->get('/documents/'.$document->public_id.'/download')
            ->assertOk()
            ->assertHeader('x-content-type-options', 'nosniff');

        $this->actingAs($outsider)
            ->get('/documents/'.$document->public_id.'/download')
            ->assertForbidden();

        $this->actingAs($creator)
            ->get('/storage/documents/'.$document->storage_path)
            ->assertStatus(403);
    }

    public function test_correspondence_lifecycle_evidence_inherits_classification_and_tracks_exact_events(): void
    {
        $origin = $this->department('COR-ORIGIN');
        $target = $this->department('COR-TARGET');
        $head = $this->human('department_head', $origin);
        $targetHead = $this->human('department_head', $target);
        $record = $this->record('Evidence-backed incoming document');

        $this->actingAs($head)->post('/correspondence/'.$record->public_id.'/workspace/register', [
            'evidence' => [UploadedFile::fake()->createWithContent('received.pdf', "%PDF-1.7\nreceived\n%%EOF")],
        ])->assertRedirect('/correspondence/'.$record->public_id.'/workspace');

        $registeredDocument = Document::query()->where('original_name', 'received.pdf')->sole();
        $registeredEvent = CorrespondenceEvent::query()->where('correspondence_record_id', $record->id)->where('event', 'registered')->sole();
        $this->assertDatabaseHas('document_links', [
            'document_id' => $registeredDocument->id,
            'linkable_type' => $record->getMorphClass(),
            'linkable_id' => $record->id,
            'relationship' => 'supporting_document',
        ]);
        $this->assertDatabaseHas('document_links', [
            'document_id' => $registeredDocument->id,
            'linkable_type' => $registeredEvent->getMorphClass(),
            'linkable_id' => $registeredEvent->id,
            'relationship' => 'action_evidence',
        ]);

        $this->actingAs($head)->post('/correspondence/'.$record->public_id.'/workspace/classify', [
            'classification' => 'restricted',
            'remarks' => 'Restricted municipal correspondence.',
        ])->assertRedirect('/correspondence/'.$record->public_id.'/workspace');

        $this->assertSame('restricted', $registeredDocument->fresh()->classification);

        $this->actingAs($head)->post('/correspondence/'.$record->public_id.'/workspace/route', [
            'target_department_id' => $target->id,
            'priority' => 'normal',
            'evidence' => [UploadedFile::fake()->createWithContent(
                'routing.docx',
                "PK\x03\x04[Content_Types].xml-word/document.xml-routing",
            )],
        ])->assertRedirect('/correspondence');

        $routed = $record->fresh('workflowTransaction');
        $routeEvent = CorrespondenceEvent::query()->where('correspondence_record_id', $record->id)->where('event', 'routed')->sole();
        $routeDocument = Document::query()->where('original_name', 'routing.docx')->sole();
        $this->assertSame('restricted', $routeDocument->classification);
        $this->assertDatabaseHas('document_links', [
            'document_id' => $routeDocument->id,
            'linkable_type' => $routeEvent->getMorphClass(),
            'linkable_id' => $routeEvent->id,
            'relationship' => 'route_evidence',
        ]);

        app(TransactionWorkflowService::class)->transition(
            $targetHead,
            $routed->workflowTransaction,
            'assign',
            assignedEmployeeId: $targetHead->employee->id,
        );

        $this->actingAs($targetHead)->post('/correspondence/'.$record->public_id.'/workspace/act', [
            'remarks' => 'Started field action.',
            'evidence' => [UploadedFile::fake()->createWithContent('photo.jpg', "\xFF\xD8\xFF".str_repeat("\0", 32))],
        ])->assertRedirect('/correspondence/'.$record->public_id.'/workspace');

        $actEvent = CorrespondenceEvent::query()->where('correspondence_record_id', $record->id)->where('event', 'in_action')->sole();
        $photo = Document::query()->where('original_name', 'photo.jpg')->sole();
        $this->assertSame('restricted', $photo->classification);
        $this->assertDatabaseHas('document_links', [
            'document_id' => $photo->id,
            'linkable_type' => $actEvent->getMorphClass(),
            'linkable_id' => $actEvent->id,
            'relationship' => 'action_evidence',
        ]);

        $this->actingAs($targetHead)
            ->get('/correspondence/'.$record->public_id.'/workspace')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('evidence.record.0.publicId', $registeredDocument->public_id)
                ->where('evidence.events.'.$routeEvent->id.'.0.publicId', $routeDocument->public_id)
                ->where('evidence.events.'.$actEvent->id.'.0.publicId', $photo->public_id));
    }

    public function test_restricted_correspondence_evidence_does_not_inherit_system_admin_transaction_oversight(): void
    {
        $owner = $this->department('RESTRICTED-OWNER');
        $adminOffice = $this->department('ADMIN-OFFICE');
        $head = $this->human('department_head', $owner);
        $admin = $this->human('system_admin', $adminOffice);
        $record = $this->record('Restricted evidence');

        $this->actingAs($head)->post('/correspondence/'.$record->public_id.'/workspace/register', [
            'evidence' => [UploadedFile::fake()->createWithContent('restricted.pdf', "%PDF-1.7\nsecret\n%%EOF")],
        ])->assertRedirect();
        $this->actingAs($head)->post('/correspondence/'.$record->public_id.'/workspace/classify', [
            'classification' => 'restricted',
        ])->assertRedirect();

        $document = Document::query()->sole();
        $this->actingAs($admin)
            ->get('/documents/'.$document->public_id.'/download')
            ->assertForbidden();
    }

    public function test_invalid_correspondence_evidence_does_not_advance_lifecycle_and_live_payload_has_no_documents(): void
    {
        $origin = $this->department('ROLLBACK-ORIGIN');
        $target = $this->department('ROLLBACK-TARGET');
        $head = $this->human('department_head', $origin);
        $targetHead = $this->human('department_head', $target);
        $record = $this->record('Invalid evidence intake');

        $this->actingAs($head)
            ->from('/correspondence/'.$record->public_id.'/workspace')
            ->post('/correspondence/'.$record->public_id.'/workspace/register', [
                'evidence' => [UploadedFile::fake()->createWithContent('fake.pdf', 'plain text pretending to be a PDF')],
            ])
            ->assertSessionHasErrors('evidence.0');

        $this->assertSame(CorrespondenceLifecycleState::Received, $record->fresh()->lifecycle_state);
        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_links', 0);

        $transaction = app(TransactionWorkflowService::class)->create($head, [
            'transaction_type' => 'internal_request',
            'title' => 'Live response remains lightweight',
            'priority' => 'normal',
            'target_department_id' => $target->id,
        ]);

        $this->actingAs($targetHead)
            ->getJson('/transactions/'.$transaction->id.'/live')
            ->assertOk()
            ->assertJsonMissingPath('evidence')
            ->assertJsonMissingPath('documents')
            ->assertJsonMissingPath('transaction.documents');
    }

    private function department(string $suffix): Department
    {
        return Department::query()->create([
            'code' => 'EVID-'.Str::upper(Str::random(5)).'-'.$suffix,
            'name' => 'Evidence '.$suffix,
            'short_name' => 'EV-'.$suffix,
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
            'name' => 'Evidence '.$role.' '.Str::random(5),
            'email' => Str::lower(Str::random(10)).'@example.test',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);

        Employee::query()->create([
            'employee_number' => 'EVID-EMP-'.Str::upper(Str::random(10)),
            'full_name' => $user->name,
            'work_email' => $user->email,
            'user_id' => $user->id,
            'department_id' => $department->id,
            'position_title' => 'Evidence Test Officer',
            'employment_status' => 'active',
        ]);

        return $user->fresh('employee.department');
    }

    private function record(string $subject): CorrespondenceRecord
    {
        return CorrespondenceRecord::query()->create([
            'public_id' => (string) Str::uuid(),
            'external_reference_no' => 'EXT-'.Str::upper(Str::random(16)),
            'source' => 'email',
            'channel' => 'official_email',
            'sender_name' => 'Evidence Sender',
            'sender_organization' => 'Evidence Office',
            'subject' => $subject,
            'summary' => 'Incoming correspondence used for protected evidence tests.',
            'received_at' => now()->subHour(),
            'lifecycle_state' => CorrespondenceLifecycleState::Received->value,
        ]);
    }
}
