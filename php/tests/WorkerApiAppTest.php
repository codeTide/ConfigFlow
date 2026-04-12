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
    /** @var array<int,array<string,mixed>> */
    public array $jobs = [
        12 => ['id' => 12, 'status' => 'processing', 'result_config' => '', 'result_link' => '', 'error_msg' => ''],
        13 => ['id' => 13, 'status' => 'done', 'result_config' => '', 'result_link' => 'abc', 'error_msg' => ''],
        14 => ['id' => 14, 'status' => 'failed', 'result_config' => '', 'result_link' => '', 'error_msg' => 'boom'],
        409 => ['id' => 409, 'status' => 'processing', 'result_config' => '', 'result_link' => '', 'error_msg' => ''],
    ];

    public function isWorkerApiEnabled(): bool { return $this->enabled; }
    public function workerApiKey(): string { return $this->apiKey; }
    public function listPendingXuiJobs(int $limit = 20): array { return [['id' => 1]]; }
    public function markXuiJobProcessing(int $jobId): array {
        return match ($jobId) {
            404 => ['ok' => false, 'error' => 'not_found'],
            409 => ['ok' => false, 'error' => 'not_actionable', 'status' => 'processing'],
            default => ['ok' => true],
        };
    }
    public function markXuiJobDone(int $jobId, string $resultConfig, string $resultLink): array {
        if (!isset($this->jobs[$jobId])) {
            return ['ok' => false, 'error' => 'not_found'];
        }
        $this->jobs[$jobId]['status'] = 'done';
        $this->jobs[$jobId]['result_config'] = $resultConfig;
        $this->jobs[$jobId]['result_link'] = $resultLink;
        return ['ok' => true];
    }
    public function markXuiJobError(int $jobId, string $errorMsg): array {
        if (!isset($this->jobs[$jobId])) {
            return ['ok' => false, 'error' => 'not_found'];
        }
        $this->jobs[$jobId]['status'] = 'failed';
        $this->jobs[$jobId]['error_msg'] = $errorMsg;
        return ['ok' => true, 'retry_count' => 2];
    }
    public function getXuiJob(int $jobId): ?array {
        return $this->jobs[$jobId] ?? null;
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
assertSame($r['status'], 200, 'start idempotent');
assertSame($r['body']['idempotent'], true, 'start idempotent flag');

$r = $app->handle('POST', '/jobs/12/result', ['X-API-KEY' => 'k'], '{}');
assertSame($r['status'], 400, 'result validation');

$r = $app->handle('POST', '/jobs/12/result', ['X-API-KEY' => 'k'], '{"result_link":"abc"}');
assertSame($r['status'], 200, 'result ok');

$r = $app->handle('POST', '/jobs/13/result', ['X-API-KEY' => 'k'], '{"result_link":"abc"}');
assertSame($r['status'], 200, 'result idempotent');
assertSame($r['body']['idempotent'], true, 'result idempotent flag');

$r = $app->handle('POST', '/jobs/13/result', ['X-API-KEY' => 'k'], '{"result_link":"changed"}');
assertSame($r['status'], 409, 'result conflict');
assertSame($r['body']['error']['code'], 'result_conflict', 'result conflict code');

$r = $app->handle('POST', '/jobs/12/error', ['X-API-KEY' => 'k'], '{"error_message":"boom"}');
assertSame($r['status'], 200, 'error ok');
assertSame($r['body']['retry_count'], 2, 'error retry');

$r = $app->handle('POST', '/jobs/14/error', ['X-API-KEY' => 'k'], '{"error_message":"boom"}');
assertSame($r['status'], 200, 'error idempotent');
assertSame($r['body']['idempotent'], true, 'error idempotent flag');

assertSame(XuiJobState::statusAfterError(1), 'failed', 'retry below threshold');
assertSame(XuiJobState::statusAfterError(5), 'error', 'retry threshold reached');
assertSame(XuiJobState::nextRetryCount(0), 1, 'next retry');

echo "WorkerApiAppTest: OK\n";
