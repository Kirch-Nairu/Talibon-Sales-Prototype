<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Memorandum;
use App\Models\MemoRecipient;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\MemorandumService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class MemorandumDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_mayor_can_publish_and_employee_can_view_and_acknowledge(): void
    {
        $this->seed();

        $mayor = User::query()->where('email', 'mayor@talibon.demo')->firstOrFail();
        $employee = User::query()->where('email', 'employee@talibon.demo')->firstOrFail();

        $this->actingAs($mayor)
            ->post('/memoranda', [
                'memo_number' => 'MEMO-TEST-001',
                'title' => 'Automated Memorandum Delivery Proof',
                'body' => 'Synthetic memorandum used by the feature test.',
                'audience_type' => 'all',
                'audience_ids' => [],
                'requires_acknowledgement' => true,
                'classification' => 'internal',
                'expires_at' => null,
            ])
            ->assertRedirect();

        $memo = Memorandum::query()->where('memo_number', 'MEMO-TEST-001')->firstOrFail();
        $recipient = MemoRecipient::query()
            ->where('memorandum_id', $memo->id)
            ->where('user_id', $employee->id)
            ->firstOrFail();

        $this->assertNotNull($recipient->delivered_at);
        $this->assertNull($recipient->viewed_at);
        $this->assertNull($recipient->acknowledged_at);

        $this->actingAs($employee)
            ->get("/memoranda/{$memo->id}")
            ->assertOk();

        $this->assertNotNull($recipient->fresh()->viewed_at);

        $this->actingAs($employee)
            ->post("/memoranda/{$memo->id}/acknowledge")
            ->assertRedirect();

        $firstAcknowledgedAt = $recipient->fresh()->acknowledged_at;
        $this->assertNotNull($firstAcknowledgedAt);

        $this->actingAs($employee)
            ->post("/memoranda/{$memo->id}/acknowledge")
            ->assertRedirect();

        $this->assertTrue($firstAcknowledgedAt->equalTo($recipient->fresh()->acknowledged_at));
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $employee->id,
            'action' => 'memorandum.acknowledged',
            'outcome' => 'allowed',
        ]);
        $this->assertSame(1, $this->acknowledgementAuditCount($memo, $employee));
    }

    public function test_first_acknowledgement_sets_view_and_acknowledgement_once(): void
    {
        $this->seed();

        $mayor = User::query()->where('email', 'mayor@talibon.demo')->firstOrFail();
        $employee = User::query()->where('email', 'employee@talibon.demo')->firstOrFail();
        [$memo, $recipient] = $this->recipientFixture($mayor, $employee, 'MEMO-H5-FIRST');

        $this->assertNull($recipient->viewed_at);
        $this->assertNull($recipient->acknowledged_at);

        $this->actingAs($employee)
            ->post("/memoranda/{$memo->id}/acknowledge")
            ->assertRedirect();

        $recipient->refresh();
        $this->assertNotNull($recipient->viewed_at);
        $this->assertNotNull($recipient->acknowledged_at);
        $this->assertSame(1, $this->acknowledgementAuditCount($memo, $employee));
    }

    public function test_acknowledgement_preserves_existing_first_view_timestamp(): void
    {
        $this->seed();

        $mayor = User::query()->where('email', 'mayor@talibon.demo')->firstOrFail();
        $employee = User::query()->where('email', 'employee@talibon.demo')->firstOrFail();
        $fixtureViewedAt = now()->subHour();
        [$memo, $recipient] = $this->recipientFixture($mayor, $employee, 'MEMO-H5-VIEWED', true, $fixtureViewedAt);
        $firstViewedAt = $recipient->fresh()->viewed_at;
        $this->assertNotNull($firstViewedAt);

        $this->actingAs($employee)
            ->post("/memoranda/{$memo->id}/acknowledge")
            ->assertRedirect();

        $recipient->refresh();
        $this->assertTrue($firstViewedAt->equalTo($recipient->viewed_at));
        $this->assertNotNull($recipient->acknowledged_at);
        $this->assertSame(1, $this->acknowledgementAuditCount($memo, $employee));
    }

    public function test_non_recipient_cannot_acknowledge_memorandum(): void
    {
        $this->seed();

        $mayor = User::query()->where('email', 'mayor@talibon.demo')->firstOrFail();
        $employee = User::query()->where('email', 'employee@talibon.demo')->firstOrFail();
        [$memo, $recipient] = $this->recipientFixture($mayor, $employee, 'MEMO-H5-NONRECIPIENT');

        $this->actingAs($mayor)
            ->post("/memoranda/{$memo->id}/acknowledge")
            ->assertNotFound();

        $recipient->refresh();
        $this->assertNull($recipient->viewed_at);
        $this->assertNull($recipient->acknowledged_at);
        $this->assertSame(0, $this->acknowledgementAuditCount($memo, $mayor));
        $this->assertSame(0, $this->acknowledgementAuditCount($memo, $employee));
    }

    public function test_acknowledgement_rolls_back_if_audit_write_fails(): void
    {
        $this->seed();

        $mayor = User::query()->where('email', 'mayor@talibon.demo')->firstOrFail();
        $employee = User::query()->where('email', 'employee@talibon.demo')->firstOrFail();
        [$memo, $recipient] = $this->recipientFixture($mayor, $employee, 'MEMO-H5-ROLLBACK');

        $this->mock(AuditLogger::class, function (MockInterface $mock): void {
            $mock->shouldReceive('record')
                ->once()
                ->andThrow(new RuntimeException('Synthetic H5 audit failure.'));
        });

        try {
            app(MemorandumService::class)->acknowledge($employee, $memo);
            $this->fail('Acknowledgement should fail when audit persistence fails.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Synthetic H5 audit failure.', $exception->getMessage());
        }

        $recipient->refresh();
        $this->assertNull($recipient->viewed_at);
        $this->assertNull($recipient->acknowledged_at);
        $this->assertSame(0, AuditLog::query()
            ->where('actor_user_id', $employee->id)
            ->where('action', 'memorandum.acknowledged')
            ->where('entity_id', $memo->id)
            ->count());
    }

    public function test_memorandum_without_acknowledgement_requirement_does_not_accept_acknowledgement(): void
    {
        $this->seed();

        $mayor = User::query()->where('email', 'mayor@talibon.demo')->firstOrFail();
        $employee = User::query()->where('email', 'employee@talibon.demo')->firstOrFail();

        $this->actingAs($mayor)
            ->post('/memoranda', [
                'memo_number' => 'MEMO-TEST-NO-ACK',
                'title' => 'Informational Memorandum Proof',
                'body' => 'Synthetic informational memorandum without acknowledgement requirement.',
                'audience_type' => 'all',
                'audience_ids' => [],
                'requires_acknowledgement' => false,
                'classification' => 'internal',
                'expires_at' => null,
            ])
            ->assertRedirect();

        $memo = Memorandum::query()->where('memo_number', 'MEMO-TEST-NO-ACK')->firstOrFail();
        $recipient = MemoRecipient::query()
            ->where('memorandum_id', $memo->id)
            ->where('user_id', $employee->id)
            ->firstOrFail();

        $this->actingAs($employee)
            ->get("/memoranda/{$memo->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Memoranda/Show')
                ->where('memorandum.requires_acknowledgement', false)
                ->where('recipient.acknowledged_at', null));

        $viewedAt = $recipient->fresh()->viewed_at;
        $this->assertNotNull($viewedAt);

        $this->actingAs($employee)
            ->post("/memoranda/{$memo->id}/acknowledge")
            ->assertStatus(422);

        $recipient->refresh();
        $this->assertNull($recipient->acknowledged_at);
        $this->assertTrue($viewedAt->equalTo($recipient->viewed_at));
        $this->assertSame(0, $this->acknowledgementAuditCount($memo, $employee));
    }

    private function recipientFixture(
        User $issuer,
        User $recipientUser,
        string $memoNumber,
        bool $requiresAcknowledgement = true,
        $viewedAt = null,
    ): array {
        $issuer->loadMissing('employee');
        $memo = Memorandum::query()->create([
            'memo_number' => $memoNumber,
            'title' => 'Synthetic H5 memorandum',
            'body' => 'Synthetic memorandum used only for H5 acknowledgement tests.',
            'issued_by_user_id' => $issuer->id,
            'issued_by_department_id' => $issuer->employee->department_id,
            'audience_type' => 'employees',
            'requires_acknowledgement' => $requiresAcknowledgement,
            'classification' => 'internal',
            'status' => 'published',
            'published_at' => now(),
            'expires_at' => null,
        ]);

        $recipient = MemoRecipient::query()->create([
            'memorandum_id' => $memo->id,
            'user_id' => $recipientUser->id,
            'delivered_at' => now()->subMinute(),
            'viewed_at' => $viewedAt,
            'acknowledged_at' => null,
        ]);

        return [$memo, $recipient];
    }

    private function acknowledgementAuditCount(Memorandum $memo, User $actor): int
    {
        return AuditLog::query()
            ->where('actor_user_id', $actor->id)
            ->where('action', 'memorandum.acknowledged')
            ->where('entity_type', Memorandum::class)
            ->where('entity_id', $memo->id)
            ->count();
    }
}
