<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class Config
{
    public static function botToken(): string
    {
        return trim((string) getenv('BOT_TOKEN'));
    }

    public static function adminIds(): array
    {
        $raw = (string) getenv('ADMIN_IDS');
        $ids = array_filter(array_map('trim', explode(',', $raw)), static fn($item) => $item !== '');

        return array_values(array_map('intval', $ids));
    }

    public static function dbHost(): string
    {
        return (string) getenv('DB_HOST') ?: '127.0.0.1';
    }

    public static function dbPort(): int
    {
        return (int) ((string) getenv('DB_PORT') ?: '3306');
    }

    public static function dbName(): string
    {
        return (string) getenv('DB_NAME') ?: 'configflow';
    }

    public static function dbUser(): string
    {
        return (string) getenv('DB_USER') ?: 'root';
    }

    public static function dbPass(): string
    {
        return (string) getenv('DB_PASS');
    }

    public static function botUsername(): string
    {
        return trim((string) getenv('BOT_USERNAME'));
    }

    public static function tetrapayCreateUrl(): string
    {
        return (string) getenv('TETRAPAY_CREATE_URL') ?: 'https://tetra98.com/api/create_order';
    }

    public static function tetrapayVerifyUrl(): string
    {
        return (string) getenv('TETRAPAY_VERIFY_URL') ?: 'https://tetra98.com/api/verify';
    }

    public static function swapwalletBaseUrl(): string
    {
        return (string) getenv('SWAPWALLET_BASE_URL') ?: 'https://api.swapwallet.org';
    }

    public static function tronpaysBaseUrl(): string
    {
        return (string) getenv('TRONPAYS_RIAL_BASE_URL') ?: 'https://api.tronpays.online';
    }
}
