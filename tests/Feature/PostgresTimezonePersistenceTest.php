<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkflowTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostgresTimezonePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_postgres_session_timezone_preserves_transaction_deadline_instant(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-25 08:00:00', 'Asia/Manila'));

        try {
            $sessionTimezone = DB::selectOne("select current_setting('TIMEZONE') as timezone");
            $this->assertSame(config('app.timezone'), $sessionTimezone->timezone);

            $department = Department::query()->create([
                'code' => 'TZ-'.Str::upper(Str::random(8)),
                'name' => 'Timezone Regression Office',
                'short_name' => 'TZ',
                'branch' => 'executive',
                'office_type' => 'department',
                'sort_order' => 10,
                'is_routable' => true,
                'is_active' => true,
            ]);
            $user = User::query()->create([
                'name' => 'Timezone Regression User',
                'email' => Str::lower(Str::random(10)).'@example.test',
                'password' => 'password',
                'role' => 'department_head',
                'is_active' => true,
            ]);
            Employee::query()->create([
                'employee_number' => 'TZ-EMP-'.Str::upper(Str::random(10)),
                'full_name' => $user->name,
                'work_email' => $user->email,
                'user_id' => $user->id,
                'department_id' => $department->id,
                'position_title' => 'Timezone Regression Officer',
                'employment_status' => 'active',
            ]);

            $deadline = now()->subHour();
            $transaction = WorkflowTransaction::query()->create([
                'reference_no' => 'TZ-TX-'.Str::upper(Str::random(10)),
                'transaction_type' => 'internal_request',
                'title' => 'Timezone persistence regression',
                'priority' => 'normal',
                'origin_department_id' => $department->id,
                'current_department_id' => $department->id,
                'created_by_user_id' => $user->id,
                'status' => 'submitted',
                'received_at' => now()->subHours(2),
                'due_at' => $deadline,
            ])->fresh();

            $this->assertTrue(
                $transaction->due_at->equalTo($deadline),
                sprintf(
                    'Expected persisted deadline %s, got %s.',
                    $deadline->toIso8601String(),
                    $transaction->due_at->toIso8601String(),
                ),
            );
            $this->assertTrue($transaction->due_at->lt(now()));
        } finally {
            Carbon::setTestNow();
        }
    }
}
