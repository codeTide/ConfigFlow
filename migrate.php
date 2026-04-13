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
$isError = false;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        $applied = $runner->applyAll();
        $message = $applied === [] ? 'No pending migrations.' : ('Applied: ' . implode(', ', $applied));
    } catch (Throwable $e) {
        http_response_code(500);
        $message = 'Migration failed: ' . $e->getMessage();
        $isError = true;
    }
}
?>
<!doctype html>
<html lang="fa">
<head>
    <meta charset="utf-8">
    <title>ConfigFlow Migrations</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --bg: #f4f7fb;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --ok-bg: #ecfdf3;
            --ok-text: #166534;
            --err-bg: #fef2f2;
            --err-text: #991b1b;
            --border: #e2e8f0;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Tahoma, Arial, sans-serif;
            background: linear-gradient(180deg, #eef4ff 0%, var(--bg) 40%);
            color: var(--text);
        }
        .wrap {
            max-width: 860px;
            margin: 48px auto;
            padding: 0 16px;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.06);
            padding: 28px;
        }
        h1 {
            margin: 0 0 8px;
            font-size: 28px;
        }
        p {
            margin: 0 0 18px;
            color: var(--muted);
            line-height: 1.8;
        }
        .btn {
            appearance: none;
            border: none;
            border-radius: 10px;
            padding: 11px 18px;
            background: var(--primary);
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }
        .btn:hover { background: var(--primary-hover); }
        .notice {
            margin-top: 18px;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 14px;
            border: 1px solid var(--border);
            white-space: pre-wrap;
        }
        .notice.ok {
            background: var(--ok-bg);
            color: var(--ok-text);
            border-color: #bbf7d0;
        }
        .notice.err {
            background: var(--err-bg);
            color: var(--err-text);
            border-color: #fecaca;
        }
        .hint {
            margin-top: 14px;
            font-size: 13px;
            color: var(--muted);
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>🛠 اجرای مایگریشن‌های ConfigFlow</h1>
        <p>از این بخش می‌توانید مایگریشن‌های pending را از پوشه <code>/migrations</code> اجرا کنید.</p>
        <form method="post">
            <button class="btn" type="submit">اجرای مایگریشن‌ها</button>
        </form>
        <?php if ($message !== ''): ?>
            <div class="notice <?= $isError ? 'err' : 'ok' ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <div class="hint">پیشنهاد: بعد از هر آپدیت پروژه، یک‌بار این صفحه یا دستور <code>php migrate.php</code> را اجرا کنید.</div>
    </div>
</div>
</body>
</html>
