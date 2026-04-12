<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class WorkerApiApp
{
    public function __construct(private readonly WorkerApiStore $store)
    {
    }

    /** @param array<string,string> $headers */
    public function handle(string $method, string $requestUri, array $headers = [], string $rawBody = ''): array
    {
        $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';
        $path = preg_replace('#^/+#', '/', $path) ?: '/';

        if ($path === '/health') {
            return $this->ok(['status' => 'ok', 'service' => 'ConfigFlow Worker API']);
        }

        if (!$this->store->isWorkerApiEnabled()) {
            return $this->error('api_disabled', 'API disabled', 503);
        }

        $expectedKey = $this->store->workerApiKey();
        if ($expectedKey === '') {
            return $this->error('api_key_missing', 'API key not configured on server', 503);
        }

        $provided = trim((string) ($headers['X-API-KEY'] ?? $headers['x-api-key'] ?? ''));
        if ($provided !== $expectedKey) {
            return $this->error('unauthorized', 'Unauthorized', 401);
        }

        if ($method === 'GET' && $path === '/jobs/pending') {
            return $this->ok(['jobs' => $this->store->listPendingXuiJobs(20)]);
        }

        if ($method === 'POST' && preg_match('#^/jobs/(\d+)/start$#', $path, $m)) {
            return $this->handleStart((int) $m[1]);
        }

        if ($method === 'POST' && preg_match('#^/jobs/(\d+)/result$#', $path, $m)) {
            return $this->handleResult((int) $m[1], $rawBody);
        }

        if ($method === 'POST' && preg_match('#^/jobs/(\d+)/error$#', $path, $m)) {
            return $this->handleError((int) $m[1], $rawBody);
        }

        if ($method === 'GET' && preg_match('#^/jobs/(\d+)$#', $path, $m)) {
            $row = $this->store->getXuiJob((int) $m[1]);
            if (!is_array($row)) {
                return $this->error('job_not_found', 'Job not found', 404);
            }

            return $this->ok(['job' => $row]);
        }

        return $this->error('not_found', 'Not Found', 404);
    }

    private function handleStart(int $jobId): array
    {
        $res = $this->store->markXuiJobProcessing($jobId);
        if (!($res['ok'] ?? false)) {
            if (($res['error'] ?? '') === 'not_found') {
                return $this->error('job_not_found', 'Job not found', 404);
            }

            if (($res['error'] ?? '') === 'not_actionable') {
                return $this->error('job_not_actionable', 'Job not in actionable state', 409, ['status' => $res['status'] ?? null]);
            }

            return $this->error('db_error', 'db_error', 500);
        }

        return $this->ok(['job_id' => $jobId]);
    }

    private function handleResult(int $jobId, string $rawBody): array
    {
        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            $payload = [];
        }
        $resultConfig = trim((string) ($payload['result_config'] ?? ''));
        $resultLink = trim((string) ($payload['result_link'] ?? ''));
        if ($resultConfig === '' && $resultLink === '') {
            return $this->error('result_required', 'result_config or result_link required', 400);
        }
        $res = $this->store->markXuiJobDone($jobId, $resultConfig, $resultLink);
        if (!($res['ok'] ?? false)) {
            if (($res['error'] ?? '') === 'not_found') {
                return $this->error('job_not_found', 'Job not found', 404);
            }

            return $this->error('db_error', 'db_error', 500);
        }

        return $this->ok(['job_id' => $jobId]);
    }

    private function handleError(int $jobId, string $rawBody): array
    {
        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $msg = (string) ($payload['error'] ?? 'Unknown error');
        $res = $this->store->markXuiJobError($jobId, $msg);
        if (!($res['ok'] ?? false)) {
            if (($res['error'] ?? '') === 'not_found') {
                return $this->error('job_not_found', 'Job not found', 404);
            }

            return $this->error('db_error', 'db_error', 500);
        }

        return $this->ok(['job_id' => $jobId, 'retry_count' => (int) ($res['retry_count'] ?? 0)]);
    }

    private function ok(array $body): array
    {
        return [
            'status' => 200,
            'body' => ['ok' => true] + $body,
        ];
    }

    private function error(string $code, string $message, int $status, array $extra = []): array
    {
        return [
            'status' => $status,
            'body' => ['ok' => false, 'error' => ['code' => $code, 'message' => $message]] + $extra,
        ];
    }
}
