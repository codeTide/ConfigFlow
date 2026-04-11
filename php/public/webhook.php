<?php

declare(strict_types=1);

use ConfigFlow\Bot\Config;
use ConfigFlow\Bot\Database;
use ConfigFlow\Bot\Bootstrap;
use ConfigFlow\Bot\CallbackHandler;
use ConfigFlow\Bot\MenuService;
use ConfigFlow\Bot\SettingsRepository;
use ConfigFlow\Bot\StartHandler;
use ConfigFlow\Bot\TelegramClient;
use ConfigFlow\Bot\UpdateRouter;

require_once __DIR__ . '/../src/Bootstrap.php';
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/KeyboardBuilder.php';
require_once __DIR__ . '/../src/MenuService.php';
require_once __DIR__ . '/../src/SettingsRepository.php';
require_once __DIR__ . '/../src/TelegramClient.php';
require_once __DIR__ . '/../src/StartHandler.php';
require_once __DIR__ . '/../src/CallbackHandler.php';
require_once __DIR__ . '/../src/UpdateRouter.php';

Bootstrap::loadEnv(__DIR__ . '/../.env');

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
$menus = new MenuService($settings, $database);
$startHandler = new StartHandler($database, $telegram, $settings, $menus);
$callbackHandler = new CallbackHandler($database, $telegram, $settings, $menus);

$router = new UpdateRouter($startHandler, $callbackHandler);
$router->route($update);

echo 'ok';
