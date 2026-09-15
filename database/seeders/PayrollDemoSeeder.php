<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\PayrollEntry;
use App\Models\PayrollPeriod;
use Illuminate\Database\Seeder;

class PayrollDemoSeeder extends Seeder
{
    public function run(): void
    {
        $period = PayrollPeriod::query()->updateOrCreate(
            ['label' => now()->format('F Y').' Payroll'],
            [
                'period_start' => now()->startOfMonth()->toDateString(),
                'period_end' => now()->endOfMonth()->toDateString(),
                'status' => 'released',
                'processed_at' => now()->subDays(4),
                'approved_at' => now()->subDays(2),
                'released_at' => now()->subDay(),
            ],
        );

        Employee::query()
            ->where('employment_status', 'active')
            ->orderBy('id')
            ->each(function (Employee $employee) use ($period): void {
                $basic = 18000 + (($employee->id % 18) * 1750);
                $allowances = 2000 + (($employee->id % 4) * 500);
                $gross = $basic + $allowances;

                // Synthetic prototype values only. These are not production government payroll formulas.
                $gsis = round($basic * 0.09, 2);
                $philhealth = round($basic * 0.025, 2);
                $pagibig = 200.00;
                $withholding = round(max(0, $gross - 25000) * 0.08, 2);
                $other = ($employee->id % 11 === 0) ? 500.00 : 0.00;
                $deductions = $gsis + $philhealth + $pagibig + $withholding + $other;
                $net = $gross - $deductions;

                PayrollEntry::query()->updateOrCreate(
                    [
                        'payroll_period_id' => $period->id,
                        'employee_id' => $employee->id,
                    ],
                    [
                        'basic_pay' => $basic,
                        'allowances' => $allowances,
                        'gross_pay' => $gross,
                        'gsis' => $gsis,
                        'philhealth' => $philhealth,
                        'pagibig' => $pagibig,
                        'withholding_tax' => $withholding,
                        'other_deductions' => $other,
                        'total_deductions' => $deductions,
                        'net_pay' => $net,
                        'status' => 'released',
                    ],
                );
            });
    }
}
