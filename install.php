<?php

declare(strict_types=1);

function cf_validate(array $input): array
{
    $errors = [];

    $required = ['BOT_TOKEN', 'ADMIN_IDS', 'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER'];
    foreach ($required as $key) {
        if (trim((string) ($input[$key] ?? '')) === '') {
            $errors[] = "{$key} is required.";
        }
    }

    $token = (string) ($input['BOT_TOKEN'] ?? '');
    if ($token !== '' && !preg_match('/^[0-9]+:[A-Za-z0-9_-]+$/', $token)) {
        $errors[] = 'BOT_TOKEN format looks invalid.';
    }

    $port = (string) ($input['DB_PORT'] ?? '');
    if ($port !== '' && (!ctype_digit($port) || (int) $port < 1 || (int) $port > 65535)) {
        $errors[] = 'DB_PORT must be a valid port number.';
    }

    $webhookBase = trim((string) ($input['WEBHOOK_BASE_URL'] ?? ''));
    if ($webhookBase !== '' && filter_var($webhookBase, FILTER_VALIDATE_URL) === false) {
        $errors[] = 'WEBHOOK_BASE_URL must be a valid URL (or leave empty).';
    }

    return $errors;
}

function cf_write_env(array $values, string $path): void
{
    $lines = [];
    foreach ($values as $key => $value) {
        $lines[] = $key . '=' . str_replace(["\r", "\n"], '', (string) $value);
    }

    $content = implode(PHP_EOL, $lines) . PHP_EOL;
    if (file_put_contents($path, $content) === false) {
        throw new RuntimeException('Could not write .env file. Check file permissions.');
    }
}

function cf_set_webhook(string $botToken, string $webhookUrl): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'description' => 'PHP curl extension is not enabled.'];
    }

    $endpoint = "https://api.telegram.org/bot{$botToken}/setWebhook";
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['url' => $webhookUrl],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);

    $raw = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        return ['ok' => false, 'description' => $error !== '' ? $error : 'Unknown cURL error'];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return ['ok' => false, 'description' => "Unexpected Telegram response (HTTP {$status})."];
    }

    return $decoded;
}

function cf_install(array $input): array
{
    $env = [
        'BOT_TOKEN' => trim((string) ($input['BOT_TOKEN'] ?? '')),
        'BOT_USERNAME' => trim((string) ($input['BOT_USERNAME'] ?? '')),
        'ADMIN_IDS' => trim((string) ($input['ADMIN_IDS'] ?? '')),
        'DB_HOST' => trim((string) ($input['DB_HOST'] ?? '127.0.0.1')),
        'DB_PORT' => trim((string) ($input['DB_PORT'] ?? '3306')),
        'DB_NAME' => trim((string) ($input['DB_NAME'] ?? 'configflow')),
        'DB_USER' => trim((string) ($input['DB_USER'] ?? 'root')),
        'DB_PASS' => (string) ($input['DB_PASS'] ?? ''),
        'TETRAPAY_CREATE_URL' => trim((string) ($input['TETRAPAY_CREATE_URL'] ?? 'https://tetra98.com/api/create_order')),
        'TETRAPAY_VERIFY_URL' => trim((string) ($input['TETRAPAY_VERIFY_URL'] ?? 'https://tetra98.com/api/verify')),
    ];

    $errors = cf_validate($input);
    if ($errors !== []) {
        return ['ok' => false, 'messages' => $errors];
    }

    $messages = [];

    try {
        cf_write_env($env, __DIR__ . '/.env');
        $messages[] = '✓ .env file written.';

        ob_start();
        require __DIR__ . '/scripts/init_db.php';
        $initOut = trim((string) ob_get_clean());
        $messages[] = '✓ Database schema initialized.' . ($initOut !== '' ? " ({$initOut})" : '');
    } catch (Throwable $e) {
        return ['ok' => false, 'messages' => ['Installation failed: ' . $e->getMessage()]];
    }

    $webhookBase = trim((string) ($input['WEBHOOK_BASE_URL'] ?? ''));
    if ($webhookBase !== '') {
        $webhookUrl = rtrim($webhookBase, '/') . '/webhook.php';
        $res = cf_set_webhook($env['BOT_TOKEN'], $webhookUrl);
        if (($res['ok'] ?? false) === true) {
            $messages[] = '✓ Telegram webhook set: ' . $webhookUrl;
        } else {
            $messages[] = '⚠ Webhook not set: ' . (string) ($res['description'] ?? 'Unknown error');
        }
    } else {
        $messages[] = '⚠ Webhook skipped (WEBHOOK_BASE_URL is empty).';
    }

    return ['ok' => true, 'messages' => $messages];
}

function cf_prompt(string $label, ?string $default = null, bool $required = true): string
{
    $suffix = $default !== null && $default !== '' ? " [{$default}]" : '';
    while (true) {
        $line = readline($label . $suffix . ': ');
        $line = trim((string) $line);
        if ($line === '' && $default !== null) {
            $line = $default;
        }
        if ($line === '' && $required) {
            echo "Value is required.\n";
            continue;
        }
        return $line;
    }
}

if (PHP_SAPI === 'cli') {
    echo "\n=== ConfigFlow Installer (CLI) ===\n\n";

    $input = [
        'BOT_TOKEN' => cf_prompt('BOT_TOKEN'),
        'BOT_USERNAME' => cf_prompt('BOT_USERNAME', '', false),
        'ADMIN_IDS' => cf_prompt('ADMIN_IDS (comma separated)'),
        'DB_HOST' => cf_prompt('DB_HOST', '127.0.0.1'),
        'DB_PORT' => cf_prompt('DB_PORT', '3306'),
        'DB_NAME' => cf_prompt('DB_NAME', 'configflow'),
        'DB_USER' => cf_prompt('DB_USER', 'root'),
        'DB_PASS' => cf_prompt('DB_PASS', '', false),
        'TETRAPAY_CREATE_URL' => cf_prompt('TETRAPAY_CREATE_URL', 'https://tetra98.com/api/create_order'),
        'TETRAPAY_VERIFY_URL' => cf_prompt('TETRAPAY_VERIFY_URL', 'https://tetra98.com/api/verify'),
        'WEBHOOK_BASE_URL' => cf_prompt('WEBHOOK_BASE_URL (optional, ex: https://example.com)', '', false),
    ];

    $result = cf_install($input);
    foreach ($result['messages'] as $message) {
        echo $message . PHP_EOL;
    }

    exit($result['ok'] ? 0 : 1);
}

$result = null;
$values = [
    'BOT_TOKEN' => '',
    'BOT_USERNAME' => '',
    'ADMIN_IDS' => '',
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '3306',
    'DB_NAME' => 'configflow',
    'DB_USER' => 'root',
    'DB_PASS' => '',
    'TETRAPAY_CREATE_URL' => 'https://tetra98.com/api/create_order',
    'TETRAPAY_VERIFY_URL' => 'https://tetra98.com/api/verify',
    'WEBHOOK_BASE_URL' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($values) as $k) {
        $values[$k] = trim((string) ($_POST[$k] ?? $values[$k]));
    }
    $result = cf_install($values);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ConfigFlow Installer</title>
  <style>
    body{font-family:Tahoma,Arial,sans-serif;background:#0f172a;color:#e2e8f0;margin:0;padding:24px}
    .wrap{max-width:900px;margin:0 auto}
    .card{background:#111827;border:1px solid #334155;border-radius:14px;padding:20px;margin-bottom:16px}
    h1{margin:0 0 10px 0;font-size:28px}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .full{grid-column:1/-1}
    label{font-size:13px;display:block;margin-bottom:6px;color:#94a3b8}
    input{width:100%;padding:10px;border-radius:8px;border:1px solid #475569;background:#0b1220;color:#e2e8f0}
    .btn{background:#2563eb;color:#fff;border:none;padding:12px 16px;border-radius:10px;cursor:pointer;font-weight:bold}
    .ok{background:#052e16;border:1px solid #166534;color:#86efac;padding:10px;border-radius:10px;margin:8px 0}
    .err{background:#3f1d1d;border:1px solid #7f1d1d;color:#fecaca;padding:10px;border-radius:10px;margin:8px 0}
    .hint{font-size:12px;color:#94a3b8}
  </style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h1>⚙️ ConfigFlow Installer</h1>
    <p>Fill your bot and database settings, then click install.</p>
    <?php if ($result !== null): ?>
      <?php foreach ($result['messages'] as $message): ?>
        <div class="<?= $result['ok'] ? 'ok' : 'err' ?>"><?= htmlspecialchars($message) ?></div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <form method="post" class="card" id="installerForm">
    <div class="grid">
      <?php foreach ($values as $key => $val): ?>
        <div class="<?= in_array($key, ['BOT_TOKEN','ADMIN_IDS','WEBHOOK_BASE_URL','TETRAPAY_CREATE_URL','TETRAPAY_VERIFY_URL'], true) ? 'full' : '' ?>">
          <label for="<?= $key ?>"><?= $key ?></label>
          <input id="<?= $key ?>" name="<?= $key ?>" value="<?= htmlspecialchars($val) ?>" autocomplete="off">
        </div>
      <?php endforeach; ?>
    </div>

    <p class="hint">WEBHOOK_BASE_URL sample: https://example.com (installer will set /webhook.php automatically)</p>
    <button class="btn" type="submit">Install ConfigFlow</button>
  </form>
</div>
<script>
  document.getElementById('installerForm').addEventListener('submit', function (e) {
    const required = ['BOT_TOKEN','ADMIN_IDS','DB_HOST','DB_PORT','DB_NAME','DB_USER'];
    for (const name of required) {
      const el = document.querySelector(`[name="${name}"]`);
      if (!el || !el.value.trim()) {
        e.preventDefault();
        alert(name + ' is required');
        el && el.focus();
        return;
      }
    }
  });
</script>
</body>
</html>
