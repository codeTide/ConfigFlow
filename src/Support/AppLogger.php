<?php

declare(strict_types=1);

namespace ConfigFlow\Bot\Support;

final class AppLogger
{
    public function __construct(private ?string $baseDir = null)
    {
        $this->baseDir ??= dirname(__DIR__, 2) . '/storage/logs';
    }

    /** @param array<string,mixed> $context */
    public function log(string $level, string $channel, string $code, string $message, array $context = [], ?string $ref = null): string
    {
        try {
            $ref ??= ErrorRef::make(strtoupper(substr($channel, 0, 3)));
            $entry = [
                'ref' => $ref,
                'ts' => gmdate('c'),
                'level' => $level,
                'channel' => $channel,
                'code' => $code,
                'message' => $message,
                'context' => PayloadSanitizer::sanitize($context),
                'user_id' => $context['user_id'] ?? null,
                'payment_id' => $context['payment_id'] ?? null,
                'purchase_id' => $context['purchase_id'] ?? null,
                'gateway' => $context['gateway'] ?? null,
                'stage' => $context['stage'] ?? null,
                'http_status' => $context['http_status'] ?? null,
                'provider_error' => $context['provider_error'] ?? null,
                'request_payload_safe' => isset($context['request_payload']) && is_array($context['request_payload']) ? PayloadSanitizer::sanitize($context['request_payload']) : null,
                'response_payload_safe' => isset($context['response_payload']) && is_array($context['response_payload']) ? PayloadSanitizer::sanitize($context['response_payload']) : null,
                'raw_response' => $this->safeRaw($context['raw_response'] ?? null),
                'exception_class' => $context['exception_class'] ?? null,
                'exception_message' => $context['exception_message'] ?? null,
            ];

            if (!is_dir($this->baseDir)) {
                @mkdir($this->baseDir, 0775, true);
            }
            $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!is_string($line)) {
                $line = json_encode(['ref' => $ref, 'ts' => gmdate('c'), 'level' => $level, 'channel' => $channel, 'code' => $code, 'message' => 'json_encode_failed']);
            }
            $line = (string) $line . PHP_EOL;
            @file_put_contents($this->baseDir . '/app-' . preg_replace('/[^a-z0-9_\\-]/i', '_', strtolower($channel)) . '.log', $line, FILE_APPEND | LOCK_EX);
            @file_put_contents($this->baseDir . '/app.log', $line, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            @error_log('[AppLoggerFailSafe] channel=' . $channel . ' code=' . $code . ' err=' . $e->getMessage());
            $ref ??= ErrorRef::make('LOG');
        }
        return $ref;
    }

    private function safeRaw(mixed $raw): ?string
    {
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $clean = preg_replace('/[^\P{C}\n\r\t]/u', '?', $raw) ?? '';
        if (strlen($clean) > 2000) {
            return substr($clean, 0, 2000) . '...[truncated]';
        }
        return $clean;
    }
}
