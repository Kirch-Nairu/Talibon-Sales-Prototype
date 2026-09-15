<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\TransactionEvent;
use App\Models\User;
use App\Models\WorkflowTransaction;
use Illuminate\Database\Seeder;

class WorkflowDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (WorkflowTransaction::query()->exists()) {
            return;
        }

        $engineering = Department::query()->where('code', 'ENG')->firstOrFail();
        $budget = Department::query()->where('code', 'BUDGET')->firstOrFail();
        $accounting = Department::query()->where('code', 'ACCOUNTING')->firstOrFail();
        $mayor = Department::query()->where('code', 'MAYOR')->firstOrFail();
        $engineeringUser = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
        $budgetUser = User::query()->where('email', 'budget@talibon.demo')->firstOrFail();

        $assigneeFor = fn (int $departmentId): ?int => Employee::query()
            ->where('department_id', $departmentId)
            ->where('employment_status', 'active')
            ->orderByRaw('CASE WHEN user_id IS NOT NULL THEN 0 ELSE 1 END')
            ->orderBy('id')
            ->value('id');

        $samples = [
            [
                'title' => 'Road Rehabilitation Funding Request',
                'type' => 'funding_request',
                'priority' => 'high',
                'origin' => $engineering->id,
                'current' => $mayor->id,
                'status' => 'for_approval',
                'creator' => $engineeringUser->id,
                'received_at' => now()->subHours(5),
                'due_at' => now()->addDays(2)->endOfDay(),
            ],
            [
                'title' => 'Municipal Equipment Acquisition',
                'type' => 'project_endorsement',
                'priority' => 'normal',
                'origin' => $budget->id,
                'current' => $mayor->id,
                'status' => 'for_review',
                'creator' => $budgetUser->id,
                'received_at' => now()->subDay(),
                'due_at' => now()->addDay()->endOfDay(),
            ],
            [
                'title' => 'Drainage Improvement Cost Review',
                'type' => 'funding_request',
                'priority' => 'urgent',
                'origin' => $engineering->id,
                'current' => $budget->id,
                'status' => 'for_review',
                'creator' => $engineeringUser->id,
                'received_at' => now()->subDays(3),
                'due_at' => now()->subHours(6),
            ],
            [
                'title' => 'Engineering Program Financial Validation',
                'type' => 'document_review',
                'priority' => 'normal',
                'origin' => $engineering->id,
                'current' => $accounting->id,
                'status' => 'submitted',
                'creator' => $engineeringUser->id,
                'received_at' => now()->subHours(8),
                'due_at' => now()->addDays(4)->endOfDay(),
            ],
        ];

        foreach ($samples as $sample) {
            $transaction = WorkflowTransaction::query()->create([
                'transaction_type' => $sample['type'],
                'title' => $sample['title'],
                'description' => 'Synthetic prototype transaction used to demonstrate accountable inter-office municipal routing.',
                'priority' => $sample['priority'],
                'origin_department_id' => $sample['origin'],
                'current_department_id' => $sample['current'],
                'created_by_user_id' => $sample['creator'],
                'assigned_employee_id' => $assigneeFor($sample['current']),
                'status' => $sample['status'],
                'received_at' => $sample['received_at'],
                'due_at' => $sample['due_at'],
            ]);

            $transaction->update(['reference_no' => sprintf('TAL-2026-%06d', $transaction->id)]);

            TransactionEvent::query()->create([
                'transaction_id' => $transaction->id,
                'actor_user_id' => $sample['creator'],
                'from_department_id' => $sample['origin'],
                'to_department_id' => $sample['current'],
                'action' => 'submitted',
                'previous_status' => 'draft',
                'new_status' => $sample['status'],
                'remarks' => 'Seeded prototype routing event with responsibility and deadline metadata.',
                'created_at' => $sample['received_at'],
            ]);
        }
    }
}
