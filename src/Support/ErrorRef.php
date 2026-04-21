<?php

declare(strict_types=1);

namespace ConfigFlow\Bot\Support;

final class ErrorRef
{
    public static function make(string $prefix = 'SYS'): string
    {
        $prefix = strtoupper(trim($prefix));
        if ($prefix === '') {
            $prefix = 'SYS';
        }
        return $prefix . '-' . gmdate('Ymd-His') . '-' . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
