<?php

declare(strict_types=1);

use ConfigFlow\Bot\Bootstrap;
use ConfigFlow\Bot\Database;

require_once __DIR__ . '/../src/Bootstrap.php';
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Database.php';

Bootstrap::loadEnv(__DIR__ . '/../.env');

$sqlitePath = $argv[1] ?? null;
if ($sqlitePath === null || !is_file($sqlitePath)) {
    fwrite(STDERR, "Usage: php php/scripts/migrate_sqlite_to_mysql.php /path/to/configflow.db\n");
    exit(1);
}

$mysql = (new Database())->pdo();
$sqlite = new PDO('sqlite:' . $sqlitePath);
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$tables = ['users', 'settings', 'referrals', 'config_types', 'packages', 'purchases'];

$mysql->beginTransaction();

try {
    foreach ($tables as $table) {
        $rows = $sqlite->query("SELECT * FROM {$table}")->fetchAll();
        if ($rows === []) {
            continue;
        }

        $columns = array_keys($rows[0]);
        $columnSql = implode(', ', array_map(static fn(string $col) => "`{$col}`", $columns));
        $paramSql = implode(', ', array_map(static fn(string $col) => ':' . $col, $columns));
        $updateSql = implode(', ', array_map(static fn(string $col) => "`{$col}` = VALUES(`{$col}`)", $columns));

        $sql = "INSERT INTO `{$table}` ({$columnSql}) VALUES ({$paramSql}) ON DUPLICATE KEY UPDATE {$updateSql}";
        $stmt = $mysql->prepare($sql);

        foreach ($rows as $row) {
            $stmt->execute($row);
        }

        echo "Migrated {$table}: " . count($rows) . " rows\n";
    }

    $mysql->commit();
    echo "SQLite -> MySQL migration completed successfully.\n";
} catch (Throwable $e) {
    $mysql->rollBack();
    fwrite(STDERR, "Migration failed: {$e->getMessage()}\n");
    exit(1);
}
