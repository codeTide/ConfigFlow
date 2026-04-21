<?php

declare(strict_types=1);

namespace ConfigFlow\Bot\Support;

final class PayloadSanitizer
{
    /** @return array<string,mixed> */
    public static function sanitize(array $payload): array
    {
        $out = [];
        foreach ($payload as $k => $v) {
            $key = (string) $k;
            if (is_array($v)) {
                $out[$key] = self::sanitize($v);
                continue;
            }
            $raw = (string) $v;
            if (self::isSecretKey($key)) {
                $out[$key] = self::maskToken($raw);
                continue;
            }
            if (str_contains(strtolower($key), 'email')) {
                $out[$key] = self::maskEmail($raw);
                continue;
            }
            if (str_contains(strtolower($key), 'mobile') || str_contains(strtolower($key), 'phone')) {
                $out[$key] = self::maskPhone($raw);
                continue;
            }
            $out[$key] = $v;
        }
        return $out;
    }

    private static function isSecretKey(string $key): bool
    {
        $key = strtolower($key);
        foreach (['key', 'token', 'secret', 'password', 'authorization'] as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }
        return false;
    }

    private static function maskToken(string $value): string
    {
        $len = mb_strlen($value);
        if ($len <= 8) {
            return str_repeat('*', max(4, $len));
        }
        return mb_substr($value, 0, 4) . str_repeat('*', $len - 8) . mb_substr($value, -4);
    }

    private static function maskEmail(string $value): string
    {
        if (!str_contains($value, '@')) {
            return $value;
        }
        [$name, $domain] = explode('@', $value, 2);
        $nameMasked = mb_substr($name, 0, 2) . '***';
        return $nameMasked . '@' . $domain;
    }

    private static function maskPhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if ($digits === '') {
            return $value;
        }
        $tail = substr($digits, -3);
        return '***' . $tail;
    }
}
