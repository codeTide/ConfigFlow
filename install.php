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

    return $errors;
}

function cf_write_env(array $values, string $path): void
{
    $lines = [];
    foreach ($values as $key => $value) {
        $lines[] = $key . '=' . str_replace(["\r", "\n"], '', (string) $value);
    }

    $content = implode(PHP_EOL, $lines) . PHP_EOL;
    $dir = dirname($path);
    if (!is_dir($dir) || !is_writable($dir)) {
        throw new RuntimeException('Could not write .env file. Project directory is not writable.');
    }

    set_error_handler(static function (): bool {
        return true;
    });
    $bytes = @file_put_contents($path, $content, LOCK_EX);
    restore_error_handler();

    if ($bytes === false) {
        throw new RuntimeException('Could not write .env file. Check file permissions (owner/group/chmod).');
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

function cf_runtime_user(): ?string
{
    if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
        $info = posix_getpwuid(posix_geteuid());
        $name = is_array($info) ? trim((string) ($info['name'] ?? '')) : '';
        if ($name !== '') {
            return $name;
        }
    }

    $fallback = trim((string) get_current_user());
    return $fallback !== '' ? $fallback : null;
}

/** @return string[] */
function cf_auto_fix_permissions(string $projectRoot): array
{
    $messages = [];
    $runtimeUser = cf_runtime_user();
    $targets = [$projectRoot, $projectRoot . '/.env'];

    $userIni = $projectRoot . '/.user.ini';
    if (is_file($userIni)) {
        $targets[] = $userIni;
    }

    foreach ($targets as $target) {
        if (!file_exists($target)) {
            continue;
        }

        $mode = 0644;
        if (is_dir($target)) {
            $mode = 0755;
        } elseif (str_ends_with($target, '/.env')) {
            $mode = 0600;
        }
        if (function_exists('chmod')) {
            if (@chmod($target, $mode)) {
                $messages[] = "✓ Permission set on {$target}";
            } else {
                $messages[] = "⚠ Could not chmod {$target}";
            }
        }

        if ($runtimeUser !== null && function_exists('chown')) {
            if (@chown($target, $runtimeUser)) {
                $messages[] = "✓ Owner set to {$runtimeUser} for {$target}";
            } else {
                $messages[] = "⚠ Could not chown {$target} to {$runtimeUser}";
            }
        }
    }

    return $messages;
}

function cf_detect_base_url(): string
{
    if (PHP_SAPI === 'cli') {
        return '';
    }

    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        return '';
    }

    $isHttps = false;
    $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
    if ($https !== '' && $https !== 'off') {
        $isHttps = true;
    }
    $xfp = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    if ($xfp === 'https') {
        $isHttps = true;
    }

    $scheme = $isHttps ? 'https' : 'http';
    return $scheme . '://' . $host;
}

function cf_install(array $input): array
{
    if (!function_exists('putenv')) {
        return ['ok' => false, 'messages' => ['Installation failed: putenv() is disabled in PHP. Please enable putenv to continue.']];
    }

    if (!is_file(__DIR__ . '/webhook.php')) {
        return ['ok' => false, 'messages' => ['Installation failed: webhook.php was not found in project root.']];
    }

    $env = [
        'BOT_TOKEN' => trim((string) ($input['BOT_TOKEN'] ?? '')),
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
        foreach (cf_auto_fix_permissions(__DIR__) as $permMsg) {
            $messages[] = $permMsg;
        }

        ob_start();
        require __DIR__ . '/scripts/InitDb.php';
        $initOut = trim((string) ob_get_clean());
        $messages[] = '✓ Database schema initialized.' . ($initOut !== '' ? " ({$initOut})" : '');
    } catch (Throwable $e) {
        return ['ok' => false, 'messages' => ['Installation failed: ' . $e->getMessage()]];
    }

    $webhookBase = cf_detect_base_url();
    if ($webhookBase !== '') {
        $webhookUrl = rtrim($webhookBase, '/') . '/webhook.php';
        $res = cf_set_webhook($env['BOT_TOKEN'], $webhookUrl);
        if (($res['ok'] ?? false) === true) {
            $messages[] = '✓ Telegram webhook set: ' . $webhookUrl;
            $messages[] = '✓ Webhook base URL auto-detected from current domain: ' . $webhookBase;
        } else {
            $messages[] = '⚠ Webhook not set: ' . (string) ($res['description'] ?? 'Unknown error');
        }
    } else {
        $messages[] = '⚠ Webhook skipped (could not auto-detect current domain in this environment).';
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
        'ADMIN_IDS' => cf_prompt('ADMIN_IDS (comma separated)'),
        'DB_HOST' => cf_prompt('DB_HOST', '127.0.0.1'),
        'DB_PORT' => cf_prompt('DB_PORT', '3306'),
        'DB_NAME' => cf_prompt('DB_NAME', 'configflow'),
        'DB_USER' => cf_prompt('DB_USER', 'root'),
        'DB_PASS' => cf_prompt('DB_PASS', '', false),
        'TETRAPAY_CREATE_URL' => cf_prompt('TETRAPAY_CREATE_URL', 'https://tetra98.com/api/create_order'),
        'TETRAPAY_VERIFY_URL' => cf_prompt('TETRAPAY_VERIFY_URL', 'https://tetra98.com/api/verify'),
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
    'ADMIN_IDS' => '',
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '3306',
    'DB_NAME' => 'configflow',
    'DB_USER' => 'root',
    'DB_PASS' => '',
    'TETRAPAY_CREATE_URL' => 'https://tetra98.com/api/create_order',
    'TETRAPAY_VERIFY_URL' => 'https://tetra98.com/api/verify',
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
    :root{
      --bg:#0f172a;--fg:#e2e8f0;--muted:#94a3b8;--card:#111827;--card-border:#334155;
      --input-bg:#0b1220;--input-border:#475569;--btn:#2563eb;
      --ok-bg:#052e16;--ok-border:#166534;--ok-fg:#86efac;
      --err-bg:#3f1d1d;--err-border:#7f1d1d;--err-fg:#fecaca;
    }
    body[data-theme="light"]{
      --bg:#f1f5f9;--fg:#0f172a;--muted:#334155;--card:#ffffff;--card-border:#cbd5e1;
      --input-bg:#ffffff;--input-border:#94a3b8;--btn:#1d4ed8;
      --ok-bg:#dcfce7;--ok-border:#16a34a;--ok-fg:#166534;
      --err-bg:#fee2e2;--err-border:#ef4444;--err-fg:#991b1b;
    }
    body{font-family:Tahoma,Arial,sans-serif;background:var(--bg);color:var(--fg);margin:0;padding:24px;transition:background .2s,color .2s}
    .wrap{max-width:900px;margin:0 auto}
    .card{background:var(--card);border:1px solid var(--card-border);border-radius:14px;padding:20px;margin-bottom:16px}
    h1{margin:0 0 10px 0;font-size:28px}
    .topbar{display:flex;align-items:center;justify-content:space-between;gap:12px}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .full{grid-column:1/-1}
    label{font-size:13px;display:block;margin-bottom:6px;color:var(--muted)}
    input{width:100%;padding:10px;border-radius:8px;border:1px solid var(--input-border);background:var(--input-bg);color:var(--fg)}
    .btn{background:var(--btn);color:#fff;border:none;padding:12px 16px;border-radius:10px;cursor:pointer;font-weight:bold}
    .theme-btn{background:transparent;color:var(--fg);border:1px solid var(--card-border);padding:8px 12px;border-radius:10px;cursor:pointer}
    .ok{background:var(--ok-bg);border:1px solid var(--ok-border);color:var(--ok-fg);padding:10px;border-radius:10px;margin:8px 0}
    .err{background:var(--err-bg);border:1px solid var(--err-border);color:var(--err-fg);padding:10px;border-radius:10px;margin:8px 0}
    .hint{font-size:12px;color:var(--muted)}
    @media (max-width:700px){.grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <div class="topbar">
      <h1>⚙️ ConfigFlow Installer</h1>
      <button type="button" id="themeToggle" class="theme-btn">🌙 Dark</button>
    </div>
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
        <div class="<?= in_array($key, ['BOT_TOKEN','ADMIN_IDS','TETRAPAY_CREATE_URL','TETRAPAY_VERIFY_URL'], true) ? 'full' : '' ?>">
          <label for="<?= $key ?>"><?= $key ?></label>
          <input
            id="<?= $key ?>"
            name="<?= $key ?>"
            value="<?= htmlspecialchars($val) ?>"
            autocomplete="off"
          >
        </div>
      <?php endforeach; ?>
    </div>

    <button class="btn" type="submit">Install ConfigFlow</button>
  </form>
</div>
<script>
  (function () {
    const saved = localStorage.getItem('cf_theme');
    const theme = saved === 'light' ? 'light' : 'dark';
    document.body.setAttribute('data-theme', theme);
    const btn = document.getElementById('themeToggle');
    const updateLabel = () => {
      const current = document.body.getAttribute('data-theme') || 'dark';
      btn.textContent = current === 'light' ? '🌙 Dark' : '☀️ Light';
    };
    updateLabel();
    btn.addEventListener('click', function () {
      const current = document.body.getAttribute('data-theme') || 'dark';
      const next = current === 'light' ? 'dark' : 'light';
      document.body.setAttribute('data-theme', next);
      localStorage.setItem('cf_theme', next);
      updateLabel();
    });
  })();

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
