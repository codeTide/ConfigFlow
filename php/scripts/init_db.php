<?php

declare(strict_types=1);

use ConfigFlow\Bot\Database;

require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Database.php';

$db = new Database();
$pdo = $db->pdo();

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS users (
        user_id BIGINT PRIMARY KEY,
        full_name VARCHAR(255) NULL,
        username VARCHAR(255) NULL,
        balance INT NOT NULL DEFAULT 0,
        joined_at DATETIME NOT NULL,
        last_seen_at DATETIME NOT NULL,
        first_start_notified TINYINT(1) NOT NULL DEFAULT 0,
        status VARCHAR(32) NOT NULL DEFAULT 'unsafe',
        is_agent TINYINT(1) NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

echo "MySQL schema initialized.\n";
