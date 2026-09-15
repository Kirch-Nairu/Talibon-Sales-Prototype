<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\OperationalItem;
use Illuminate\Database\Seeder;

class OperationsMonitoringDemoSeeder extends Seeder
{
    public function run(): void
    {
        $department = fn (string $code): Department => Department::query()->where('code', $code)->firstOrFail();
        $employee = fn (Department $office): ?int => Employee::query()
            ->where('department_id', $office->id)
            ->where('employment_status', 'active')
            ->orderByRaw('CASE WHEN user_id IS NOT NULL THEN 0 ELSE 1 END')
            ->orderBy('id')
            ->value('id');

        $engineering = $department('ENG');
        $planning = $department('MPDO');
        $bac = $department('BAC');
        $gso = $department('GSO');
        $budget = $department('BUDGET');
        $accounting = $department('ACCOUNTING');
        $mdrrmo = $department('MDRRMO');
        $menro = $department('MENRO');

        $items = [
            ['project', 'PRJ-2026-021', 'Priority Road Rehabilitation Program', $engineering, 'implementation', 'high', now()->addDays(18), 68, 4200000, 2875000, 'Civil works are progressing by priority road segment.'],
            ['project', 'PRJ-2026-028', 'Municipal Drainage Improvement Program', $engineering, 'for_review', 'urgent', now()->addDays(7), 42, 2100000, 865000, 'Cost review and work sequencing remain under inter-office review.'],
            ['project', 'PRJ-2026-033', 'Digital Records Modernization Pilot', $planning, 'implementation', 'normal', now()->addDays(30), 55, 850000, 392000, 'Pilot covers records routing, employee access, and management visibility.'],
            ['procurement', 'PRC-2026-044', 'ICT Equipment Procurement', $bac, 'evaluation', 'high', now()->addDays(5), 62, 1250000, null, 'Technical and financial evaluation in progress.'],
            ['procurement', 'PRC-2026-051', 'Emergency Response Supplies Procurement', $gso, 'award_preparation', 'urgent', now()->addDays(2), 80, 640000, null, 'Award documentation is being prepared.'],
            ['fund', 'FUND-2026-ROAD', 'Road Improvement Fund Utilization', $budget, 'active', 'high', now()->addDays(45), 64, 4200000, 2688000, 'Utilization reflects synthetic prototype financial monitoring data.'],
            ['fund', 'FUND-2026-DRRM', 'Local DRRM Fund Utilization', $mdrrmo, 'active', 'normal', now()->addDays(70), 47, 3000000, 1410000, 'Current utilization includes preparedness and equipment activities.'],
            ['compliance', 'COMP-2026-Q3-FIN', 'Quarterly Financial Reporting Requirement', $accounting, 'pending_submission', 'high', now()->addDays(9), 75, null, null, 'Supporting schedules are being consolidated.'],
            ['compliance', 'COMP-2026-ENV-02', 'Environmental Program Compliance Report', $menro, 'in_progress', 'normal', now()->addDays(14), 60, null, null, 'Program accomplishment evidence is under consolidation.'],
            ['compliance', 'COMP-2026-DRRM-03', 'Municipal Preparedness Documentation Review', $mdrrmo, 'due_soon', 'urgent', now()->addDay(), 88, null, null, 'Final review required before submission.'],
        ];

        foreach ($items as [$type, $reference, $title, $office, $status, $priority, $targetDate, $progress, $allocated, $utilized, $remarks]) {
            OperationalItem::query()->updateOrCreate(
                ['reference_no' => $reference],
                [
                    'item_type' => $type,
                    'title' => $title,
                    'department_id' => $office->id,
                    'responsible_employee_id' => $employee($office),
                    'status' => $status,
                    'priority' => $priority,
                    'target_date' => $targetDate->toDateString(),
                    'progress_percent' => $progress,
                    'allocated_amount' => $allocated,
                    'utilized_amount' => $utilized,
                    'remarks' => $remarks,
                ],
            );
        }
    }
}
