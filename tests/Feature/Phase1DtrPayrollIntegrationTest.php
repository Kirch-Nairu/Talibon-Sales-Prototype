<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\DtrDailySummary;
use App\Models\DtrPeriod;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollEntry;
use App\Models\PayrollPeriod;
use App\Models\PlatformNotification;
use App\Models\User;
use App\Services\DtrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Phase1DtrPayrollIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dtr_generation_uses_recorded_evidence_without_manufacturing_absence_rows(): void
    {
        $this->seed();

        $hr = User::query()->where('email', 'hr@talibon.demo')->firstOrFail();
        $employee = User::query()->where('email', 'employee@talibon.demo')->firstOrFail()->employee;
        $service = app(DtrService::class);
        $yesterday = now()->subDay()->startOfDay();

        $period = $service->generate($hr, [
            'label' => 'Evidence-only DTR test',
            'period_start' => $yesterday->copy()->subDay()->toDateString(),
            'period_end' => $yesterday->copy()->addDay()->toDateString(),
        ]);

        $rows = DtrDailySummary::query()
            ->where('dtr_period_id', $period->id)
            ->where('employee_id', $employee->id)
            ->get();

        $this->assertCount(1, $rows);
        $this->assertSame($yesterday->toDateString(), $rows->first()->work_date->toDateString());
        $this->assertSame('complete_pair', $rows->first()->source_status);
        $this->assertSame(2, $rows->first()->raw_event_count);
    }

    public function test_locked_dtr_can_link_context_to_payroll_without_changing_money(): void
    {
        $this->seed();

        $hr = User::query()->where('email', 'hr@talibon.demo')->firstOrFail();
        $service = app(DtrService::class);
        $payroll = PayrollPeriod::query()->latest('period_end')->firstOrFail();
        $entry = PayrollEntry::query()->where('payroll_period_id', $payroll->id)->firstOrFail();
        $grossBefore = (string) $entry->gross_pay;
        $netBefore = (string) $entry->net_pay;

        $dtr = $service->generate($hr, [
            'label' => 'Payroll context DTR',
            'period_start' => $payroll->period_start->toDateString(),
            'period_end' => $payroll->period_end->toDateString(),
        ]);

        try {
            $service->linkPayroll($hr, $payroll, $dtr);
            $this->fail('Unlocked DTR should not link to payroll.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('dtr_period', $exception->errors());
        }

        $service->lock($hr, $dtr);
        $service->linkPayroll($hr, $payroll->fresh(), $dtr->fresh());

        $entry->refresh();
        $payroll->refresh();
        $this->assertSame($grossBefore, (string) $entry->gross_pay);
        $this->assertSame($netBefore, (string) $entry->net_pay);
        $this->assertSame('linked_context_only', $entry->dtr_snapshot_status);
        $this->assertSame($dtr->id, $payroll->dtr_period_id);
        $this->assertSame('prototype_with_dtr_context', $payroll->calculation_mode);
        $this->assertStringContainsString('not recalculated', $payroll->source_notes);
    }

    public function test_leave_submission_and_approval_publish_notification_and_calendar_evidence(): void
    {
        $this->seed();

        $employeeUser = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
        $hr = User::query()->where('email', 'hr@talibon.demo')->firstOrFail();
        $vacation = LeaveType::query()->where('code', 'VL')->firstOrFail();
        $start = now()->addDays(12)->toDateString();
        $end = now()->addDays(13)->toDateString();

        $this->actingAs($employeeUser)->post('/hris/leave-requests', [
            'leave_type_id' => $vacation->id,
            'start_date' => $start,
            'end_date' => $end,
            'units' => 2,
            'reason' => 'Phase 1 notification and calendar integration test.',
        ])->assertRedirect();

        $leave = LeaveRequest::query()
            ->where('employee_id', $employeeUser->employee->id)
            ->where('start_date', $start)
            ->firstOrFail();

        $this->assertTrue(PlatformNotification::query()
            ->where('user_id', $hr->id)
            ->where('event_key', 'leave-submitted-'.$leave->id)
            ->exists());

        $this->actingAs($hr)->post('/hris/admin/leave-requests/'.$leave->id.'/approve')->assertRedirect();

        $this->assertTrue(PlatformNotification::query()
            ->where('user_id', $employeeUser->id)
            ->where('event_key', 'leave-approved-'.$leave->id)
            ->exists());
        $this->assertDatabaseHas('calendar_events', [
            'event_key' => 'leave-approved-'.$leave->id,
            'user_id' => $employeeUser->id,
            'source_domain' => 'leave',
            'status' => 'scheduled',
        ]);
    }

    public function test_non_hr_user_cannot_mutate_dtr_administration(): void
    {
        $this->seed();

        $engineering = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();

        $this->actingAs($engineering)->post('/hris/admin/dtr/generate', [
            'label' => 'Unauthorized DTR',
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
        ])->assertStatus(403);

        $this->assertSame(0, DtrPeriod::query()->where('label', 'Unauthorized DTR')->count());
    }
}
