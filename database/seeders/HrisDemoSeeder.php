<?php

namespace Database\Seeders;

use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\LeaveCreditAccount;
use App\Models\LeaveCreditTransaction;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Seeder;

class HrisDemoSeeder extends Seeder
{
    public function run(): void
    {
        $types = collect([
            ['code' => 'VL', 'name' => 'Vacation Leave', 'tracks_balance' => true, 'entitlement_label' => null],
            ['code' => 'SL', 'name' => 'Sick Leave', 'tracks_balance' => true, 'entitlement_label' => null],
            ['code' => 'SPL', 'name' => 'Special Privilege Leave', 'tracks_balance' => true, 'entitlement_label' => null],
            ['code' => 'ML', 'name' => 'Maternity Leave', 'tracks_balance' => false, 'entitlement_label' => 'Policy-based entitlement'],
            ['code' => 'PL', 'name' => 'Paternity Leave', 'tracks_balance' => false, 'entitlement_label' => 'Policy-based entitlement'],
        ])->mapWithKeys(function (array $data): array {
            $type = LeaveType::query()->updateOrCreate(['code' => $data['code']], $data + ['is_active' => true]);
            return [$data['code'] => $type];
        });

        $sampleWorkday = now()->copy()->subDay()->startOfDay();

        Employee::query()->each(function (Employee $employee) use ($types, $sampleWorkday): void {
            foreach ([['VL', 18.500], ['SL', 27.250], ['SPL', 2.000]] as [$code, $balance]) {
                $account = LeaveCreditAccount::query()->firstOrCreate([
                    'employee_id' => $employee->id,
                    'leave_type_id' => $types[$code]->id,
                ], ['balance' => $balance]);

                if (! $account->transactions()->exists()) {
                    LeaveCreditTransaction::query()->create([
                        'leave_credit_account_id' => $account->id,
                        'amount' => $balance,
                        'entry_type' => 'prototype_opening_balance',
                        'notes' => 'Synthetic prototype opening balance; not an official HR record.',
                        'created_at' => now()->subMonth(),
                    ]);
                }
            }

            if (! $employee->attendanceLogs()->exists()) {
                AttendanceLog::query()->create([
                    'employee_id' => $employee->id,
                    'occurred_at' => $sampleWorkday->copy()->addHours(7)->addMinutes(56),
                    'event_type' => 'in',
                    'source' => 'Prototype Biometric 01',
                    'created_at' => now(),
                ]);
                AttendanceLog::query()->create([
                    'employee_id' => $employee->id,
                    'occurred_at' => $sampleWorkday->copy()->addHours(17)->addMinutes(8),
                    'event_type' => 'out',
                    'source' => 'Prototype Biometric 01',
                    'created_at' => now(),
                ]);
            }
        });

        if (! LeaveRequest::query()->exists()) {
            $employee = User::query()->where('email', 'employee@talibon.demo')->firstOrFail()->employee;
            LeaveRequest::query()->create([
                'employee_id' => $employee->id,
                'leave_type_id' => $types['VL']->id,
                'start_date' => now()->addDays(6)->toDateString(),
                'end_date' => now()->addDays(7)->toDateString(),
                'units' => 2,
                'reason' => 'Synthetic pending request for prototype demonstration.',
                'status' => 'pending',
            ]);
        }
    }
}
