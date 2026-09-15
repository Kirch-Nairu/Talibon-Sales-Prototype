<?php

declare(strict_types=1);

use App\Domain\Correspondence\CorrespondenceLifecycleState;
use App\Models\AuditLog;
use App\Models\CorrespondenceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Memorandum;
use App\Models\MemoRecipient;
use App\Models\TransactionEvent;
use App\Models\TravelOrder;
use App\Models\TravelOrderEvent;
use App\Models\User;
use App\Models\WorkflowTransaction;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

const H1_DATABASE = 'talibon_h1_mutations';
const H1_CORRESPONDENCE_ID = '11000000-0000-4000-8000-000000000001';

if (! app()->environment('testing')) {
    fwrite(STDERR, "H1 mutation probe is testing-only.\n");
    exit(2);
}

$database = DB::connection()->getDatabaseName();
if ($database !== H1_DATABASE) {
    fwrite(STDERR, "H1 mutation probe requires the isolated H1 database.\n");
    exit(2);
}

$command = $argv[1] ?? '';
$args = array_slice($argv, 2);

$output = match ($command) {
    'isolation' => isolationSnapshot($database),
    'setup' => setupFixtures($args[0] ?? ''),
    'totals' => totalsSnapshot(),
    'transaction' => transactionSnapshot($args[0] ?? ''),
    'correspondence' => correspondenceSnapshot($args[0] ?? H1_CORRESPONDENCE_ID),
    'memorandum' => memorandumSnapshot($args[0] ?? '', $args[1] ?? ''),
    'travel' => travelSnapshot($args[0] ?? ''),
    default => null,
};

if ($output === null) {
    fwrite(STDERR, "Unknown H1 mutation probe command.\n");
    exit(2);
}

echo json_encode($output, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES).PHP_EOL;

function isolationSnapshot(string $database): array
{
    return [
        'environment' => app()->environment(),
        'database' => $database,
        'isolated' => app()->environment('testing') && $database === H1_DATABASE,
    ];
}

function setupFixtures(string $rawMarker): array
{
    $marker = strtolower((string) preg_replace('/[^a-f0-9]/i', '', $rawMarker));
    if ($marker === '') {
        throw new RuntimeException('H3 fixture marker is required.');
    }
    $marker = substr($marker, 0, 10);

    return DB::transaction(function () use ($marker): array {
        $existing = CorrespondenceRecord::query()->where('public_id', H1_CORRESPONDENCE_ID)->first();
        if ($existing) {
            $workflowId = $existing->workflow_transaction_id;
            $existing->events()->delete();
            $existing->delete();

            if ($workflowId) {
                TransactionEvent::query()->where('transaction_id', $workflowId)->delete();
                WorkflowTransaction::query()->whereKey($workflowId)->delete();
            }
        }

        CorrespondenceRecord::query()->create([
            'public_id' => H1_CORRESPONDENCE_ID,
            'external_reference_no' => 'H1-BROWSER-INPUT-001',
            'source' => 'official_email',
            'channel' => 'email',
            'sender_name' => 'Synthetic H1 QA Sender',
            'sender_organization' => 'Synthetic QA Fixture',
            'subject' => 'H1 mutation correspondence acceptance',
            'summary' => 'Testing-only correspondence used for isolated browser mutation acceptance.',
            'received_at' => now(),
            'lifecycle_state' => CorrespondenceLifecycleState::Received,
            'workflow_transaction_id' => null,
            'receiving_department_id' => null,
        ]);

        $engineering = Department::query()->where('code', 'ENG')->firstOrFail();
        $budget = Department::query()->where('code', 'BUDGET')->firstOrFail();
        $mayorDepartment = Department::query()->where('code', 'MAYOR')->firstOrFail();
        $engineeringUser = User::query()->where('email', 'engineering@talibon.demo')->firstOrFail();
        $mayorUser = User::query()->where('email', 'mayor@talibon.demo')->firstOrFail();
        $budgetEmployee = Employee::query()
            ->where('department_id', $budget->id)
            ->where('employment_status', 'active')
            ->orderBy('id')
            ->firstOrFail();
        $engineeringEmployee = Employee::query()
            ->where('department_id', $engineering->id)
            ->where('employment_status', 'active')
            ->orderBy('id')
            ->firstOrFail();

        $fixtureTitles = [
            "H3 assign transaction {$marker}",
            "H3 forward transaction {$marker}",
            "H3 return transaction {$marker}",
            "H3 mayor disapprove transaction {$marker}",
        ];

        $existingTransactionIds = WorkflowTransaction::query()->whereIn('title', $fixtureTitles)->pluck('id');
        if ($existingTransactionIds->isNotEmpty()) {
            TransactionEvent::query()->whereIn('transaction_id', $existingTransactionIds)->delete();
            WorkflowTransaction::query()->whereIn('id', $existingTransactionIds)->delete();
        }

        $createTransactionFixture = function (
            string $title,
            string $suffix,
            int $currentDepartmentId,
            string $status,
            ?int $assignedEmployeeId,
            int $minutesAgo,
        ) use ($engineering, $engineeringUser, $marker): WorkflowTransaction {
            $receivedAt = now()->subMinutes($minutesAgo);
            $transaction = WorkflowTransaction::query()->create([
                'reference_no' => sprintf('H3-%s-%s', strtoupper($marker), $suffix),
                'transaction_type' => 'document_review',
                'title' => $title,
                'description' => 'Synthetic H3 browser acceptance fixture.',
                'priority' => 'normal',
                'origin_department_id' => $engineering->id,
                'current_department_id' => $currentDepartmentId,
                'created_by_user_id' => $engineeringUser->id,
                'assigned_employee_id' => $assignedEmployeeId,
                'status' => $status,
                'received_at' => $receivedAt,
                'due_at' => now()->addDays(2)->endOfDay(),
            ]);

            TransactionEvent::query()->create([
                'transaction_id' => $transaction->id,
                'actor_user_id' => $engineeringUser->id,
                'from_department_id' => $engineering->id,
                'to_department_id' => $currentDepartmentId,
                'action' => 'submitted',
                'previous_status' => 'draft',
                'new_status' => $status,
                'remarks' => 'Synthetic H3 fixture seed event.',
                'created_at' => $receivedAt,
            ]);

            return $transaction;
        };

        $createTransactionFixture("H3 assign transaction {$marker}", 'ASSIGN', (int) $budget->id, 'for_review', null, 75);
        $createTransactionFixture("H3 forward transaction {$marker}", 'FORWARD', (int) $budget->id, 'for_review', (int) $budgetEmployee->id, 80);
        $createTransactionFixture("H3 return transaction {$marker}", 'RETURN', (int) $budget->id, 'for_review', (int) $budgetEmployee->id, 85);
        $createTransactionFixture("H3 mayor disapprove transaction {$marker}", 'DISAPPROVE', (int) $mayorDepartment->id, 'for_approval', null, 90);

        $cancelReference = 'H3-TO-CANCEL-'.strtoupper($marker);
        $existingTravel = TravelOrder::query()->where('reference_number', $cancelReference)->first();
        if ($existingTravel) {
            $existingTravel->events()->delete();
            $existingTravel->issuedTo()->detach();
            $existingTravel->delete();
        }

        $travelOrder = TravelOrder::query()->create([
            'reference_number' => $cancelReference,
            'issuance_date' => now()->toDateString(),
            'purpose' => 'Synthetic H3 cancellation acceptance fixture',
            'destination' => 'Synthetic QA destination',
            'department_id' => $engineering->id,
            'travel_start_date' => now()->addDay()->toDateString(),
            'travel_end_date' => now()->addDays(2)->toDateString(),
            'status' => 'approved',
            'recorded_by_user_id' => $mayorUser->id,
        ]);
        $travelOrder->issuedTo()->sync([(int) $engineeringEmployee->id]);
        $occurredAt = now()->subMinutes(30);
        TravelOrderEvent::query()->create([
            'travel_order_id' => $travelOrder->id,
            'actor_user_id' => $mayorUser->id,
            'event' => 'recorded_approved',
            'from_status' => null,
            'to_status' => 'approved',
            'remarks' => 'Synthetic H3 approved fixture.',
            'occurred_at' => $occurredAt,
            'created_at' => $occurredAt,
        ]);

        return [
            'ok' => true,
            'correspondencePublicId' => H1_CORRESPONDENCE_ID,
            'h3Marker' => $marker,
            'database' => DB::connection()->getDatabaseName(),
        ];
    });
}

function totalsSnapshot(): array
{
    return [
        'transactions' => WorkflowTransaction::query()->count(),
        'correspondence' => CorrespondenceRecord::query()->count(),
        'memoranda' => Memorandum::query()->count(),
        'travelOrders' => TravelOrder::query()->count(),
    ];
}

function transactionSnapshot(string $title): array
{
    $query = WorkflowTransaction::query()->where('title', $title);
    $count = (clone $query)->count();
    $transaction = $query->with(['originDepartment:id,code', 'currentDepartment:id,code'])->orderBy('id')->first();
    $latestEvent = $transaction
        ? TransactionEvent::query()
            ->where('transaction_id', $transaction->id)
            ->with(['fromDepartment:id,code', 'toDepartment:id,code'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first()
        : null;

    return [
        'count' => $count,
        'id' => $transaction?->id,
        'reference' => $transaction?->reference_no,
        'status' => $transaction?->status,
        'originDepartmentCode' => $transaction?->originDepartment?->code,
        'currentDepartmentCode' => $transaction?->currentDepartment?->code,
        'assignedEmployeeId' => $transaction?->assigned_employee_id ? (int) $transaction->assigned_employee_id : null,
        'receivedAt' => $transaction?->received_at?->toIso8601String(),
        'completedAt' => $transaction?->completed_at?->toIso8601String(),
        'eventCount' => $transaction?->events()->count() ?? 0,
        'latestEvent' => $latestEvent ? [
            'action' => $latestEvent->action,
            'previousStatus' => $latestEvent->previous_status,
            'newStatus' => $latestEvent->new_status,
            'fromDepartmentCode' => $latestEvent->fromDepartment?->code,
            'toDepartmentCode' => $latestEvent->toDepartment?->code,
        ] : null,
    ];
}

function correspondenceSnapshot(string $publicId): array
{
    $query = CorrespondenceRecord::query()->where('public_id', $publicId);
    $count = (clone $query)->count();
    $record = $query->with('workflowTransaction.currentDepartment:id,code')->first();
    $workflow = $record?->workflowTransaction;

    return [
        'count' => $count,
        'lifecycle' => $record?->lifecycle_state?->value,
        'classification' => $record?->classification?->value,
        'municipalReference' => $record?->municipal_reference_no,
        'actionStartedAt' => $record?->action_started_at?->toIso8601String(),
        'eventCount' => $record?->events()->count() ?? 0,
        'workflowId' => $workflow?->id,
        'workflowReference' => $workflow?->reference_no,
        'workflowStatus' => $workflow?->status,
        'workflowDepartmentCode' => $workflow?->currentDepartment?->code,
        'workflowEventCount' => $workflow?->events()->count() ?? 0,
    ];
}

function memorandumSnapshot(string $memoNumber, string $recipientEmail): array
{
    $query = Memorandum::query()->where('memo_number', $memoNumber);
    $count = (clone $query)->count();
    $memorandum = $query->orderBy('id')->first();
    $recipient = null;

    if ($memorandum && $recipientEmail !== '') {
        $recipient = MemoRecipient::query()
            ->where('memorandum_id', $memorandum->id)
            ->whereHas('user', fn ($user) => $user->where('email', $recipientEmail))
            ->first();
    }

    return [
        'count' => $count,
        'id' => $memorandum?->id,
        'recipientCount' => $memorandum?->recipients()->count() ?? 0,
        'acknowledgedCount' => $memorandum?->recipients()->whereNotNull('acknowledged_at')->count() ?? 0,
        'targetAcknowledged' => $recipient?->acknowledged_at !== null,
        'ackAuditCount' => $memorandum
            ? AuditLog::query()
                ->where('action', 'memorandum.acknowledged')
                ->where('entity_type', Memorandum::class)
                ->where('entity_id', $memorandum->id)
                ->count()
            : 0,
    ];
}

function travelSnapshot(string $reference): array
{
    $query = TravelOrder::query()->where('reference_number', $reference);
    $count = (clone $query)->count();
    $travelOrder = $query->orderBy('id')->first();
    $latestEvent = $travelOrder?->events()->orderByDesc('created_at')->orderByDesc('id')->first();

    return [
        'count' => $count,
        'publicId' => $travelOrder?->public_id,
        'status' => $travelOrder?->status?->value,
        'eventCount' => $travelOrder?->events()->count() ?? 0,
        'issuedToCount' => $travelOrder?->issuedTo()->count() ?? 0,
        'latestEvent' => $latestEvent ? [
            'event' => $latestEvent->event,
            'fromStatus' => $latestEvent->from_status?->value,
            'toStatus' => $latestEvent->to_status?->value,
        ] : null,
    ];
}
