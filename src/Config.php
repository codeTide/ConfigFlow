<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class Config
{
    private static ?string $cachedBotUsername = null;

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
        if (self::$cachedBotUsername !== null) {
            return self::$cachedBotUsername;
        }

        if (!function_exists('curl_init')) {
            self::$cachedBotUsername = '';
            return self::$cachedBotUsername;
        }

        $token = self::botToken();
        if ($token === '') {
            self::$cachedBotUsername = '';
            return self::$cachedBotUsername;
        }

        $endpoint = "https://api.telegram.org/bot{$token}/getMe";
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);

        $raw = curl_exec($ch);
        curl_close($ch);
        if (!is_string($raw) || $raw === '') {
            self::$cachedBotUsername = '';
            return self::$cachedBotUsername;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || ($decoded['ok'] ?? false) !== true) {
            self::$cachedBotUsername = '';
            return self::$cachedBotUsername;
        }

        self::$cachedBotUsername = trim((string) ($decoded['result']['username'] ?? ''));
        return self::$cachedBotUsername;
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
