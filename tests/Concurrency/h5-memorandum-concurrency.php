<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Memorandum;
use App\Models\MemoRecipient;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

const H5_DATABASE = 'talibon_h5_concurrency';
const H5_WORKER_COUNT = 2;
const H5_REPORT = 'storage/app/qa/h5-memorandum-concurrency-report.json';

$root = dirname(__DIR__, 2);
$reportPath = $root.'/'.H5_REPORT;
$expectedSha = (string) getenv('QA_EXPECTED_SHA');
$environment = (string) (getenv('QA_ENVIRONMENT') ?: 'local-isolated-h5');
$actualSha = actualGitSha($root);
$database = DB::connection()->getDatabaseName();
$report = [
    'schemaVersion' => 1,
    'generatedAt' => now()->toIso8601String(),
    'environment' => $environment,
    'expectedSha' => $expectedSha,
    'actualSha' => $actualSha,
    'exactHead' => $expectedSha !== '' && hash_equals($expectedSha, $actualSha),
    'database' => $database,
    'workerCount' => H5_WORKER_COUNT,
    'workersCompleted' => 0,
    'contentionObserved' => false,
    'newlyAcknowledgedTrueCount' => 0,
    'newlyAcknowledgedFalseCount' => 0,
    'recipientCount' => 0,
    'targetAcknowledged' => false,
    'targetViewed' => false,
    'ackAuditCount' => 0,
    'workerFailures' => 0,
    'result' => 'FAIL',
    'failure' => null,
];

$processes = [];
$tempFiles = [];
$lockHeld = false;
$stage = 'guards';
$exitCode = 1;

try {
    if (! app()->environment('testing')) {
        throw new RuntimeException('H5 concurrency harness is testing-only.');
    }
    if ($database !== H5_DATABASE) {
        throw new RuntimeException('H5 concurrency harness requires the isolated H5 database.');
    }
    if (! $report['exactHead']) {
        throw new RuntimeException('H5 concurrency harness exact checkout mismatch.');
    }

    $stage = 'fixture';
    [$memorandum, $actor, $recipient] = createFixture($expectedSha);

    $stage = 'orchestrator-lock';
    DB::beginTransaction();
    $lockHeld = true;
    MemoRecipient::query()->whereKey($recipient->id)->lockForUpdate()->firstOrFail();

    $stage = 'workers-start';
    for ($worker = 1; $worker <= H5_WORKER_COUNT; $worker++) {
        $statusPath = storage_path("app/qa/.h5-worker-{$worker}-status.json");
        $resultPath = storage_path("app/qa/.h5-worker-{$worker}-result.json");
        @unlink($statusPath);
        @unlink($resultPath);
        $tempFiles[] = $statusPath;
        $tempFiles[] = $resultPath;
        $processes[$worker] = startWorker(
            $root,
            (int) $memorandum->id,
            (int) $actor->id,
            $worker,
            $statusPath,
            $resultPath,
        );
    }

    $stage = 'workers-contending';
    $statuses = waitForWorkerStatuses($processes, 10.0);
    $backendPids = array_values(array_map(fn (array $status): int => (int) $status['backendPid'], $statuses));
    waitForLockContention($backendPids, 10.0);
    $report['contentionObserved'] = true;

    $stage = 'release-lock';
    DB::commit();
    $lockHeld = false;

    $stage = 'workers-complete';
    $workerResults = [];
    foreach ($processes as $worker => $process) {
        $workerResults[$worker] = waitForWorker($process, 20.0);
    }

    $semanticResults = array_column($workerResults, 'result');
    $report['workersCompleted'] = count(array_filter(
        $semanticResults,
        fn (?array $result): bool => ($result['completed'] ?? false) === true,
    ));
    $report['newlyAcknowledgedTrueCount'] = count(array_filter(
        $semanticResults,
        fn (?array $result): bool => ($result['newlyAcknowledged'] ?? null) === true,
    ));
    $report['newlyAcknowledgedFalseCount'] = count(array_filter(
        $semanticResults,
        fn (?array $result): bool => ($result['newlyAcknowledged'] ?? null) === false,
    ));
    $report['workerFailures'] = count(array_filter(
        $workerResults,
        fn (array $workerResult): bool => $workerResult['exitCode'] !== 0
            || ($workerResult['result']['completed'] ?? false) !== true,
    ));

    $stage = 'database-truth';
    $target = MemoRecipient::query()
        ->where('memorandum_id', $memorandum->id)
        ->where('user_id', $actor->id)
        ->firstOrFail();
    $report['recipientCount'] = MemoRecipient::query()->where('memorandum_id', $memorandum->id)->count();
    $report['targetAcknowledged'] = $target->acknowledged_at !== null;
    $report['targetViewed'] = $target->viewed_at !== null;
    $report['ackAuditCount'] = AuditLog::query()
        ->where('actor_user_id', $actor->id)
        ->where('action', 'memorandum.acknowledged')
        ->where('entity_type', Memorandum::class)
        ->where('entity_id', $memorandum->id)
        ->count();

    $passed = $report['workerCount'] === 2
        && $report['workersCompleted'] === 2
        && $report['contentionObserved'] === true
        && $report['newlyAcknowledgedTrueCount'] === 1
        && $report['newlyAcknowledgedFalseCount'] === 1
        && $report['recipientCount'] === 1
        && $report['targetAcknowledged'] === true
        && $report['targetViewed'] === true
        && $report['ackAuditCount'] === 1
        && $report['workerFailures'] === 0;

    if (! $passed) {
        throw new RuntimeException('H5 concurrency invariants did not converge.');
    }

    $report['result'] = 'PASS';
    $exitCode = 0;
} catch (Throwable $throwable) {
    if ($lockHeld && DB::transactionLevel() > 0) {
        DB::rollBack();
        $lockHeld = false;
    }

    foreach ($processes as $process) {
        terminateWorker($process);
    }

    $report['failure'] = [
        'stage' => $stage,
        'exception' => $throwable::class,
    ];
} finally {
    writeReport($reportPath, $report);
    foreach ($tempFiles as $tempFile) {
        @unlink($tempFile);
    }
}

if ($report['result'] === 'PASS') {
    echo "H5_MEMORANDUM_CONCURRENCY_PASS sha={$actualSha}\n";
} else {
    fwrite(STDERR, "H5_MEMORANDUM_CONCURRENCY_FAIL stage={$stage}\n");
}

exit($exitCode);

function actualGitSha(string $root): string
{
    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open(['git', 'rev-parse', 'HEAD'], $descriptorSpec, $pipes, $root);
    if (! is_resource($process)) {
        return '';
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($process);

    return $exit === 0 && $stderr === '' ? trim($stdout) : '';
}

function createFixture(string $expectedSha): array
{
    $marker = strtoupper(substr(preg_replace('/[^a-f0-9]/i', '', $expectedSha), 0, 10));
    if ($marker === '') {
        throw new RuntimeException('H5 fixture requires an exact SHA marker.');
    }

    $issuer = User::query()->where('email', 'mayor@talibon.demo')->firstOrFail();
    $actor = User::query()->where('email', 'employee@talibon.demo')->firstOrFail();
    $issuer->loadMissing('employee');
    $memoNumber = "H5-CONCURRENCY-{$marker}";

    $existing = Memorandum::query()->where('memo_number', $memoNumber)->first();
    if ($existing) {
        AuditLog::query()
            ->where('entity_type', Memorandum::class)
            ->where('entity_id', $existing->id)
            ->delete();
        $existing->delete();
    }

    $memorandum = Memorandum::query()->create([
        'memo_number' => $memoNumber,
        'title' => 'Synthetic H5 concurrency acknowledgement',
        'body' => 'Synthetic testing-only memorandum for H5 concurrency acceptance.',
        'issued_by_user_id' => $issuer->id,
        'issued_by_department_id' => $issuer->employee->department_id,
        'audience_type' => 'employees',
        'requires_acknowledgement' => true,
        'classification' => 'internal',
        'status' => 'published',
        'published_at' => now(),
        'expires_at' => null,
    ]);

    $recipient = MemoRecipient::query()->create([
        'memorandum_id' => $memorandum->id,
        'user_id' => $actor->id,
        'delivered_at' => now(),
        'viewed_at' => null,
        'acknowledged_at' => null,
    ]);

    return [$memorandum, $actor, $recipient];
}

function startWorker(
    string $root,
    int $memorandumId,
    int $actorId,
    int $worker,
    string $statusPath,
    string $resultPath,
): array {
    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open([
        PHP_BINARY,
        $root.'/tests/Concurrency/h5-memorandum-worker.php',
        (string) $memorandumId,
        (string) $actorId,
        (string) $worker,
        $statusPath,
        $resultPath,
    ], $descriptorSpec, $pipes, $root);

    if (! is_resource($process)) {
        throw new RuntimeException('Unable to launch H5 worker process.');
    }

    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    return [
        'process' => $process,
        'pipes' => $pipes,
        'statusPath' => $statusPath,
        'resultPath' => $resultPath,
    ];
}

function waitForWorkerStatuses(array $processes, float $timeoutSeconds): array
{
    $deadline = microtime(true) + $timeoutSeconds;

    do {
        $statuses = [];
        foreach ($processes as $worker => $process) {
            if (is_file($process['statusPath'])) {
                $decoded = json_decode((string) file_get_contents($process['statusPath']), true, 512, JSON_THROW_ON_ERROR);
                if (($decoded['started'] ?? false) === true && isset($decoded['backendPid'])) {
                    $statuses[$worker] = $decoded;
                }
            }
        }

        if (count($statuses) === H5_WORKER_COUNT) {
            return $statuses;
        }

        usleep(50_000);
    } while (microtime(true) < $deadline);

    throw new RuntimeException('H5 workers did not reach the production service boundary.');
}

function waitForLockContention(array $backendPids, float $timeoutSeconds): void
{
    if (count($backendPids) !== H5_WORKER_COUNT) {
        throw new RuntimeException('H5 requires exactly two worker database sessions.');
    }

    $deadline = microtime(true) + $timeoutSeconds;
    do {
        $row = DB::selectOne(
            "select count(*)::int as waiting from pg_stat_activity where pid in (?, ?) and wait_event_type = 'Lock'",
            [$backendPids[0], $backendPids[1]],
        );

        if ((int) $row->waiting === H5_WORKER_COUNT) {
            return;
        }

        usleep(50_000);
    } while (microtime(true) < $deadline);

    throw new RuntimeException('Both H5 workers did not enter database lock contention.');
}

function waitForWorker(array $worker, float $timeoutSeconds): array
{
    $deadline = microtime(true) + $timeoutSeconds;
    $lastStatus = proc_get_status($worker['process']);

    while ($lastStatus['running'] && microtime(true) < $deadline) {
        usleep(50_000);
        $lastStatus = proc_get_status($worker['process']);
    }

    if ($lastStatus['running']) {
        proc_terminate($worker['process']);
        throw new RuntimeException('H5 worker completion timed out.');
    }

    $stdout = stream_get_contents($worker['pipes'][1]);
    $stderr = stream_get_contents($worker['pipes'][2]);
    fclose($worker['pipes'][1]);
    fclose($worker['pipes'][2]);
    $closeCode = proc_close($worker['process']);
    $exitCode = $lastStatus['exitcode'] >= 0 ? $lastStatus['exitcode'] : $closeCode;
    $result = is_file($worker['resultPath'])
        ? json_decode((string) file_get_contents($worker['resultPath']), true, 512, JSON_THROW_ON_ERROR)
        : null;

    return [
        'exitCode' => $exitCode,
        'result' => $result,
        'stdoutPresent' => trim($stdout) !== '',
        'stderrPresent' => trim($stderr) !== '',
    ];
}

function terminateWorker(array $worker): void
{
    if (! isset($worker['process']) || ! is_resource($worker['process'])) {
        return;
    }

    $status = proc_get_status($worker['process']);
    if ($status['running']) {
        proc_terminate($worker['process']);
    }

    foreach ($worker['pipes'] ?? [] as $pipe) {
        if (is_resource($pipe)) {
            fclose($pipe);
        }
    }
    proc_close($worker['process']);
}

function writeReport(string $path, array $report): void
{
    $directory = dirname($path);
    if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
        throw new RuntimeException('Unable to create H5 evidence directory.');
    }

    file_put_contents(
        $path,
        json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES).PHP_EOL,
        LOCK_EX,
    );
}
