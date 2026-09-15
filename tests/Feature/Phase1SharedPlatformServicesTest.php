<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\Document;
use App\Models\PlatformNotification;
use App\Models\TransactionEvent;
use App\Models\User;
use App\Models\WorkflowTransaction;
use App\Services\PlatformNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1SharedPlatformServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_routed_transaction_creates_persistent_notification_and_calendar_deadline(): void
    {
        $this->seed();

        $engineering = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
        $budgetUser = User::query()->where('email', 'budget@talibon.demo')->firstOrFail();
        $budget = Department::query()->where('code', 'BUDGET')->firstOrFail();

        $this->actingAs($engineering)->post('/transactions', [
            'transaction_type' => 'funding_request',
            'title' => 'Phase 1 Shared Services Test',
            'priority' => 'high',
            'target_department_id' => $budget->id,
        ])->assertRedirect();

        $transaction = WorkflowTransaction::query()->where('title', 'Phase 1 Shared Services Test')->firstOrFail();
        $event = TransactionEvent::query()->where('transaction_id', $transaction->id)->where('action', 'submitted')->firstOrFail();

        $this->assertDatabaseHas('platform_notifications', [
            'user_id' => $budgetUser->id,
            'event_key' => 'transaction-event-'.$event->id,
            'source_domain' => 'transaction',
            'source_id' => $transaction->id,
        ]);
        $this->assertDatabaseHas('calendar_events', [
            'event_key' => 'transaction-due-'.$transaction->id,
            'department_id' => $budget->id,
            'source_domain' => 'transaction',
            'source_id' => $transaction->id,
            'status' => 'scheduled',
        ]);
    }

    public function test_notification_event_key_is_deduplicated_per_recipient_and_can_be_acknowledged(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'budget@talibon.demo')->firstOrFail();
        $service = app(PlatformNotificationService::class);
        $payload = [
            'event_key' => 'phase1-dedupe-test',
            'source_domain' => 'hr',
            'source_type' => 'test',
            'source_id' => 1,
            'priority' => 'acknowledgement_required',
            'title' => 'Acknowledgement required',
            'message' => 'Confirm receipt.',
            'action_url' => '/dashboard',
            'requires_acknowledgement' => true,
        ];

        $service->notifyUser($user, $payload);
        $service->notifyUser($user, $payload);

        $this->assertSame(1, PlatformNotification::query()->where('user_id', $user->id)->where('event_key', 'phase1-dedupe-test')->count());
        $notification = PlatformNotification::query()->where('user_id', $user->id)->where('event_key', 'phase1-dedupe-test')->firstOrFail();

        $this->actingAs($user)->post('/notifications/'.$notification->id.'/acknowledge')->assertRedirect();

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
        $this->assertNotNull($notification->acknowledged_at);
    }

    public function test_calendar_is_visible_to_the_receiving_office_and_document_metadata_can_link_to_a_domain_record(): void
    {
        $this->seed();

        $engineering = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
        $budgetUser = User::query()->where('email', 'budget@talibon.demo')->firstOrFail();
        $budget = Department::query()->where('code', 'BUDGET')->firstOrFail();

        $this->actingAs($engineering)->post('/transactions', [
            'transaction_type' => 'document_review',
            'title' => 'Calendar Visibility Test',
            'priority' => 'normal',
            'target_department_id' => $budget->id,
        ])->assertRedirect();

        $transaction = WorkflowTransaction::query()->where('title', 'Calendar Visibility Test')->firstOrFail();
        $this->actingAs($budgetUser)->get('/calendar')->assertOk();

        $document = Document::query()->create([
            'title' => 'Supporting review document',
            'document_type' => 'supporting_document',
            'classification' => 'internal',
            'owner_department_id' => $budget->id,
            'uploaded_by_user_id' => $budgetUser->id,
            'storage_disk' => 'local',
            'storage_path' => 'protected/test/supporting-review.pdf',
        ]);
        $document->links()->create([
            'linkable_type' => WorkflowTransaction::class,
            'linkable_id' => $transaction->id,
            'relationship' => 'attachment',
            'created_by_user_id' => $budgetUser->id,
        ]);

        $this->assertNotNull($document->public_id);
        $this->assertDatabaseHas('document_links', [
            'document_id' => $document->id,
            'linkable_type' => WorkflowTransaction::class,
            'linkable_id' => $transaction->id,
        ]);
        $this->assertTrue(CalendarEvent::query()->where('event_key', 'transaction-due-'.$transaction->id)->exists());
    }
}
