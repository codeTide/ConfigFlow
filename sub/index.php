<?php

declare(strict_types=1);

use ConfigFlow\Bot\Bootstrap;
use ConfigFlow\Bot\Database;

require_once __DIR__ . '/../src/Bootstrap.php';
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/ProvisioningProviderInterface.php';
require_once __DIR__ . '/../src/PGClient.php';
require_once __DIR__ . '/../src/PasarGuardProvisioningProvider.php';
require_once __DIR__ . '/../src/WorkerApiStore.php';
require_once __DIR__ . '/../src/Database.php';

Bootstrap::loadEnv(__DIR__ . '/../.env');

header('Content-Type: text/plain; charset=utf-8');

$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '';
$prefix = '/sub/';
$token = '';
if (str_starts_with($path, $prefix)) {
    $token = substr($path, strlen($prefix));
}
if ($token === '' && isset($_GET['token'])) {
    $token = (string) $_GET['token'];
}
$token = preg_replace('/[^a-zA-Z0-9]/', '', $token) ?? '';
if ($token === '') {
    http_response_code(400);
    echo 'invalid token';
    exit;
}

$db = new Database();
$sourceSubLink = $db->getDeliverySubLinkByToken($token);
if ($sourceSubLink === null) {
    http_response_code(404);
    echo 'subscription not found';
    exit;
}

$ch = curl_init($sourceSubLink);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 3,
    CURLOPT_USERAGENT => 'ConfigFlow-SubProxy/1.0',
]);
$raw = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!is_string($raw) || $raw === '' || $httpCode >= 400) {
    http_response_code(502);
    echo 'upstream error';
    exit;
}

$decoded = base64_decode(trim($raw), true);
if ($decoded === false) {
    $decoded = $raw;
}

$parts = preg_split('/\s+/', trim($decoded)) ?: [];
$links = array_values(array_filter($parts, static fn(string $item): bool => preg_match('~^(vless|vmess|trojan|ss)://~i', $item) === 1));
$normalized = implode("\n", $links);

if (isset($_GET['raw']) && $_GET['raw'] === '1') {
    echo $normalized;
    exit;
}

echo base64_encode($normalized);
