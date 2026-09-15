<?php

namespace Tests\Feature;

use App\Models\LeaveCreditAccount;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveCreditTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_approval_deducts_balance_tracked_leave_atomically(): void
    {
        $this->seed();

        $employeeUser = User::query()->where('email', 'employee@talibon.demo')->firstOrFail();
        $hr = User::query()->where('email', 'hr@talibon.demo')->firstOrFail();
        $vacation = LeaveType::query()->where('code', 'VL')->firstOrFail();
        $account = LeaveCreditAccount::query()
            ->where('employee_id', $employeeUser->employee->id)
            ->where('leave_type_id', $vacation->id)
            ->firstOrFail();

        $startingBalance = (float) $account->balance;

        $this->actingAs($employeeUser)
            ->post('/hris/leave-requests', [
                'leave_type_id' => $vacation->id,
                'start_date' => now()->addDays(10)->toDateString(),
                'end_date' => now()->addDays(10)->toDateString(),
                'units' => 1,
                'reason' => 'Automated leave-credit proof.',
            ])
            ->assertRedirect();

        $leave = LeaveRequest::query()
            ->where('employee_id', $employeeUser->employee->id)
            ->where('reason', 'Automated leave-credit proof.')
            ->firstOrFail();

        $this->actingAs($hr)
            ->post("/hris/admin/leave-requests/{$leave->id}/approve", [
                'review_notes' => 'Approved by automated feature proof.',
            ])
            ->assertRedirect();

        $leave->refresh();
        $account->refresh();

        $this->assertSame('approved', $leave->status);
        $this->assertEquals($startingBalance - 1.0, (float) $account->balance);
        $this->assertDatabaseHas('leave_credit_transactions', [
            'leave_credit_account_id' => $account->id,
            'entry_type' => 'approved_leave',
            'source_id' => $leave->id,
        ]);
    }
}
