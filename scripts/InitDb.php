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

/**
 * Execute SQL scripts safely across hosts where multi-statement exec is disabled.
 */
$executeSqlBatch = static function (PDO $pdo, string $sql): void {
    $normalized = str_replace("\r\n", "\n", $sql);
    $chunks = preg_split('/;\n+/', $normalized) ?: [];
    foreach ($chunks as $chunk) {
        $stmt = trim($chunk);
        if ($stmt === '') {
            continue;
        }
        if (str_starts_with($stmt, '--') || str_starts_with($stmt, '#')) {
            continue;
        }
        $pdo->exec($stmt);
    }
};

$executeSqlBatch($pdo, $schema);

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

$pdo->exec("DROP TABLE IF EXISTS free_test_requests");
$pdo->exec("DROP TABLE IF EXISTS free_test_package_rules");
$pdo->exec("DROP TABLE IF EXISTS free_test_claims");
$pdo->exec("DROP TABLE IF EXISTS packages");
$pdo->exec("DROP TABLE IF EXISTS configs");
$pdo->exec("DROP TABLE IF EXISTS provisioning_services");
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
