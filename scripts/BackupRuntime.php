<?php

declare(strict_types=1);

use ConfigFlow\Bot\Bootstrap;
use ConfigFlow\Bot\Database;
use ConfigFlow\Bot\DatabaseBackupService;
use ConfigFlow\Bot\SettingsRepository;
use ConfigFlow\Bot\TelegramClient;
use ConfigFlow\Bot\Config;

require_once __DIR__ . '/../src/Bootstrap.php';
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/SettingsRepository.php';
require_once __DIR__ . '/../src/TelegramClient.php';
require_once __DIR__ . '/../src/DatabaseBackupService.php';

Bootstrap::loadEnv(dirname(__DIR__) . '/.env');

$token = Config::botToken();
if ($token === '') {
    fwrite(STDERR, "BOT_TOKEN is missing\n");
    exit(1);
}

$db = new Database();
$settings = new SettingsRepository($db);
$telegram = new TelegramClient($token);
$service = new DatabaseBackupService($db, $telegram, $settings);

$lastBackupAt = 0;
fwrite(STDOUT, "backup_runtime started\n");

while (true) {
    sleep(60);

    $enabled = $settings->get('backup_enabled', '0') === '1';
    $target = trim($settings->get('backup_target_id', ''));
    $intervalHours = max(1, (int) $settings->get('backup_interval', '24'));

    if (!$enabled || $target === '') {
        continue;
    }

    $now = time();
    if (($now - $lastBackupAt) < ($intervalHours * 3600)) {
        continue;
    }

    $ok = $service->sendBackup();
    if ($ok) {
        $lastBackupAt = $now;
        fwrite(STDOUT, '[' . gmdate('c') . "] backup sent\n");
    } else {
        fwrite(STDOUT, '[' . gmdate('c') . "] backup failed\n");
    }
}
