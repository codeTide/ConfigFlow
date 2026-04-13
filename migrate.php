<?php

declare(strict_types=1);

use ConfigFlow\Bot\Bootstrap;
use ConfigFlow\Bot\Config;
use ConfigFlow\Bot\MigrationRunner;

require_once __DIR__ . '/src/Bootstrap.php';
require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/src/MigrationRunner.php';

Bootstrap::loadEnv(__DIR__ . '/.env');

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    Config::dbHost(),
    Config::dbPort(),
    Config::dbName()
);

$pdo = new PDO($dsn, Config::dbUser(), Config::dbPass(), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$runner = new MigrationRunner($pdo, __DIR__ . '/migrations');

if (PHP_SAPI === 'cli') {
    $applied = $runner->applyAll();
    if ($applied === []) {
        echo "No pending migrations.\n";
        exit(0);
    }
    echo "Applied migrations:\n";
    foreach ($applied as $name) {
        echo " - {$name}\n";
    }
    exit(0);
}

header('Content-Type: text/html; charset=utf-8');
$message = '';
$applied = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        $applied = $runner->applyAll();
        $message = $applied === [] ? 'No pending migrations.' : ('Applied: ' . implode(', ', $applied));
    } catch (Throwable $e) {
        http_response_code(500);
        $message = 'Migration failed: ' . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>ConfigFlow Migrations</title>
</head>
<body style="font-family: sans-serif; max-width: 820px; margin: 2rem auto;">
<h2>ConfigFlow Migration Runner</h2>
<p>This page applies pending SQL files from <code>/migrations</code>.</p>
<form method="post">
    <button type="submit">Apply pending migrations</button>
</form>
<?php if ($message !== ''): ?>
    <pre style="margin-top:1rem;padding:0.75rem;background:#f4f4f4;white-space:pre-wrap;"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></pre>
<?php endif; ?>
</body>
</html>
