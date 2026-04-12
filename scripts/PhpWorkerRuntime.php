<?php

declare(strict_types=1);

use ConfigFlow\Bot\Bootstrap;
use ConfigFlow\Bot\Database;
use ConfigFlow\Bot\PhpWorkerRuntime;

require_once __DIR__ . '/../src/Bootstrap.php';
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/XuiJobState.php';
require_once __DIR__ . '/../src/WorkerApiStore.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/PhpWorkerRuntime.php';

Bootstrap::loadEnv(dirname(__DIR__) . '/.env');

$db = new Database();
$runtime = new PhpWorkerRuntime($db);

$argvList = $_SERVER['argv'] ?? [];
$once = in_array('--once', $argvList, true);
$limit = 20;
foreach ($argvList as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = max(1, min(100, (int) substr($arg, 8)));
    }
}

$getSetting = static function (string $key, string $default) use ($db): string {
    $stmt = $db->pdo()->prepare('SELECT `value` FROM settings WHERE `key` = :k LIMIT 1');
    $stmt->execute(['k' => $key]);
    $v = $stmt->fetchColumn();
    return $v === false || $v === null ? $default : (string) $v;
};

$enabled = $getSetting('php_worker_runtime_enabled', '0') === '1';
$interval = max(3, (int) $getSetting('php_worker_poll_interval', '10'));

if (!$enabled && !$once) {
    fwrite(STDOUT, "php_worker_runtime is disabled (php_worker_runtime_enabled=0)\n");
    exit(0);
}

if ($once) {
    $stats = $runtime->runOnce($limit);
    fwrite(STDOUT, json_encode($stats, JSON_UNESCAPED_UNICODE) . PHP_EOL);
    exit(0);
}

fwrite(STDOUT, "PHP worker runtime started. interval={$interval}s limit={$limit}\n");
while (true) {
    $stats = $runtime->runOnce($limit);
    fwrite(STDOUT, '[' . gmdate('c') . '] ' . json_encode($stats, JSON_UNESCAPED_UNICODE) . PHP_EOL);
    sleep($interval);
}
