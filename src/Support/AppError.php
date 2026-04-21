<?php

declare(strict_types=1);

namespace ConfigFlow\Bot\Support;

final class AppError
{
    /** @param array<string,mixed> $details */
    public static function make(
        string $code,
        string $userMessageKey,
        string $provider,
        string $stage,
        array $details = [],
        bool $retryable = false,
        ?string $errorRef = null
    ): array {
        return [
            'ok' => false,
            'code' => $code,
            'user_message_key' => $userMessageKey,
            'admin_message' => (string) ($details['admin_message'] ?? $code),
            'error_ref' => $errorRef ?? ErrorRef::make(strtoupper(substr($provider, 0, 2))),
            'stage' => $stage,
            'provider' => $provider,
            'http_status' => $details['http_status'] ?? null,
            'details' => $details,
            'retryable' => $retryable,
        ];
    }
}
