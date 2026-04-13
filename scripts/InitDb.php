<?php

declare(strict_types=1);

use ConfigFlow\Bot\Database;
use ConfigFlow\Bot\Bootstrap;

require_once __DIR__ . '/../src/Bootstrap.php';
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/WorkerApiStore.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/MigrationRunner.php';

Bootstrap::loadEnv(__DIR__ . '/../.env');

$db = new Database();
$pdo = $db->pdo();

$schemaPath = __DIR__ . '/schema.sql';
$schema = file_get_contents($schemaPath);
if ($schema === false) {
    throw new RuntimeException('Could not read schema.sql');
}

$pdo->exec($schema);

// Lightweight forward-compatible column migrations for existing installations
$pdo->exec("ALTER TABLE payments ADD COLUMN IF NOT EXISTS gateway_ref VARCHAR(191) NULL");
$pdo->exec("ALTER TABLE payments ADD COLUMN IF NOT EXISTS tx_hash VARCHAR(255) NULL");
$pdo->exec("ALTER TABLE payments ADD COLUMN IF NOT EXISTS crypto_amount_claimed DECIMAL(24,8) NULL");
$pdo->exec("ALTER TABLE payments ADD COLUMN IF NOT EXISTS provider_payload TEXT NULL");
$pdo->exec("ALTER TABLE payments ADD COLUMN IF NOT EXISTS receipt_file_id VARCHAR(255) NULL");
$pdo->exec("ALTER TABLE payments ADD COLUMN IF NOT EXISTS receipt_text TEXT NULL");
$pdo->exec("ALTER TABLE payments ADD COLUMN IF NOT EXISTS admin_note TEXT NULL");
$pdo->exec("ALTER TABLE payments ADD COLUMN IF NOT EXISTS verified_at DATETIME NULL");
$pdo->exec("ALTER TABLE payments ADD COLUMN IF NOT EXISTS verify_attempts INT NOT NULL DEFAULT 0");
$pdo->exec("ALTER TABLE payments ADD COLUMN IF NOT EXISTS last_verify_at DATETIME NULL");

// Request tracking tables for free-test / agency workflows
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS free_test_requests (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT NOT NULL,
        note TEXT NOT NULL,
        status VARCHAR(32) NOT NULL DEFAULT 'pending',
        admin_note TEXT NULL,
        created_at DATETIME NOT NULL,
        reviewed_at DATETIME NULL,
        INDEX idx_free_test_user (user_id),
        INDEX idx_free_test_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS agency_requests (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT NOT NULL,
        note TEXT NOT NULL,
        status VARCHAR(32) NOT NULL DEFAULT 'pending',
        admin_note TEXT NULL,
        created_at DATETIME NOT NULL,
        reviewed_at DATETIME NULL,
        INDEX idx_agency_user (user_id),
        INDEX idx_agency_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);
$migrator = new \ConfigFlow\Bot\MigrationRunner($pdo, __DIR__ . '/../migrations');
$migrator->applyAll();

$defaults = [
    'bot_status' => 'on',
    'start_text' => '',
    'channel_id' => '',
    'group_id' => '',
    'group_topic_backup' => '',
    'group_topic_broadcast_report' => '',
    'group_topic_error_log' => '',
    'backup_enabled' => '0',
    'backup_interval' => '24',
    'backup_target_id' => '',
    'php_worker_runtime_enabled' => '0',
    'php_worker_poll_interval' => '10',
];

$stmt = $pdo->prepare('INSERT IGNORE INTO settings (`key`, `value`) VALUES (:key, :value)');
foreach ($defaults as $key => $value) {
    $stmt->execute(['key' => $key, 'value' => $value]);
}

echo "MySQL schema initialized.\n";
