<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class XuiJobState
{
    public const MAX_RETRIES = 5;

    public static function nextRetryCount(int $current): int
    {
        return max(0, $current) + 1;
    }

    public static function statusAfterError(int $retryCount): string
    {
        return $retryCount < self::MAX_RETRIES ? 'failed' : 'error';
    }
}
