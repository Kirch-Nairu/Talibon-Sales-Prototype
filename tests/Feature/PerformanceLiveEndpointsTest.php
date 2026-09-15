<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Memorandum;
use App\Models\MemoRecipient;
use App\Models\PlatformNotification;
use App\Models\TransactionEvent;
use App\Models\User;
use App\Models\WorkflowTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class PerformanceLiveEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_endpoints_require_authentication_and_mfa_assurance(): void
    {
        $mayorOffice = $this->department('MAYOR', 'Mayor Office');
        $adminOffice = $this->department('ADMIN', 'Admin Office');
        $admin = $this->human('system_admin', $adminOffice);
        $transaction = $this->transaction($adminOffice, $mayorOffice, $admin);

        foreach ([
            '/notifications/feed',
            '/transactions/'.$transaction->id.'/live',
            '/mayor-office/live',
        ] as $url) {
            $this->get($url)->assertRedirect('/login');
        }

        Auth::guard('web')->login($admin);

        foreach ([
            '/notifications/feed',
            '/transactions/'.$transaction->id.'/live',
            '/mayor-office/live',
        ] as $url) {
            $this->get($url)->assertRedirect(route('mfa.enroll'));
        }
    }

    public function test_notification_feed_preserves_memo_and_platform_notification_contracts(): void
    {
        $office = $this->department('FEED', 'Feed Office');
        $user = $this->human('department_staff', $office);

        $memo = Memorandum::query()->create([
            'memo_number' => 'MEMO-PERF-001',
            'title' => 'Performance feed memorandum',
            'body' => 'Synthetic notification feed fixture.',
            'issued_by_user_id' => $user->id,
            'issued_by_department_id' => $office->id,
            'audience_type' => 'employees',
            'requires_acknowledgement' => true,
            'classification' => 'internal',
            'status' => 'published',
            'published_at' => now()->subMinute(),
        ]);

        $recipient = MemoRecipient::query()->create([
            'memorandum_id' => $memo->id,
            'user_id' => $user->id,
            'delivered_at' => now()->subMinute(),
        ]);

        $platform = PlatformNotification::query()->create([
            'user_id' => $user->id,
            'department_id' => $office->id,
            'event_key' => 'perf-feed-platform',
            'source_domain' => 'transaction',
            'source_type' => WorkflowTransaction::class,
            'source_id' => 99,
            'priority' => 'action_required',
            'title' => 'Workflow action required',
            'message' => 'Synthetic platform notification.',
            'action_url' => '/transactions/99',
            'requires_acknowledgement' => false,
        ]);

        $this->actingAs($user)
            ->getJson('/notifications/feed')
            ->assertOk()
            ->assertJsonPath('pendingMemo.id', $memo->id)
            ->assertJsonPath('pendingMemo.memo_number', 'MEMO-PERF-001')
            ->assertJsonPath('unreadMemoCount', 1)
            ->assertJsonPath('unreadPlatformNotificationCount', 1)
            ->assertJsonPath('notificationCount', 2)
            ->assertJsonFragment([
                'key' => 'memo-'.$recipient->id,
                'type' => 'memorandum',
                'urgent' => true,
            ])
            ->assertJsonFragment([
                'key' => 'platform-'.$platform->id,
                'type' => 'transaction',
                'urgent' => true,
            ]);
    }

    public function test_notification_feed_preserves_transaction_event_fallback(): void
    {
        $origin = $this->department('FEED-ORIGIN', 'Feed Origin');
        $current = $this->department('FEED-CURRENT', 'Feed Current');
        $originUser = $this->human('department_head', $origin);
        $recipient = $this->human('department_staff', $current);
        $transaction = $this->transaction($origin, $current, $originUser);

        $event = $this->event(
            $transaction,
            $originUser,
            $origin,
            $current,
            'forward',
        );

        PlatformNotification::query()
            ->where('user_id', $recipient->id)
            ->delete();

        $this->actingAs($recipient)
            ->getJson('/notifications/feed')
            ->assertOk()
            ->assertJsonFragment([
                'key' => 'tx-event-'.$event->id,
                'type' => 'transaction',
                'url' => '/transactions/'.$transaction->id,
            ]);
    }

    public function test_transaction_live_state_uses_view_policy_and_returns_only_mutable_projection(): void
    {
        $origin = $this->department('LIVE-ORIGIN', 'Live Origin');
        $current = $this->department('LIVE-CURRENT', 'Live Current');
        $unrelated = $this->department('LIVE-OTHER', 'Live Other');
        $creator = $this->human('department_head', $origin);
        $actor = $this->human('department_head', $current);
        $outsider = $this->human('department_head', $unrelated);
        $transaction = $this->transaction(
            $origin,
            $current,
            $creator,
            $actor->employee,
        );

        $first = $this->event($transaction, $creator, $origin, $current, 'submitted');
        $second = $this->event($transaction, $actor, $current, $current, 'assign');

        $this->actingAs($actor)
            ->getJson('/transactions/'.$transaction->id.'/live?after_event_id='.$first->id)
            ->assertOk()
            ->assertJsonPath('transaction.status', 'submitted')
            ->assertJsonPath('transaction.current_department.id', $current->id)
            ->assertJsonPath('transaction.assigned_employee.id', $actor->employee->id)
            ->assertJsonPath('permissions.canTransition', true)
            ->assertJsonPath('permissions.canAssign', true)
            ->assertJsonPath('events.0.id', $second->id)
            ->assertJsonCount(1, 'events')
            ->assertJsonMissingPath('transaction.title')
            ->assertJsonMissingPath('transaction.creator')
            ->assertJsonMissingPath('departments');

        $this->actingAs($outsider)
            ->getJson('/transactions/'.$transaction->id.'/live')
            ->assertForbidden();
    }

    public function test_transaction_live_state_hides_mutation_permissions_after_terminal_state(): void
    {
        $origin = $this->department('TERM-ORIGIN', 'Terminal Origin');
        $mayorOffice = $this->department('MAYOR', 'Mayor Office');
        $creator = $this->human('department_head', $origin);
        $approver = $this->human('mayor_staff', $mayorOffice);
        $admin = $this->human('system_admin', $mayorOffice);
        $transaction = $this->transaction($origin, $mayorOffice, $creator);

        $transaction->forceFill([
            'status' => 'approved',
            'completed_at' => now(),
        ])->save();

        $this->actingAs($approver)
            ->getJson('/transactions/'.$transaction->id.'/live')
            ->assertOk()
            ->assertJsonPath('transaction.status', 'approved')
            ->assertJsonPath('permissions.canMayorDecision', false);

        $this->actingAs($admin)
            ->getJson('/transactions/'.$transaction->id.'/live')
            ->assertOk()
            ->assertJsonPath('permissions.canTransition', false)
            ->assertJsonPath('permissions.canMayorDecision', false)
            ->assertJsonPath('permissions.canAssign', false);
    }

    public function test_mayor_live_endpoint_preserves_existing_executive_access_boundary(): void
    {
        $mayorOffice = $this->department('MAYOR', 'Mayor Office');
        $otherOffice = $this->department('EXEC-OTHER', 'Executive Other');

        $mayorStaff = $this->human('mayor_staff', $mayorOffice);
        $admin = $this->human('system_admin', $otherOffice);
        $misplacedMayorStaff = $this->human('mayor_staff', $otherOffice);
        $departmentHead = $this->human('department_head', $otherOffice);

        $this->transaction($otherOffice, $mayorOffice, $departmentHead);

        $this->actingAs($mayorStaff)
            ->getJson('/mayor-office/live')
            ->assertOk()
            ->assertJsonPath('stats.total', 1);

        $this->actingAs($admin)
            ->getJson('/mayor-office/live')
            ->assertOk()
            ->assertJsonPath('stats.total', 1);

        $this->actingAs($misplacedMayorStaff)
            ->getJson('/mayor-office/live')
            ->assertForbidden();

        $this->actingAs($departmentHead)
            ->getJson('/mayor-office/live')
            ->assertForbidden();
    }

    public function test_frontend_polling_contract_uses_live_json_endpoints_without_inertia_reload(): void
    {
        $files = [
            resource_path('js/layouts/AppLayout.tsx') => '/notifications/feed',
            resource_path('js/pages/Transactions/Show.tsx') => '/live?after_event_id=',
            resource_path('js/pages/MayorOffice.tsx') => '/mayor-office/live',
        ];

        foreach ($files as $path => $endpoint) {
            $source = file_get_contents($path);
            $this->assertIsString($source);
            $this->assertStringNotContainsString('router.reload', $source);
            $this->assertStringContainsString('useVisiblePolling', $source);
            $this->assertStringContainsString($endpoint, $source);
        }

        $hook = file_get_contents(resource_path('js/hooks/useVisiblePolling.ts'));
        $this->assertIsString($hook);
        $this->assertStringContainsString("document.visibilityState !== 'visible'", $hook);
        $this->assertStringContainsString('inFlight.current', $hook);
        $this->assertStringContainsString('new AbortController()', $hook);
        $this->assertStringContainsString("window.addEventListener('focus'", $hook);
        $this->assertStringContainsString("document.addEventListener('visibilitychange'", $hook);
        $this->assertStringContainsString('window.clearInterval(timer)', $hook);

        $this->assertTrue(Route::has('notifications.feed'));
        $this->assertTrue(Route::has('transactions.live'));
        $this->assertTrue(Route::has('mayor-office.live'));
    }

    private function department(string $code, string $name): Department
    {
        return Department::query()->create([
            'code' => $code,
            'name' => $name,
            'short_name' => $code,
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
            'name' => Str::headline($role).' '.Str::random(5),
            'email' => Str::lower(Str::random(10)).'@example.test',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);

        Employee::query()->create([
            'employee_number' => 'PERF-'.Str::upper(Str::random(10)),
            'full_name' => $user->name,
            'work_email' => $user->email,
            'user_id' => $user->id,
            'department_id' => $department->id,
            'position_title' => 'Performance Test Officer',
            'employment_status' => 'active',
        ]);

        return $user->fresh('employee.department');
    }

    private function transaction(
        Department $origin,
        Department $current,
        User $creator,
        ?Employee $assignee = null,
    ): WorkflowTransaction {
        return WorkflowTransaction::query()->create([
            'reference_no' => 'PERF-TX-'.Str::upper(Str::random(10)),
            'transaction_type' => 'internal_request',
            'title' => 'Performance live endpoint fixture',
            'description' => 'Synthetic performance-hardening transaction.',
            'priority' => 'normal',
            'origin_department_id' => $origin->id,
            'current_department_id' => $current->id,
            'created_by_user_id' => $creator->id,
            'assigned_employee_id' => $assignee?->id,
            'status' => 'submitted',
            'received_at' => now()->subHour(),
            'due_at' => now()->addDay(),
        ]);
    }

    private function event(
        WorkflowTransaction $transaction,
        User $actor,
        Department $from,
        Department $to,
        string $action,
    ): TransactionEvent {
        return TransactionEvent::query()->create([
            'transaction_id' => $transaction->id,
            'actor_user_id' => $actor->id,
            'from_department_id' => $from->id,
            'to_department_id' => $to->id,
            'action' => $action,
            'previous_status' => 'submitted',
            'new_status' => 'submitted',
            'remarks' => 'Synthetic live endpoint event.',
            'created_at' => now(),
        ]);
    }
}
