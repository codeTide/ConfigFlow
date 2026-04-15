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

        if (str_starts_with($path, '/jobs')) {
            return $this->error('endpoint_removed', 'x-ui job runtime endpoints are removed', 410);
        }

        return $this->error('not_found', 'Not Found', 404);
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
