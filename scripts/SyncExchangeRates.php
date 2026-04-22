<?php

declare(strict_types=1);

use ConfigFlow\Bot\Bootstrap;
use ConfigFlow\Bot\Database;

require_once __DIR__ . '/../src/Bootstrap.php';
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Database.php';

Bootstrap::loadEnv(dirname(__DIR__) . '/.env');

$lockPath = sys_get_temp_dir() . '/configflow_sync_exchange_rates.lock';
$lockHandle = fopen($lockPath, 'c+');
if ($lockHandle === false) {
    fwrite(STDERR, "Unable to open lock file: {$lockPath}\n");
    exit(1);
}
if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
    fwrite(STDOUT, "SyncExchangeRates is already running.\n");
    exit(0);
}

try {
    $payload = fetchWallexMarkets();
    $symbols = is_array($payload['result']['symbols'] ?? null) ? $payload['result']['symbols'] : [];
    $usdttmn = is_array($symbols['USDTTMN'] ?? null) ? $symbols['USDTTMN'] : null;
    $stats = is_array($usdttmn['stats'] ?? null) ? $usdttmn['stats'] : [];
    $lastPriceRaw = $stats['lastPrice'] ?? null;

    if (!is_numeric($lastPriceRaw)) {
        throw new RuntimeException('Invalid Wallex payload: result.symbols.USDTTMN.stats.lastPrice is missing or non-numeric.');
    }

    $price = (float) $lastPriceRaw;
    if ($price <= 0) {
        throw new RuntimeException('Invalid Wallex payload: USDTTMN lastPrice must be greater than zero.');
    }

    $database = new Database();
    $database->upsertExchangeRate(
        'wallex',
        'USDTTMN',
        $price,
        gmdate('Y-m-d H:i:s'),
        [
            'symbol' => 'USDTTMN',
            'stats' => [
                'lastPrice' => $lastPriceRaw,
            ],
        ]
    );

    fwrite(STDOUT, sprintf("Synced USDTTMN rate from Wallex: %.8f\n", $price));
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'SyncExchangeRates failed: ' . $e->getMessage() . "\n");
    exit(1);
} finally {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
}

function fetchWallexMarkets(): array
{
    $ch = curl_init('https://api.wallex.ir/v1/markets');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
        ],
    ]);

    $raw = curl_exec($ch);
    $curlErr = curl_error($ch);
    $curlErrno = curl_errno($ch);
    $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false || $curlErr !== '') {
        throw new RuntimeException('Wallex request failed: ' . $curlErr . ' (#' . $curlErrno . ')');
    }
    if ($httpStatus < 200 || $httpStatus >= 300) {
        throw new RuntimeException('Wallex request failed with HTTP status ' . $httpStatus . '.');
    }

    $decoded = json_decode((string) $raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid Wallex JSON response.');
    }

    return $decoded;
}
