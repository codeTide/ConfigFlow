<?php

declare(strict_types=1);

use ConfigFlow\Bot\WorkerApiApp;
use ConfigFlow\Bot\WorkerApiStore;
use ConfigFlow\Bot\XuiJobState;

require __DIR__ . '/../src/WorkerApiStore.php';
require __DIR__ . '/../src/XuiJobState.php';
require __DIR__ . '/../src/WorkerApiApp.php';

final class FakeStore implements WorkerApiStore
{
    public bool $enabled = true;
    public string $apiKey = 'k';

    public function isWorkerApiEnabled(): bool { return $this->enabled; }
    public function workerApiKey(): string { return $this->apiKey; }
    public function listPendingXuiJobs(int $limit = 20): array { return [['id' => 1]]; }
    public function markXuiJobProcessing(int $jobId): array {
        return match ($jobId) {
            404 => ['ok' => false, 'error' => 'not_found'],
            409 => ['ok' => false, 'error' => 'not_actionable', 'status' => 'done'],
            default => ['ok' => true],
        };
    }
    public function markXuiJobDone(int $jobId, string $resultConfig, string $resultLink): array {
        return $jobId === 404 ? ['ok' => false, 'error' => 'not_found'] : ['ok' => true];
    }
    public function markXuiJobError(int $jobId, string $errorMsg): array {
        return $jobId === 404 ? ['ok' => false, 'error' => 'not_found'] : ['ok' => true, 'retry_count' => 2];
    }
    public function getXuiJob(int $jobId): ?array {
        return $jobId === 404 ? null : ['id' => $jobId, 'status' => 'done'];
    }
}

function assertSame(mixed $actual, mixed $expected, string $message): void {
    if ($actual !== $expected) {
        fwrite(STDERR, "Assertion failed: {$message}\nActual: " . var_export($actual, true) . "\nExpected: " . var_export($expected, true) . "\n");
        exit(1);
    }
}

$store = new FakeStore();
$app = new WorkerApiApp($store);

$r = $app->handle('GET', '/health');
assertSame($r['status'], 200, 'health status');
assertSame($r['body']['ok'], true, 'health ok');

$r = $app->handle('GET', '/jobs/pending', ['X-API-KEY' => 'bad']);
assertSame($r['status'], 401, 'unauthorized status');
assertSame($r['body']['error']['code'], 'unauthorized', 'unauthorized code');

$r = $app->handle('GET', '/jobs/pending', ['X-API-KEY' => 'k']);
assertSame($r['status'], 200, 'pending status');
assertSame($r['body']['jobs'][0]['id'], 1, 'pending payload');

$r = $app->handle('POST', '/jobs/409/start', ['X-API-KEY' => 'k']);
assertSame($r['status'], 409, 'start conflict');
assertSame($r['body']['error']['code'], 'job_not_actionable', 'start error code');

$r = $app->handle('POST', '/jobs/12/result', ['X-API-KEY' => 'k'], '{}');
assertSame($r['status'], 400, 'result validation');

$r = $app->handle('POST', '/jobs/12/result', ['X-API-KEY' => 'k'], '{"result_link":"abc"}');
assertSame($r['status'], 200, 'result ok');

$r = $app->handle('POST', '/jobs/12/error', ['X-API-KEY' => 'k'], '{"error":"boom"}');
assertSame($r['status'], 200, 'error ok');
assertSame($r['body']['retry_count'], 2, 'error retry');

assertSame(XuiJobState::statusAfterError(1), 'failed', 'retry below threshold');
assertSame(XuiJobState::statusAfterError(5), 'error', 'retry threshold reached');
assertSame(XuiJobState::nextRetryCount(0), 1, 'next retry');

echo "WorkerApiAppTest: OK\n";
