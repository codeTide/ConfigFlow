<?php

declare(strict_types=1);

use ConfigFlow\Bot\Config;
use ConfigFlow\Bot\Database;
use ConfigFlow\Bot\StartHandler;
use ConfigFlow\Bot\TelegramClient;

require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/TelegramClient.php';
require_once __DIR__ . '/../src/StartHandler.php';

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
$handler = new StartHandler($database, $telegram);
$handler->handle($update);

echo 'ok';
