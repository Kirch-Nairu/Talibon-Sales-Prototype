<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\LegislativeAgendaItem;
use App\Models\LegislativeSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1ExecutiveLegislativeWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_admin_can_schedule_legislative_session_and_calendar_event_is_published(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();

        $this->actingAs($admin)->post('/legislative-workspace/sessions', [
            'session_code' => 'SB-REG-2026-TEST-01',
            'session_type' => 'regular',
            'title' => 'Regular Session Test',
            'scheduled_at' => now()->addDay()->setTime(9, 0)->toDateTimeString(),
            'location' => 'SB Session Hall',
        ])->assertRedirect();

        $session = LegislativeSession::query()->where('session_code', 'SB-REG-2026-TEST-01')->firstOrFail();
        $this->assertDatabaseHas('calendar_events', [
            'event_key' => 'legislative-session-'.$session->id,
            'event_type' => 'legislative_session',
            'source_domain' => 'legislation',
        ]);
        $this->assertSame(1, CalendarEvent::query()->where('source_id', $session->id)->where('source_type', LegislativeSession::class)->count());
    }

    public function test_featured_legislative_account_can_manage_the_sb_workspace(): void
    {
        $this->seed();
        $legislative = User::query()->where('email', 'legislative@talibon.demo')->firstOrFail();

        $this->actingAs($legislative)->get('/legislative-workspace')->assertOk();
        $this->actingAs($legislative)->post('/legislative-workspace/sessions', [
            'session_code' => 'SB-DEMO-MANAGER-01',
            'session_type' => 'committee',
            'title' => 'Committee Session Managed by Legislative Demo Account',
            'scheduled_at' => now()->addDays(3)->setTime(13, 30)->toDateTimeString(),
            'location' => 'SB Committee Room',
        ])->assertRedirect();

        $this->assertDatabaseHas('legislative_sessions', ['session_code' => 'SB-DEMO-MANAGER-01']);
    }

    public function test_agenda_sequence_is_session_scoped_and_duplicate_sequence_is_rejected(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@talibon.demo')->firstOrFail();
        $session = LegislativeSession::query()->create([
            'session_code' => 'SB-AGENDA-TEST-01',
            'session_type' => 'regular',
            'title' => 'Agenda Sequence Test',
            'scheduled_at' => now()->addDays(2),
            'status' => 'scheduled',
            'created_by_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)->post('/legislative-workspace/sessions/'.$session->id.'/agenda', [
            'sequence_no' => 1,
            'title' => 'First Matter',
        ])->assertRedirect();

        $this->assertSame(1, LegislativeAgendaItem::query()->where('legislative_session_id', $session->id)->count());

        $this->actingAs($admin)
            ->from('/legislative-workspace')
            ->post('/legislative-workspace/sessions/'.$session->id.'/agenda', [
                'sequence_no' => 1,
                'title' => 'Duplicate Sequence',
            ])
            ->assertRedirect('/legislative-workspace')
            ->assertSessionHasErrors('sequence_no');
    }

    public function test_engineering_user_cannot_open_legislative_workspace_but_mayor_workspace_remains_restricted(): void
    {
        $this->seed();
        $engineering = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();

        $this->actingAs($engineering)->get('/legislative-workspace')->assertForbidden();
        $this->actingAs($engineering)->get('/mayor-office')->assertForbidden();
    }

    public function test_mayor_workspace_renders_municipality_wide_accountability_surface(): void
    {
        $this->seed();
        $mayor = User::query()->where('email', 'mayor@talibon.demo')->firstOrFail();

        $this->actingAs($mayor)->get('/mayor-office')->assertOk();
    }
}
