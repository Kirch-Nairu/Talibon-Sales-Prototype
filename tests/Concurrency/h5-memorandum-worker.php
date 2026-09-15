<?php

declare(strict_types=1);

use App\Models\Memorandum;
use App\Models\User;
use App\Services\MemorandumService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

const H5_DATABASE = 'talibon_h5_concurrency';

$resultPath = '';

try {
    if (! app()->environment('testing')) {
        throw new RuntimeException('H5 worker requires APP_ENV=testing.');
    }

    if (DB::connection()->getDatabaseName() !== H5_DATABASE) {
        throw new RuntimeException('H5 worker requires the isolated H5 database.');
    }

    $memorandumId = filter_var($argv[1] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $actorId = filter_var($argv[2] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $workerNumber = filter_var($argv[3] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2]]);
    $statusPath = $argv[4] ?? '';
    $resultPath = $argv[5] ?? '';

    if (! $memorandumId || ! $actorId || ! $workerNumber || $statusPath === '' || $resultPath === '') {
        throw new InvalidArgumentException('Invalid H5 worker arguments.');
    }

    DB::selectOne("select set_config('application_name', ?, false)", ["talibon-h5-worker-{$workerNumber}"]);
    $backendPid = (int) DB::selectOne('select pg_backend_pid() as pid')->pid;

    $actor = User::query()->findOrFail($actorId);
    $memorandum = Memorandum::query()->findOrFail($memorandumId);

    writeJson($statusPath, [
        'worker' => $workerNumber,
        'started' => true,
        'backendPid' => $backendPid,
    ]);

    $newlyAcknowledged = app(MemorandumService::class)->acknowledge($actor, $memorandum);

    writeJson($resultPath, [
        'worker' => $workerNumber,
        'completed' => true,
        'newlyAcknowledged' => $newlyAcknowledged,
        'failure' => null,
    ]);

    exit(0);
} catch (Throwable $throwable) {
    if ($resultPath !== '') {
        writeJson($resultPath, [
            'completed' => false,
            'newlyAcknowledged' => null,
            'failure' => $throwable::class,
        ]);
    }

    fwrite(STDERR, "H5 worker failed.\n");
    exit(1);
}

function writeJson(string $path, array $payload): void
{
    $directory = dirname($path);
    if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
        throw new RuntimeException('Unable to create H5 worker evidence directory.');
    }

    file_put_contents(
        $path,
        json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        LOCK_EX,
    );
}
