<?php

declare(strict_types=1);

/**
 * Schema upgrade helper for existing deployments.
 *
 * Usage:
 *   php php/scripts/upgrade_schema.php
 */

$root = dirname(__DIR__, 2);
$envPath = $root . '/.env';
if (is_file($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v, " \t\n\r\0\x0B\"'");
        if ($k !== '' && getenv($k) === false) {
            putenv("{$k}={$v}");
        }
    }
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('DB_PORT') ?: '3306');
$name = getenv('DB_NAME') ?: 'configflow';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name);
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$sql = file_get_contents(__DIR__ . '/schema.sql');
if (!is_string($sql) || trim($sql) === '') {
    fwrite(STDERR, "schema.sql not found or empty\n");
    exit(1);
}

/** @return bool */
function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name'
    );
    $stmt->execute(['table_name' => $table, 'column_name' => $column]);
    return (int) $stmt->fetchColumn() > 0;
}

/** @return bool */
function indexExists(PDO $pdo, string $table, string $index): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND INDEX_NAME = :index_name'
    );
    $stmt->execute(['table_name' => $table, 'index_name' => $index]);
    return (int) $stmt->fetchColumn() > 0;
}

try {
    $pdo->beginTransaction();

    $parts = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($parts as $stmt) {
        if ($stmt === '') {
            continue;
        }
        $pdo->exec($stmt);
    }

    $defaults = [
        'worker_api_enabled' => '0',
        'worker_api_port' => '8080',
        'worker_api_key' => '',
        'manual_renewal_enabled' => '1',
        'gw_swapwallet_crypto_enabled' => '0',
        'gw_tronpays_rial_enabled' => '0',
        'swapwallet_crypto_api_key' => '',
        'swapwallet_crypto_username' => '',
        'tronpays_rial_api_key' => '',
        'tronpays_rial_callback_url' => '',
        'gw_card_min' => '0',
        'gw_card_max' => '0',
        'gw_crypto_min' => '0',
        'gw_crypto_max' => '0',
        'gw_tetrapay_min' => '0',
        'gw_tetrapay_max' => '0',
        'gw_swapwallet_crypto_min' => '0',
        'gw_swapwallet_crypto_max' => '0',
        'gw_tronpays_rial_min' => '0',
        'gw_tronpays_rial_max' => '0',
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
    $upsert = $pdo->prepare('INSERT INTO settings (`key`, `value`) VALUES (:k, :v) ON DUPLICATE KEY UPDATE `value` = `value`');
    foreach ($defaults as $k => $v) {
        $upsert->execute(['k' => $k, 'v' => $v]);
    }

    if (!columnExists($pdo, 'xui_jobs', 'order_id')) {
        $pdo->exec('ALTER TABLE xui_jobs ADD COLUMN order_id BIGINT NULL AFTER job_uuid');
    }
    if (!indexExists($pdo, 'xui_jobs', 'uniq_xui_jobs_order')) {
        // Keep migration safe for legacy rows with order_id=0; uniqueness applies only to real orders.
        $pdo->exec('ALTER TABLE xui_jobs ADD UNIQUE KEY uniq_xui_jobs_order (order_id)');
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Schema upgrade failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo "✅ Schema upgrade completed successfully.\n";
