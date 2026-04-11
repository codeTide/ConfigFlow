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

$defaults = [
    'bot_status' => 'on',
    'start_text' => '',
];

$stmt = $pdo->prepare('INSERT IGNORE INTO settings (`key`, `value`) VALUES (:key, :value)');
foreach ($defaults as $key => $value) {
    $stmt->execute(['key' => $key, 'value' => $value]);
}

echo "MySQL schema initialized.\n";
