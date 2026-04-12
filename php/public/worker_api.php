<?php

declare(strict_types=1);

use ConfigFlow\Bot\Bootstrap;
use ConfigFlow\Bot\Database;

require __DIR__ . '/../src/Bootstrap.php';
require __DIR__ . '/../src/Config.php';
require __DIR__ . '/../src/Database.php';

Bootstrap::loadEnv(__DIR__ . '/../.env');
$db = new Database();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '/';
$path = preg_replace('#^/+#', '/', $path);

$send = static function (array $body, int $status = 200): void {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
};

if ($path === '/health') {
    $send(['status' => 'ok', 'service' => 'ConfigFlow Worker API']);
}

if (!$db->isWorkerApiEnabled()) {
    $send(['error' => 'API disabled'], 503);
}

$expectedKey = $db->workerApiKey();
if ($expectedKey === '') {
    $send(['error' => 'API key not configured on server'], 503);
}

$provided = (string) ($_SERVER['HTTP_X_API_KEY'] ?? '');
if ($provided !== $expectedKey) {
    $send(['error' => 'Unauthorized'], 401);
}

if ($method === 'GET' && $path === '/jobs/pending') {
    $send(['jobs' => $db->listPendingXuiJobs(20)]);
}

if (preg_match('#^/jobs/(\d+)/start$#', $path, $m) && $method === 'POST') {
    $jobId = (int) $m[1];
    $res = $db->markXuiJobProcessing($jobId);
    if (!($res['ok'] ?? false)) {
        if (($res['error'] ?? '') === 'not_found') {
            $send(['error' => 'Job not found'], 404);
        }
        if (($res['error'] ?? '') === 'not_actionable') {
            $send(['error' => 'Job not in actionable state', 'status' => $res['status'] ?? null], 409);
        }
        $send(['error' => 'db_error'], 500);
    }
    $send(['ok' => true, 'job_id' => $jobId]);
}

if (preg_match('#^/jobs/(\d+)/result$#', $path, $m) && $method === 'POST') {
    $jobId = (int) $m[1];
    $raw = file_get_contents('php://input') ?: '';
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        $payload = [];
    }
    $resultConfig = trim((string) ($payload['result_config'] ?? ''));
    $resultLink = trim((string) ($payload['result_link'] ?? ''));
    if ($resultConfig === '' && $resultLink === '') {
        $send(['error' => 'result_config or result_link required'], 400);
    }
    $res = $db->markXuiJobDone($jobId, $resultConfig, $resultLink);
    if (!($res['ok'] ?? false)) {
        if (($res['error'] ?? '') === 'not_found') {
            $send(['error' => 'Job not found'], 404);
        }
        $send(['error' => 'db_error'], 500);
    }
    $send(['ok' => true, 'job_id' => $jobId]);
}

if (preg_match('#^/jobs/(\d+)/error$#', $path, $m) && $method === 'POST') {
    $jobId = (int) $m[1];
    $raw = file_get_contents('php://input') ?: '';
    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        $payload = [];
    }
    $msg = (string) ($payload['error'] ?? 'Unknown error');
    $res = $db->markXuiJobError($jobId, $msg);
    if (!($res['ok'] ?? false)) {
        if (($res['error'] ?? '') === 'not_found') {
            $send(['error' => 'Job not found'], 404);
        }
        $send(['error' => 'db_error'], 500);
    }
    $send(['ok' => true, 'job_id' => $jobId, 'retry_count' => (int) ($res['retry_count'] ?? 0)]);
}

if (preg_match('#^/jobs/(\d+)$#', $path, $m) && $method === 'GET') {
    $jobId = (int) $m[1];
    $row = $db->getXuiJob($jobId);
    if (!is_array($row)) {
        $send(['error' => 'Job not found'], 404);
    }
    $send($row);
}

$send(['error' => 'Not Found'], 404);
