<?php

declare(strict_types=1);

use ConfigFlow\Bot\Bootstrap;
use ConfigFlow\Bot\Config;
use ConfigFlow\Bot\Database;
use ConfigFlow\Bot\PGClient;
use ConfigFlow\Bot\TelegramClient;

require_once __DIR__ . '/../src/Bootstrap.php';
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/WorkerApiStore.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/PGClient.php';
require_once __DIR__ . '/../src/TelegramClient.php';

Bootstrap::loadEnv(dirname(__DIR__) . '/.env');

$database = new Database();
$telegramToken = Config::botToken();
$telegram = $telegramToken !== '' ? new TelegramClient($telegramToken) : null;
$deliveries = listPanelDeliveriesForStatusSync($database);
if ($deliveries === []) {
    fwrite(STDOUT, "No panel deliveries to sync.\n");
    exit(0);
}

$groups = groupPanelDeliveriesByConnection($deliveries);
$summary = syncPanelDeliveryStatuses($database, $groups, $telegram);
fwrite(STDOUT, json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);

/**
 * @return array<int,array<string,mixed>>
 */
function listPanelDeliveriesForStatusSync(Database $database): array
{
    return $database->listPanelDeliveriesForStatusSync();
}

/**
 * @param array<int,array<string,mixed>> $deliveries
 * @return array<string,array{connection:array<string,string>,deliveries:array<int,array<string,mixed>>}>
 */
function groupPanelDeliveriesByConnection(array $deliveries): array
{
    $groups = [];
    foreach ($deliveries as $delivery) {
        $baseUrl = trim((string) ($delivery['panel_base_url'] ?? ''));
        $username = trim((string) ($delivery['panel_username'] ?? ''));
        $password = (string) ($delivery['panel_password'] ?? '');
        if ($baseUrl === '' || $username === '' || $password === '') {
            continue;
        }

        $key = hash('sha256', $baseUrl . '|' . $username . '|' . $password);
        if (!isset($groups[$key])) {
            $groups[$key] = [
                'connection' => [
                    'base_url' => $baseUrl,
                    'username' => $username,
                    'password' => $password,
                ],
                'deliveries' => [],
            ];
        }
        $groups[$key]['deliveries'][] = $delivery;
    }

    return $groups;
}

/**
 * @param array<string,array{connection:array<string,string>,deliveries:array<int,array<string,mixed>>}> $groups
 * @return array<string,int>
 */
function syncPanelDeliveryStatuses(Database $database, array $groups, ?TelegramClient $telegram): array
{
    $summary = [
        'connections' => count($groups),
        'deliveries_total' => 0,
        'deliveries_synced' => 0,
        'fetch_errors' => 0,
        'notifications_sent' => 0,
    ];

    foreach ($groups as $group) {
        $conn = $group['connection'];
        $rows = $group['deliveries'];
        $summary['deliveries_total'] += count($rows);

        $client = new PGClient($conn['base_url'], $conn['username'], $conn['password']);
        $res = $client->getUsers(1000);
        if (($res['success'] ?? false) !== true) {
            $summary['fetch_errors']++;
            continue;
        }

        $panelUsers = is_array($res['data']['users'] ?? null) ? $res['data']['users'] : [];
        $userMap = [];
        foreach ($panelUsers as $panelUser) {
            $panelUsername = trim((string) ($panelUser['username'] ?? ''));
            if ($panelUsername === '') {
                continue;
            }
            $userMap[$panelUsername] = is_array($panelUser) ? $panelUser : [];
        }

        foreach ($rows as $delivery) {
            if (applyPanelUserStatusToDelivery($database, $delivery, $userMap, $telegram)) {
                $summary['notifications_sent']++;
            }
            $summary['deliveries_synced']++;
        }
    }

    return $summary;
}

/**
 * @param array<string,mixed> $delivery
 * @param array<string,array<string,mixed>> $userMap
 */
function applyPanelUserStatusToDelivery(Database $database, array $delivery, array $userMap, ?TelegramClient $telegram): bool
{
    $deliveryId = (int) ($delivery['delivery_id'] ?? 0);
    if ($deliveryId <= 0) {
        return false;
    }

    $servicePublicId = trim((string) ($delivery['service_public_id'] ?? ''));
    if ($servicePublicId === '') {
        return false;
    }
    $previousStatus = trim((string) ($delivery['lifecycle_status'] ?? 'active'));
    $userId = (int) ($delivery['user_id'] ?? 0);
    $serviceName = trim((string) ($delivery['service_name'] ?? 'سرویس'));

    $panelUser = $userMap[$servicePublicId] ?? null;
    if (!is_array($panelUser)) {
        $nextStatus = 'deleted';
        $database->applyPanelUserStatusToDelivery(
            $deliveryId,
            $nextStatus,
            0,
            'panel_user_missing',
            ['panel_username' => $servicePublicId]
        );
        return notifyOnStatusChange($telegram, $userId, $servicePublicId, $serviceName, $previousStatus, $nextStatus);
    }

    $expireRaw = $panelUser['expire'] ?? null;
    $expireEpoch = toEpoch($expireRaw);
    $usedTraffic = toInt($panelUser['used_traffic'] ?? 0);
    $dataLimit = toInt($panelUser['data_limit'] ?? 0);
    $panelStatusRaw = strtolower(trim((string) ($panelUser['status'] ?? 'active')));
    $subscriptionUrl = trim((string) ($panelUser['subscription_url'] ?? ''));

    $status = 'active';
    $isManageable = 1;
    $reason = null;

    if ($expireEpoch > 0 && $expireEpoch <= time()) {
        $status = 'expired';
        $isManageable = 0;
        $reason = 'panel_expired';
    } elseif ($dataLimit > 0 && $usedTraffic >= $dataLimit) {
        $status = 'depleted';
        $isManageable = 0;
        $reason = 'panel_quota_exhausted';
    } elseif (isPanelDisabled($panelStatusRaw)) {
        $status = 'disabled';
        $isManageable = 0;
        $reason = 'panel_disabled';
    }

    $subLinkMode = trim((string) ($delivery['sub_link_mode'] ?? 'proxy'));
    $nextSubLink = null;
    if ($subscriptionUrl !== '' && $subLinkMode === 'direct') {
        $nextSubLink = $subscriptionUrl;
    }

    $database->applyPanelUserStatusToDelivery(
        $deliveryId,
        $status,
        $isManageable,
        $reason,
        [
            'panel_status' => $panelStatusRaw,
            'panel_expire' => $expireRaw,
            'panel_used_traffic' => $usedTraffic,
            'panel_data_limit' => $dataLimit,
            'panel_subscription_url' => $subscriptionUrl !== '' ? $subscriptionUrl : null,
            'panel_username' => $servicePublicId,
        ],
        $nextSubLink
    );
    return notifyOnStatusChange($telegram, $userId, $servicePublicId, $serviceName, $previousStatus, $status);
}

/**
 * @param mixed $value
 */
function toEpoch(mixed $value): int
{
    if (is_int($value) || (is_string($value) && ctype_digit($value))) {
        return (int) $value;
    }
    if (is_string($value) && trim($value) !== '') {
        $ts = strtotime($value);
        if ($ts !== false) {
            return $ts;
        }
    }
    return 0;
}

/**
 * @param mixed $value
 */
function toInt(mixed $value): int
{
    if (is_int($value)) {
        return $value;
    }
    if (is_float($value)) {
        return (int) round($value);
    }
    if (is_string($value)) {
        if ($value === '') {
            return 0;
        }
        if (is_numeric($value)) {
            return (int) round((float) $value);
        }
    }
    return 0;
}

function isPanelDisabled(string $rawStatus): bool
{
    return in_array($rawStatus, ['disabled', 'inactive', 'off', '0', 'false'], true);
}

function notifyOnStatusChange(
    ?TelegramClient $telegram,
    int $userId,
    string $servicePublicId,
    string $serviceName,
    string $previousStatus,
    string $nextStatus
): bool {
    if ($telegram === null || $userId <= 0 || $previousStatus === $nextStatus) {
        return false;
    }

    $shouldNotify = match ($nextStatus) {
        'depleted' => $previousStatus !== 'depleted',
        'expired' => $previousStatus !== 'expired',
        'deleted' => $previousStatus !== 'deleted',
        'disabled' => $previousStatus !== 'disabled',
        default => false,
    };
    if (!$shouldNotify) {
        return false;
    }

    $message = buildStatusChangeMessage($nextStatus, $servicePublicId, $serviceName);
    if ($message === '') {
        return false;
    }

    $telegram->sendMessage($userId, $message);
    return true;
}

function buildStatusChangeMessage(string $nextStatus, string $servicePublicId, string $serviceName): string
{
    $serviceName = $serviceName !== '' ? $serviceName : 'سرویس شما';
    $servicePublicId = $servicePublicId !== '' ? $servicePublicId : '-';

    return match ($nextStatus) {
        'depleted' => "📦 سرویس شما به دلیل اتمام حجم غیرفعال شده است.\n\n🧩 نام سرویس: {$serviceName}\n🆔 شناسه سرویس: <code>{$servicePublicId}</code>\n\n<blockquote>💡 برای فعال‌سازی دوباره، از بخش خرید/تمدید اقدام کنید.</blockquote>",
        'expired' => "⏳ سرویس شما به دلیل اتمام زمان منقضی شده است.\n\n🧩 نام سرویس: {$serviceName}\n🆔 شناسه سرویس: <code>{$servicePublicId}</code>\n\n<blockquote>💡 برای ادامه استفاده، سرویس را تمدید کنید.</blockquote>",
        'deleted' => "🗑 سرویس شما دیگر در پنل پیدا نشد.\n\n🧩 نام سرویس: {$serviceName}\n🆔 شناسه سرویس: <code>{$servicePublicId}</code>\n\n<blockquote>💡 در صورت نیاز با پشتیبانی ارتباط بگیرید تا وضعیت سرویس بررسی شود.</blockquote>",
        'disabled' => "🚫 سرویس شما غیرفعال شده است.\n\n🧩 نام سرویس: {$serviceName}\n🆔 شناسه سرویس: <code>{$servicePublicId}</code>\n\n<blockquote>💡 برای بررسی علت غیرفعال‌سازی، با پشتیبانی در ارتباط باشید.</blockquote>",
        default => '',
    };
}
