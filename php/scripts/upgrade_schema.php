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
];
$upsert = $pdo->prepare('INSERT INTO settings (`key`, `value`) VALUES (:k, :v) ON DUPLICATE KEY UPDATE `value` = `value`');
foreach ($defaults as $k => $v) {
    $upsert->execute(['k' => $k, 'v' => $v]);
}

echo "✅ Schema upgrade completed successfully.\n";
