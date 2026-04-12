<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(400);
    echo "This installer must be run from CLI: php install.php\n";
    exit(1);
}

$color = static fn(string $code, string $text): string => "\033[{$code}m{$text}\033[0m";

function prompt(string $label, ?string $default = null, bool $required = true): string
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

function writeEnv(array $values, string $path): void
{
    $lines = [];
    foreach ($values as $key => $value) {
        $lines[] = $key . '=' . str_replace(["\r", "\n"], '', (string) $value);
    }
    $content = implode(PHP_EOL, $lines) . PHP_EOL;
    if (file_put_contents($path, $content) === false) {
        throw new RuntimeException('Could not write .env file');
    }
}

function setWebhook(string $botToken, string $webhookUrl): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'description' => 'curl extension is not installed'];
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
        return ['ok' => false, 'description' => "Unexpected response (HTTP {$status})"]; 
    }

    return $decoded;
}

echo $color('36', "\n╔══════════════════════════════════════════╗\n");
echo $color('36', "║        ConfigFlow PHP Installer          ║\n");
echo $color('36', "╚══════════════════════════════════════════╝\n\n");

echo "This wizard will:\n";
echo "  1) collect .env settings\n";
echo "  2) write .env\n";
echo "  3) initialize MySQL schema\n";
echo "  4) optionally set Telegram webhook\n\n";

$env = [];
$env['BOT_TOKEN'] = prompt('BOT_TOKEN');
$env['BOT_USERNAME'] = prompt('BOT_USERNAME', '', false);
$env['ADMIN_IDS'] = prompt('ADMIN_IDS (comma separated user IDs)');
$env['DB_HOST'] = prompt('DB_HOST', '127.0.0.1');
$env['DB_PORT'] = prompt('DB_PORT', '3306');
$env['DB_NAME'] = prompt('DB_NAME', 'configflow');
$env['DB_USER'] = prompt('DB_USER', 'root');
$env['DB_PASS'] = prompt('DB_PASS', '', false);
$env['TETRAPAY_CREATE_URL'] = prompt('TETRAPAY_CREATE_URL', 'https://tetra98.com/api/create_order');
$env['TETRAPAY_VERIFY_URL'] = prompt('TETRAPAY_VERIFY_URL', 'https://tetra98.com/api/verify');

$webhookBase = prompt('Public base URL (example: https://example.com) for webhook setup', '', false);

try {
    writeEnv($env, __DIR__ . '/.env');
    echo $color('32', "\n✓ .env created successfully\n");

    require __DIR__ . '/scripts/init_db.php';
    echo $color('32', "✓ Database schema initialized\n");
} catch (Throwable $e) {
    echo $color('31', "✗ Installation failed: {$e->getMessage()}\n");
    exit(1);
}

if ($webhookBase !== '') {
    $webhookUrl = rtrim($webhookBase, '/') . '/webhook.php';
    echo "\nSetting webhook to: {$webhookUrl}\n";
    $result = setWebhook($env['BOT_TOKEN'], $webhookUrl);
    if (($result['ok'] ?? false) === true) {
        echo $color('32', "✓ Webhook set successfully\n");
    } else {
        $message = (string) ($result['description'] ?? 'Unknown error');
        echo $color('33', "⚠ Webhook was not set: {$message}\n");
        echo "You can set it manually later.\n";
    }
} else {
    echo $color('33', "\n⚠ Webhook setup skipped (no base URL provided).\n");
}

echo $color('36', "\nDone. You can now run:\n");
echo "  php -S 0.0.0.0:8080\n";
echo "  php scripts/php_worker_runtime.php\n\n";
