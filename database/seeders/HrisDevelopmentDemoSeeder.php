<?php

namespace Database\Seeders;

use App\Models\EmployeeDevelopmentRecord;
use App\Models\EmployeeHealthRecord;
use App\Models\PerformanceRecord;
use App\Models\User;
use Illuminate\Database\Seeder;

class HrisDevelopmentDemoSeeder extends Seeder
{
    public function run(): void
    {
        $employee = User::query()->where('email', 'employee@talibon.demo')->firstOrFail()->employee;
        $hr = User::query()->where('email', 'hr@talibon.demo')->firstOrFail();

        PerformanceRecord::query()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'period_start' => now()->subYear()->startOfYear()->toDateString(),
                'period_end' => now()->subYear()->endOfYear()->toDateString(),
            ],
            [
                'evaluator_user_id' => $hr->id,
                'rating' => 4.350,
                'rating_scale' => '5-point prototype scale',
                'status' => 'final',
                'summary' => 'Synthetic Phase 1 performance record for workflow demonstration only.',
                'reviewed_at' => now()->subMonths(2),
                'created_by_user_id' => $hr->id,
            ],
        );

        EmployeeDevelopmentRecord::query()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'record_type' => 'training',
                'title' => 'Records Management and Data Privacy Orientation',
            ],
            [
                'provider' => 'Synthetic Municipal Learning Activity',
                'reference_no' => 'DEMO-TRN-001',
                'attained_at' => now()->subMonths(5)->toDateString(),
                'expires_at' => null,
                'status' => 'active',
                'notes' => 'Synthetic demonstration record; not an official certificate.',
                'created_by_user_id' => $hr->id,
            ],
        );

        EmployeeDevelopmentRecord::query()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'record_type' => 'eligibility',
                'title' => 'Prototype Eligibility Record',
            ],
            [
                'provider' => 'Synthetic Issuer',
                'reference_no' => 'DEMO-ELG-001',
                'attained_at' => now()->subYears(2)->toDateString(),
                'expires_at' => now()->addDays(75)->toDateString(),
                'status' => 'active',
                'notes' => 'Synthetic demonstration record; requires replacement with verified HR evidence in production.',
                'created_by_user_id' => $hr->id,
            ],
        );

        EmployeeHealthRecord::query()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'record_type' => 'fit_to_work',
                'title' => 'Synthetic Fit-to-Work Evidence',
            ],
            [
                'issued_at' => now()->subMonths(3)->toDateString(),
                'valid_until' => now()->addMonths(9)->toDateString(),
                'status' => 'active',
                'summary' => 'Synthetic employment-health evidence for restricted-vault demonstration. Not a clinical diagnosis or RHU patient record.',
                'restriction_notes' => null,
                'created_by_user_id' => $hr->id,
            ],
        );
    }
}
