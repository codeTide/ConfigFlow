<?php

declare(strict_types=1);

use ConfigFlow\Bot\Bootstrap;
use ConfigFlow\Bot\Database;
use ConfigFlow\Bot\PaymentMethodRepository;
use ConfigFlow\Bot\Payments\Nowpayments\NowpaymentsCallbackHandler;
use ConfigFlow\Bot\Support\AppLogger;
use ConfigFlow\Bot\Support\ErrorRef;

require_once __DIR__ . '/../../../src/Bootstrap.php';
require_once __DIR__ . '/../../../src/Config.php';
require_once __DIR__ . '/../../../src/Database.php';
require_once __DIR__ . '/../../../src/PaymentMethodRepository.php';
require_once __DIR__ . '/../../../src/Support/ErrorRef.php';
require_once __DIR__ . '/../../../src/Support/PayloadSanitizer.php';
require_once __DIR__ . '/../../../src/Support/AppLogger.php';
require_once __DIR__ . '/../../../src/Payments/Nowpayments/NowpaymentsCallbackHandler.php';

Bootstrap::loadEnv(__DIR__ . '/../../../.env');
header('Content-Type: application/json; charset=utf-8');

$logger = new AppLogger();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$raw = (string) file_get_contents('php://input');
$json = json_decode($raw, true);
$payload = is_array($json) ? $json : [];

$database = new Database();
$methods = new PaymentMethodRepository($database);
$handler = new NowpaymentsCallbackHandler($database, $methods);

try {
    $sig = (string) ($_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ?? '');
    if (!$handler->verifySignature($raw, $sig)) {
        $ref = $logger->log('warning', 'callback', 'nowpayments_invalid_signature', 'NOWPayments callback invalid signature', [
            'gateway' => 'nowpayments',
            'stage' => 'callback_verify',
            'request_payload' => $payload,
        ], ErrorRef::make('CB'));
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'invalid_signature', 'error_ref' => $ref], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $result = $handler->handle($payload);
    http_response_code(($result['ok'] ?? false) ? 200 : 400);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (\Throwable $e) {
    $ref = $logger->log('critical', 'callback', 'nowpayments_callback_unhandled_exception', 'Unhandled exception in NOWPayments callback', [
        'gateway' => 'nowpayments',
        'stage' => 'callback_entrypoint',
        'exception_class' => $e::class,
        'exception_message' => $e->getMessage(),
    ], ErrorRef::make('CB'));
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'internal_error', 'error_ref' => $ref], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
