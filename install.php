<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function cf_auth_file(): string
{
    return __DIR__ . '/.env';
}

/** @return array{username:string,password:string} */
function cf_installer_credentials(): array
{
    $path = cf_auth_file();
    $username = 'admin';
    $password = 'admin';
    if (is_file($path)) {
        $raw = file_get_contents($path);
        if (is_string($raw) && $raw !== '') {
            if (preg_match('/^INSTALLER_USERNAME=(.*)$/m', $raw, $m1) === 1) {
                $u = trim((string) ($m1[1] ?? ''));
                if ($u !== '') {
                    $username = $u;
                }
            }
            if (preg_match('/^INSTALLER_PASSWORD=(.*)$/m', $raw, $m2) === 1) {
                $p = trim((string) ($m2[1] ?? ''));
                if ($p !== '') {
                    $password = $p;
                }
            }
        }
    }
    return ['username' => $username, 'password' => $password];
}

function cf_is_installer_authenticated(): bool
{
    if (PHP_SAPI === 'cli') {
        return true;
    }
    $auth = cf_installer_credentials();
    $user = (string) ($_SESSION['installer_user'] ?? '');
    $last = (int) ($_SESSION['installer_last'] ?? 0);
    $now = time();
    if ($user === '' || $last <= 0 || ($now - $last) > 1800) {
        unset($_SESSION['installer_user'], $_SESSION['installer_last']);
        return false;
    }
    if (!hash_equals($auth['username'], $user)) {
        return false;
    }
    $_SESSION['installer_last'] = $now;
    return true;
}

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

    $installerUser = trim((string) ($input['INSTALLER_USERNAME'] ?? ''));
    $installerPass = trim((string) ($input['INSTALLER_PASSWORD'] ?? ''));
    if ($installerUser === '' || $installerPass === '') {
        $errors[] = 'INSTALLER_USERNAME and INSTALLER_PASSWORD are required.';
    }
    if (mb_strlen($installerPass) < 4) {
        $errors[] = 'INSTALLER_PASSWORD must be at least 4 characters.';
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
            $chmodErr = '';
            set_error_handler(static function (int $_errno, string $errstr) use (&$chmodErr): bool {
                $chmodErr = $errstr;
                return true;
            });
            $chmodOk = chmod($target, $mode);
            restore_error_handler();
            if ($chmodOk) {
                $messages[] = "✓ Permission set on {$target}";
            } else {
                $extra = $chmodErr !== '' ? " ({$chmodErr})" : '';
                if (str_ends_with($target, '/.user.ini')) {
                    $extra .= $extra === '' ? ' (file may be immutable or panel-managed)' : ', file may be immutable or panel-managed';
                }
                $messages[] = "⚠ Could not chmod {$target}{$extra}";
            }
        }

        if ($runtimeUser !== null && function_exists('chown')) {
            $chownErr = '';
            set_error_handler(static function (int $_errno, string $errstr) use (&$chownErr): bool {
                $chownErr = $errstr;
                return true;
            });
            $chownOk = chown($target, $runtimeUser);
            restore_error_handler();
            if ($chownOk) {
                $messages[] = "✓ Owner set to {$runtimeUser} for {$target}";
            } else {
                $extra = $chownErr !== '' ? " ({$chownErr})" : '';
                if (str_ends_with($target, '/.user.ini')) {
                    $extra .= $extra === '' ? ' (file may be immutable or panel-managed)' : ', file may be immutable or panel-managed';
                }
                $messages[] = "⚠ Could not chown {$target} to {$runtimeUser}{$extra}";
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

function cf_has_existing_installation(string $root): bool
{
    $lockPath = $root . '/.install.lock';
    return is_file($lockPath);
}

function cf_mark_installed(string $root): void
{
    $lockPath = $root . '/.install.lock';
    $stamp = 'installed_at=' . gmdate('c') . PHP_EOL;
    @file_put_contents($lockPath, $stamp, LOCK_EX);
}

function cf_reset_database(array $env): void
{
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        (string) ($env['DB_HOST'] ?? '127.0.0.1'),
        (int) ($env['DB_PORT'] ?? 3306),
        (string) ($env['DB_NAME'] ?? '')
    );
    $pdo = new PDO($dsn, (string) ($env['DB_USER'] ?? ''), (string) ($env['DB_PASS'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach ($tables as $table) {
        $tableName = (string) $table;
        if ($tableName === '') {
            continue;
        }
        $pdo->exec('DROP TABLE IF EXISTS `' . str_replace('`', '``', $tableName) . '`');
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}

function cf_install(array $input): array
{
    $isLocked = cf_has_existing_installation(__DIR__);
    $allowReinstall = (string) ($input['ALLOW_REINSTALL'] ?? '') === '1';
    $reinstallMode = (string) ($input['REINSTALL_MODE'] ?? 'preserve');
    if (!in_array($reinstallMode, ['preserve', 'reset_db'], true)) {
        $reinstallMode = 'preserve';
    }

    if ($isLocked && !$allowReinstall) {
        return ['ok' => false, 'messages' => ['Installation blocked: this project is locked (.install.lock). Enable reinstall mode to continue.']];
    }
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
        'INSTALLER_USERNAME' => trim((string) ($input['INSTALLER_USERNAME'] ?? 'admin')),
        'INSTALLER_PASSWORD' => trim((string) ($input['INSTALLER_PASSWORD'] ?? 'admin')),
        'TETRAPAY_CREATE_URL' => trim((string) ($input['TETRAPAY_CREATE_URL'] ?? 'https://tetra98.com/api/create_order')),
        'TETRAPAY_VERIFY_URL' => trim((string) ($input['TETRAPAY_VERIFY_URL'] ?? 'https://tetra98.com/api/verify')),
    ];

    $errors = cf_validate($input);
    if ($errors !== []) {
        return ['ok' => false, 'messages' => $errors];
    }

    $messages = [];
    if ($isLocked && $allowReinstall) {
        $messages[] = '⚠ Reinstall mode is enabled.';
    }

    try {
        cf_write_env($env, __DIR__ . '/.env');
        $messages[] = '✓ .env file written.';
        foreach (cf_auto_fix_permissions(__DIR__) as $permMsg) {
            $messages[] = $permMsg;
        }

        if ($isLocked && $allowReinstall && $reinstallMode === 'reset_db') {
            cf_reset_database($env);
            $messages[] = '⚠ Database tables were dropped (reset_db mode).';
        } elseif ($isLocked && $allowReinstall) {
            $messages[] = '✓ Database data preserved (preserve mode).';
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

    cf_mark_installed(__DIR__);
    $messages[] = '✓ Installation lock created (.install.lock).';

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

    if (cf_has_existing_installation(__DIR__)) {
        fwrite(STDERR, "Installation is locked (.install.lock). Remove lock file first or use web installer reinstall mode.\n");
        exit(1);
    }

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
$authError = '';
$isAuthenticated = cf_is_installer_authenticated();
[$authUser, $authPass] = [cf_installer_credentials()['username'], cf_installer_credentials()['password']];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auth_action'])) {
    $action = (string) ($_POST['auth_action'] ?? '');
    if ($action === 'login') {
        $u = trim((string) ($_POST['auth_username'] ?? ''));
        $p = (string) ($_POST['auth_password'] ?? '');
        if (!hash_equals($authUser, $u) || !hash_equals($authPass, $p)) {
            $authError = 'Login failed: invalid username or password.';
        } else {
            $_SESSION['installer_user'] = $u;
            $_SESSION['installer_last'] = time();
            $isAuthenticated = true;
        }
    } elseif ($action === 'logout') {
        unset($_SESSION['installer_user'], $_SESSION['installer_last']);
        $isAuthenticated = false;
    }
}

$isInstalled = cf_has_existing_installation(__DIR__);
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
    'INSTALLER_USERNAME' => $authUser,
    'INSTALLER_PASSWORD' => $authPass,
    'ALLOW_REINSTALL' => '0',
    'REINSTALL_MODE' => 'preserve',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['auth_action']) && $isAuthenticated) {
    foreach (array_keys($values) as $k) {
        $values[$k] = trim((string) ($_POST[$k] ?? $values[$k]));
    }
    $result = cf_install($values);
    $isInstalled = cf_has_existing_installation(__DIR__);
}
$showInstalledCard = $isInstalled && !($result !== null && ($result['ok'] ?? false) === true);
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
    *{box-sizing:border-box}
    body{font-family:Tahoma,Arial,sans-serif;background:var(--bg);color:var(--fg);margin:0;padding:24px;transition:background .2s,color .2s}
    .wrap{max-width:900px;margin:0 auto}
    .card{background:var(--card);border:1px solid var(--card-border);border-radius:14px;padding:20px;margin-bottom:16px;box-shadow:0 8px 24px rgba(15,23,42,.06)}
    h1{margin:0 0 10px 0;font-size:28px}
    .topbar{display:flex;align-items:center;justify-content:space-between;gap:12px}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .full{grid-column:1/-1}
    label{font-size:12px;display:block;margin-bottom:6px;color:var(--muted);letter-spacing:.2px}
    input,select{width:100%;padding:11px 12px;border-radius:10px;border:1px solid var(--input-border);background:var(--input-bg);color:var(--fg)}
    input:focus,select:focus{outline:none;border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.2)}
    .btn{background:var(--btn);color:#fff;border:none;padding:12px 16px;border-radius:10px;cursor:pointer;font-weight:bold}
    .theme-btn{background:transparent;color:var(--fg);border:1px solid var(--card-border);padding:8px 12px;border-radius:10px;cursor:pointer}
    .ok{background:var(--ok-bg);border:1px solid var(--ok-border);color:var(--ok-fg);padding:10px;border-radius:10px;margin:8px 0}
    .warn{background:#422006;border:1px solid #92400e;color:#fed7aa;padding:10px;border-radius:10px;margin:8px 0}
    .err{background:var(--err-bg);border:1px solid var(--err-border);color:var(--err-fg);padding:10px;border-radius:10px;margin:8px 0}
    .hint{font-size:12px;color:var(--muted)}
    .inline{display:flex;align-items:center;gap:8px}
    fieldset{border:none;padding:0;margin:0}
    .disabled{opacity:.6;filter:grayscale(.15)}
    @media (max-width:700px){.grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <div class="topbar">
      <h1>⚙️ ConfigFlow Installer</h1>
      <div class="inline">
        <button type="button" id="themeToggle" class="theme-btn">🌙 Dark</button>
        <?php if ($isAuthenticated): ?>
          <form method="post" style="margin:0;">
            <input type="hidden" name="auth_action" value="logout">
            <button type="submit" class="theme-btn">Logout</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
    <p>Fill your bot and database settings, then click install.</p>
    <?php if ($authError !== ''): ?>
      <div class="err"><?= htmlspecialchars($authError) ?></div>
    <?php endif; ?>
    <?php if ($result !== null): ?>
      <?php foreach ($result['messages'] as $message): ?>
        <?php
          $boxClass = 'ok';
          if (str_starts_with((string) $message, '⚠')) {
              $boxClass = 'warn';
          } elseif (str_starts_with((string) $message, '✗') || str_starts_with((string) $message, 'Installation failed') || str_starts_with((string) $message, 'Installation blocked')) {
              $boxClass = 'err';
          }
        ?>
        <div class="<?= $boxClass ?>"><?= htmlspecialchars($message) ?></div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php if (!$isAuthenticated): ?>
    <div class="card">
      <h3 style="margin-top:0;">Installer login</h3>
      <form method="post" class="grid">
        <input type="hidden" name="auth_action" value="login">
        <div>
          <label for="auth_username">Username</label>
          <input id="auth_username" name="auth_username" autocomplete="off">
        </div>
        <div>
          <label for="auth_password">Password</label>
          <input id="auth_password" name="auth_password" type="password" autocomplete="off">
        </div>
        <div class="full" style="margin-top:8px;">
          <button class="btn" type="submit">Login</button>
        </div>
      </form>
      <p class="hint">Default first login is <code>admin / admin</code>. Change it from installer fields after login. Session auto-logout after 30 minutes of inactivity.</p>
    </div>
  <?php else: ?>
  <?php if ($showInstalledCard): ?>
    <div class="card">
      <div class="ok">ConfigFlow is already installed on this path (.install.lock found).</div>
      <p class="hint">You can still reinstall from this page by enabling reinstall mode below.</p>
    </div>
  <?php endif; ?>
  <form method="post" class="card" id="installerForm">
      <?php if ($isInstalled): ?>
      <div class="full" style="margin-bottom:12px;">
        <label class="inline"><input style="width:auto" id="allowReinstall" type="checkbox" name="ALLOW_REINSTALL" value="1"> Enable reinstall</label>
        <div id="reinstallAdvanced" style="display:none;margin-top:10px;">
        <label for="REINSTALL_MODE" style="margin-top:8px;">Reinstall mode</label>
        <select id="REINSTALL_MODE" name="REINSTALL_MODE" disabled>
          <option value="preserve">Preserve database data</option>
          <option value="reset_db">Reset database (drop all tables)</option>
        </select>
        </div>
      </div>
      <?php endif; ?>
      <fieldset id="mainFields" class="<?= $isInstalled ? 'disabled' : '' ?>" <?= $isInstalled ? 'disabled' : '' ?>>
      <div class="grid">
        <?php foreach ($values as $key => $val): ?>
          <?php if (in_array($key, ['ALLOW_REINSTALL','REINSTALL_MODE'], true)) { continue; } ?>
          <div class="<?= in_array($key, ['BOT_TOKEN','ADMIN_IDS','INSTALLER_USERNAME','INSTALLER_PASSWORD','TETRAPAY_CREATE_URL','TETRAPAY_VERIFY_URL'], true) ? 'full' : '' ?>">
            <label for="<?= $key ?>"><?= $key ?></label>
            <input
              id="<?= $key ?>"
              name="<?= $key ?>"
              value="<?= htmlspecialchars($val) ?>"
              type="<?= $key === 'INSTALLER_PASSWORD' ? 'password' : 'text' ?>"
              autocomplete="off"
            >
          </div>
        <?php endforeach; ?>
      </div>
      </fieldset>

      <div style="margin-top:16px;">
        <button class="btn" id="installBtn" type="submit">Install ConfigFlow</button>
      </div>
    </form>
  <?php endif; ?>
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

  const installerForm = document.getElementById('installerForm');
  const allowReinstall = document.getElementById('allowReinstall');
  const mainFields = document.getElementById('mainFields');
  const reinstallAdvanced = document.getElementById('reinstallAdvanced');
  const reinstallMode = document.getElementById('REINSTALL_MODE');
  const installBtn = document.getElementById('installBtn');
  if (allowReinstall && mainFields) {
    const syncState = () => {
      const enabled = allowReinstall.checked;
      mainFields.disabled = !enabled;
      mainFields.classList.toggle('disabled', !enabled);
      if (reinstallMode) reinstallMode.disabled = !enabled;
      if (installBtn) installBtn.disabled = !enabled;
      if (reinstallAdvanced) reinstallAdvanced.style.display = enabled ? 'block' : 'none';
    };
    syncState();
    allowReinstall.addEventListener('change', syncState);
  }

  if (installerForm) installerForm.addEventListener('submit', function (e) {
    const reinstallCheckbox = document.querySelector('[name="ALLOW_REINSTALL"]');
    if (reinstallCheckbox && !reinstallCheckbox.checked) {
      e.preventDefault();
      alert('Enable reinstall first');
      reinstallCheckbox.focus();
      return;
    }

    const required = ['BOT_TOKEN','ADMIN_IDS','DB_HOST','DB_PORT','DB_NAME','DB_USER','INSTALLER_USERNAME','INSTALLER_PASSWORD'];
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
