<?php

namespace Database\Seeders;

use App\Domain\Correspondence\CorrespondenceClassification;
use App\Domain\Correspondence\CorrespondenceLifecycleState;
use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkflowTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class F4BrowserQaCorrespondenceSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('testing')) {
            throw new RuntimeException('F4 Browser QA correspondence fixtures are testing-only.');
        }

        DB::transaction(function (): void {
            $engineering = Department::query()->where('code', 'ENG')->firstOrFail();
            $engineeringUser = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
            $engineeringEmployee = Employee::query()
                ->where('user_id', $engineeringUser->id)
                ->where('department_id', $engineering->id)
                ->where('employment_status', 'active')
                ->firstOrFail();

            $actionReceivedAt = CarbonImmutable::parse('2026-09-03T08:15:00+08:00');
            $informationReceivedAt = CarbonImmutable::parse('2026-09-02T14:30:00+08:00');

            CorrespondenceRecord::query()->updateOrCreate(
                ['public_id' => 'f4000000-0000-4000-8000-000000000001'],
                [
                    'external_reference_no' => 'BRGY-SAN-ISIDRO-2026-091',
                    'source' => 'official_email',
                    'channel' => 'email',
                    'sender_name' => 'Office of the Punong Barangay',
                    'sender_organization' => 'Barangay San Isidro',
                    'subject' => 'Drainage clearing coordination request',
                    'summary' => 'Request for municipal coordination on drainage clearing before the next scheduled inspection.',
                    'received_at' => $actionReceivedAt,
                    'receiving_department_id' => $engineering->id,
                    'registered_by_user_id' => $engineeringUser->id,
                    'registered_at' => $actionReceivedAt->addMinutes(10),
                    'municipal_reference_no' => 'TAL-CORR-F4-0001',
                    'classification' => CorrespondenceClassification::Internal,
                    'classified_at' => $actionReceivedAt->addMinutes(20),
                    'classified_by_user_id' => $engineeringUser->id,
                    'lifecycle_state' => CorrespondenceLifecycleState::Classified,
                    'workflow_transaction_id' => null,
                ],
            );

            $closedWorkflow = WorkflowTransaction::query()->updateOrCreate(
                ['reference_no' => 'TAL-F4-QA-ROUTE-0001'],
                [
                    'transaction_type' => 'document_review',
                    'title' => 'Watershed monitoring advisory acknowledgement',
                    'description' => 'Testing-only closed route used to prove F4 correspondence accountability presentation.',
                    'priority' => 'normal',
                    'origin_department_id' => $engineering->id,
                    'current_department_id' => $engineering->id,
                    'created_by_user_id' => $engineeringUser->id,
                    'assigned_to_user_id' => $engineeringUser->id,
                    'assigned_employee_id' => $engineeringEmployee->id,
                    'status' => 'closed',
                    'received_at' => $informationReceivedAt,
                    'due_at' => $informationReceivedAt->addDay(),
                    'completed_at' => $informationReceivedAt->addHours(4),
                    'closed_at' => $informationReceivedAt->addHours(4),
                ],
            );

            CorrespondenceRecord::query()->updateOrCreate(
                ['public_id' => 'f4000000-0000-4000-8000-000000000002'],
                [
                    'external_reference_no' => 'PENRO-BOHOL-2026-184',
                    'source' => 'official_email',
                    'channel' => 'email',
                    'sender_name' => 'Provincial Environment and Natural Resources Office',
                    'sender_organization' => 'Province of Bohol',
                    'subject' => 'Watershed monitoring advisory',
                    'summary' => 'Advisory furnished for Engineering Office information and record.',
                    'received_at' => $informationReceivedAt,
                    'receiving_department_id' => $engineering->id,
                    'registered_by_user_id' => $engineeringUser->id,
                    'registered_at' => $informationReceivedAt->addMinutes(10),
                    'municipal_reference_no' => 'TAL-CORR-F4-0002',
                    'classification' => CorrespondenceClassification::Public,
                    'classified_at' => $informationReceivedAt->addMinutes(20),
                    'classified_by_user_id' => $engineeringUser->id,
                    'routed_by_user_id' => $engineeringUser->id,
                    'routed_at' => $informationReceivedAt->addMinutes(30),
                    'action_started_by_user_id' => $engineeringUser->id,
                    'action_started_at' => $informationReceivedAt->addMinutes(45),
                    'lifecycle_state' => CorrespondenceLifecycleState::Archived,
                    'workflow_transaction_id' => $closedWorkflow->id,
                ],
            );
        });
    }
}
