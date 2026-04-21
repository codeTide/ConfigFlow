<?php

declare(strict_types=1);

use ConfigFlow\Bot\Bootstrap;
use ConfigFlow\Bot\Database;
use ConfigFlow\Bot\PaymentGatewayService;
use ConfigFlow\Bot\PaymentMethodRepository;
use ConfigFlow\Bot\Payments\Tetrapay\TetrapayCallbackHandler;
use ConfigFlow\Bot\Payments\Tetrapay\TetrapayGateway;
use ConfigFlow\Bot\SettingsRepository;
use ConfigFlow\Bot\Support\AppLogger;
use ConfigFlow\Bot\Support\ErrorRef;

require_once __DIR__ . '/../../../src/Bootstrap.php';
require_once __DIR__ . '/../../../src/Config.php';
require_once __DIR__ . '/../../../src/Database.php';
require_once __DIR__ . '/../../../src/SettingsRepository.php';
require_once __DIR__ . '/../../../src/PaymentMethodRepository.php';
require_once __DIR__ . '/../../../src/PaymentGatewayService.php';
require_once __DIR__ . '/../../../src/Support/ErrorRef.php';
require_once __DIR__ . '/../../../src/Support/PayloadSanitizer.php';
require_once __DIR__ . '/../../../src/Support/AppLogger.php';
require_once __DIR__ . '/../../../src/Support/AppError.php';
require_once __DIR__ . '/../../../src/Payments/Tetrapay/TetrapayGateway.php';
require_once __DIR__ . '/../../../src/Payments/Tetrapay/TetrapayCallbackHandler.php';

Bootstrap::loadEnv(__DIR__ . '/../../../.env');

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$json = json_decode((string) $raw, true);
$request = is_array($json) ? $json : $_REQUEST;

$database = new Database();
$settings = new SettingsRepository($database);
$methods = new PaymentMethodRepository($database);
$gatewayService = new PaymentGatewayService($settings, $methods);
$gateway = new TetrapayGateway($gatewayService);
$handler = new TetrapayCallbackHandler($database, $gateway);
$logger = new AppLogger();
try {
    $result = $handler->handle(is_array($request) ? $request : []);
    http_response_code(($result['ok'] ?? false) ? 200 : 400);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (\Throwable $e) {
    $ref = $logger->log('critical', 'callback', 'tetrapay_callback_unhandled_exception', 'Unhandled exception in Tetrapay callback', [
        'gateway' => 'tetrapay',
        'stage' => 'callback_entrypoint',
        'exception_class' => $e::class,
        'exception_message' => $e->getMessage(),
        'request_payload' => is_array($request) ? $request : [],
    ], ErrorRef::make('CB'));
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'internal_error', 'error_ref' => $ref], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
