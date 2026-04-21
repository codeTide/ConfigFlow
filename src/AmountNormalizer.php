<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class AmountNormalizer
{
    /** @return array{ok:bool,amount?:int} */
    public static function parseToInt(string $raw): array
    {
        $normalized = self::normalizeDigits($raw);
        $normalized = preg_replace('/[^\d]/u', '', $normalized) ?? '';
        if ($normalized === '') {
            return ['ok' => false];
        }

        $amount = (int) ltrim($normalized, '0');
        if ($amount <= 0) {
            return ['ok' => false];
        }

        return ['ok' => true, 'amount' => $amount];
    }

    public static function normalizeDigits(string $value): string
    {
        return strtr($value, [
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
        ]);
    }
}
