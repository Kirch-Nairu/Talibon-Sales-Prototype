<?php

namespace Database\Seeders;

use App\Domain\TravelOrders\TravelOrderStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\TravelOrder;
use App\Models\User;
use App\Models\WorkflowTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class F5BrowserQaSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('testing')) {
            throw new RuntimeException('F5 Browser QA fixtures are testing-only.');
        }

        DB::transaction(function (): void {
            $engineering = Department::query()->where('code', 'ENG')->firstOrFail();
            $engineeringUser = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
            $engineeringEmployee = Employee::query()
                ->where('user_id', $engineeringUser->id)
                ->where('department_id', $engineering->id)
                ->where('employment_status', 'active')
                ->firstOrFail();
            $mayorUser = User::query()->where('email', 'mayor@talibon.demo')->firstOrFail();

            $issuedToIds = Employee::query()
                ->where('department_id', $engineering->id)
                ->where('employment_status', 'active')
                ->orderBy('id')
                ->limit(2)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            if ($issuedToIds === []) {
                throw new RuntimeException('F5 Browser QA requires active Engineering employees.');
            }

            WorkflowTransaction::query()->updateOrCreate(
                ['reference_no' => 'TAL-F5-QA-REPORT-0001'],
                [
                    'transaction_type' => 'document_review',
                    'title' => 'Municipal drainage rehabilitation inspection findings and inter-office coordination follow-up',
                    'description' => 'Testing-only transaction used to prove F5 Records and Reports result presentation.',
                    'priority' => 'high',
                    'origin_department_id' => $engineering->id,
                    'current_department_id' => $engineering->id,
                    'created_by_user_id' => $engineeringUser->id,
                    'assigned_to_user_id' => $engineeringUser->id,
                    'assigned_employee_id' => $engineeringEmployee->id,
                    'status' => 'for_review',
                    'received_at' => CarbonImmutable::parse('2026-09-03T09:00:00+08:00'),
                    'due_at' => CarbonImmutable::parse('2026-09-10T17:00:00+08:00'),
                ],
            );

            $approved = TravelOrder::query()->updateOrCreate(
                ['public_id' => 'f5000000-0000-4000-8000-000000000001'],
                [
                    'reference_number' => 'TAL-TO-F5-0001',
                    'issuance_date' => '2026-09-03',
                    'purpose' => 'Municipal drainage site validation and barangay coordination',
                    'destination' => 'Barangay San Isidro, Talibon, Bohol',
                    'department_id' => $engineering->id,
                    'travel_start_date' => '2026-09-04',
                    'travel_end_date' => '2026-09-05',
                    'status' => TravelOrderStatus::Approved,
                    'recorded_by_user_id' => $mayorUser->id,
                    'status_updated_by_user_id' => null,
                ],
            );
            $approved->issuedTo()->sync($issuedToIds);

            $completed = TravelOrder::query()->updateOrCreate(
                ['public_id' => 'f5000000-0000-4000-8000-000000000002'],
                [
                    'reference_number' => 'TAL-TO-F5-0002',
                    'issuance_date' => '2026-09-02',
                    'purpose' => 'Bridge approach condition assessment and engineering field verification',
                    'destination' => 'Barangay San Pedro, Talibon, Bohol',
                    'department_id' => $engineering->id,
                    'travel_start_date' => '2026-09-02',
                    'travel_end_date' => '2026-09-02',
                    'status' => TravelOrderStatus::Completed,
                    'recorded_by_user_id' => $mayorUser->id,
                    'status_updated_by_user_id' => $mayorUser->id,
                ],
            );
            $completed->issuedTo()->sync([$issuedToIds[0]]);
        });
    }
}
