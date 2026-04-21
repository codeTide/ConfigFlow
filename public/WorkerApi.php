<?php

declare(strict_types=1);

use ConfigFlow\Bot\Bootstrap;
use ConfigFlow\Bot\Database;
use ConfigFlow\Bot\WorkerApiApp;

require __DIR__ . '/../src/Bootstrap.php';
require __DIR__ . '/../src/Config.php';
require __DIR__ . '/../src/Support/ErrorRef.php';
require __DIR__ . '/../src/Support/PayloadSanitizer.php';
require __DIR__ . '/../src/Support/AppLogger.php';
require __DIR__ . '/../src/Support/AppError.php';
require __DIR__ . '/../src/ProvisioningProviderInterface.php';
require __DIR__ . '/../src/PGClient.php';
require __DIR__ . '/../src/PasarGuardProvisioningProvider.php';
require __DIR__ . '/../src/WorkerApiStore.php';
require __DIR__ . '/../src/Database.php';
require __DIR__ . '/../src/WorkerApiApp.php';

Bootstrap::loadEnv(__DIR__ . '/../.env');
$db = new Database();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '/';
$path = preg_replace('#^/+#', '/', $path);

$headers = [];
foreach ($_SERVER as $k => $v) {
    if (!is_string($k) || !str_starts_with($k, 'HTTP_')) {
        continue;
    }
    $name = str_replace('_', '-', substr($k, 5));
    $headers[$name] = is_string($v) ? $v : '';
}

$app = new WorkerApiApp($db);
$result = $app->handle($method, $path, $headers, (string) (file_get_contents('php://input') ?: ''));
http_response_code((int) ($result['status'] ?? 200));
echo json_encode($result['body'] ?? ['ok' => false, 'error' => ['code' => 'internal_error', 'message' => 'Invalid response']], JSON_UNESCAPED_UNICODE);
