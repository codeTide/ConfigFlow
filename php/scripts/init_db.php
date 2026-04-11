<?php

declare(strict_types=1);

use ConfigFlow\Bot\Database;
use ConfigFlow\Bot\Bootstrap;

require_once __DIR__ . '/../src/Bootstrap.php';
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Database.php';

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
$pdo->exec("ALTER TABLE payments ADD COLUMN IF NOT EXISTS provider_payload TEXT NULL");
$pdo->exec("ALTER TABLE payments ADD COLUMN IF NOT EXISTS receipt_file_id VARCHAR(255) NULL");
$pdo->exec("ALTER TABLE payments ADD COLUMN IF NOT EXISTS receipt_text TEXT NULL");
$pdo->exec("ALTER TABLE payments ADD COLUMN IF NOT EXISTS admin_note TEXT NULL");
$pdo->exec("ALTER TABLE payments ADD COLUMN IF NOT EXISTS verified_at DATETIME NULL");

$defaults = [
    'bot_status' => 'on',
    'start_text' => '',
];

$stmt = $pdo->prepare('INSERT IGNORE INTO settings (`key`, `value`) VALUES (:key, :value)');
foreach ($defaults as $key => $value) {
    $stmt->execute(['key' => $key, 'value' => $value]);
}

echo "MySQL schema initialized.\n";
