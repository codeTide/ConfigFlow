<?php

declare(strict_types=1);

use ConfigFlow\Bot\Config;
use ConfigFlow\Bot\Database;
use ConfigFlow\Bot\Bootstrap;
use ConfigFlow\Bot\CallbackHandler;
use ConfigFlow\Bot\UiKeyboardFactory;
use ConfigFlow\Bot\MenuService;
use ConfigFlow\Bot\MessageHandler;
use ConfigFlow\Bot\PaymentGatewayService;
use ConfigFlow\Bot\PaymentMethodRepository;
use ConfigFlow\Bot\SettingsRepository;
use ConfigFlow\Bot\StartHandler;
use ConfigFlow\Bot\TelegramClient;
use ConfigFlow\Bot\UpdateRouter;
use ConfigFlow\Bot\Support\AppLogger;
use ConfigFlow\Bot\Support\ErrorRef;

require_once __DIR__ . '/src/Bootstrap.php';
require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/src/ProvisioningProviderInterface.php';
require_once __DIR__ . '/src/PGClient.php';
require_once __DIR__ . '/src/PasarGuardProvisioningProvider.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/KeyboardBuilder.php';
require_once __DIR__ . '/src/UiLabels.php';
require_once __DIR__ . '/src/UiJsonCatalog.php';
require_once __DIR__ . '/src/UiMessageRenderer.php';
require_once __DIR__ . '/src/UiKeyboardFactoryInterface.php';
require_once __DIR__ . '/src/InvalidInlineKeyboardException.php';
require_once __DIR__ . '/src/UiKeyboardFactory.php';
require_once __DIR__ . '/src/AmountNormalizer.php';
require_once __DIR__ . '/src/Support/ErrorRef.php';
require_once __DIR__ . '/src/Support/PayloadSanitizer.php';
require_once __DIR__ . '/src/Support/AppLogger.php';
require_once __DIR__ . '/src/Support/AppError.php';
require_once __DIR__ . '/src/MenuService.php';
require_once __DIR__ . '/src/SettingsRepository.php';
require_once __DIR__ . '/src/DatabaseBackupService.php';
require_once __DIR__ . '/src/ExchangeRateService.php';
require_once __DIR__ . '/src/TelegramClient.php';
require_once __DIR__ . '/src/StartHandler.php';
require_once __DIR__ . '/src/CallbackHandler.php';
require_once __DIR__ . '/src/Payments/PremiumVoucher/PremiumVoucherGateway.php';
require_once __DIR__ . '/src/MessageHandler.php';
require_once __DIR__ . '/src/PaymentGatewayService.php';
require_once __DIR__ . '/src/PaymentMethodRepository.php';
require_once __DIR__ . '/src/UpdateRouter.php';

Bootstrap::loadEnv(__DIR__ . '/.env');

$token = Config::botToken();
if ($token === '') {
    http_response_code(500);
    echo 'BOT_TOKEN is missing';
    exit;
}

$raw = file_get_contents('php://input');
$update = json_decode($raw ?: '{}', true);
if (!is_array($update)) {
    $update = [];
}

$database = new Database();
$telegram = new TelegramClient($token);
$settings = new SettingsRepository($database);
$paymentMethods = new PaymentMethodRepository($database);
$gateways = new PaymentGatewayService($settings, $paymentMethods);
$uiKeyboard = new UiKeyboardFactory();
$menus = new MenuService($settings, $database, $uiKeyboard);
$startHandler = new StartHandler($database, $telegram, $settings, $menus);
$callbackHandler = new CallbackHandler($database, $telegram, $settings, $menus, $gateways);
$messageHandler = new MessageHandler($database, $telegram, $settings, $menus, $gateways, $paymentMethods, $uiKeyboard);

$router = new UpdateRouter($startHandler, $callbackHandler, $messageHandler);
$logger = new AppLogger();
try {
    $router->route($update);
} catch (\Throwable $e) {
    $ref = $logger->log('critical', 'system', 'webhook_unhandled_exception', 'Unhandled exception in webhook router', [
        'stage' => 'webhook_route',
        'exception_class' => $e::class,
        'exception_message' => $e->getMessage(),
        'request_payload' => is_array($update) ? $update : [],
    ], ErrorRef::make('SYS'));
    $chatId = (int) (($update['message']['chat']['id'] ?? 0));
    if ($chatId > 0) {
        $telegram->sendMessage($chatId, (new \ConfigFlow\Bot\UiJsonCatalog())->get('messages.system.unexpected_error', [
            'error_ref' => $ref,
        ]));
    }
}

echo 'ok';
