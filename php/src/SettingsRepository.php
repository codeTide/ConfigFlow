<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class SettingsRepository
{
    public function __construct(private Database $database)
    {
    }

    public function get(string $key, string $default = ''): string
    {
        $stmt = $this->database->pdo()->prepare('SELECT value FROM settings WHERE `key` = :key LIMIT 1');
        $stmt->execute(['key' => $key]);
        $value = $stmt->fetchColumn();

        if ($value === false || $value === null) {
            return $default;
        }

        return (string) $value;
    }
}
