<?php

declare(strict_types=1);

use ConfigFlow\Bot\Bootstrap;
use ConfigFlow\Bot\Config;
use ConfigFlow\Bot\Database;
use ConfigFlow\Bot\PGClient;
use ConfigFlow\Bot\TelegramClient;
use ConfigFlow\Bot\UiJsonCatalog;
use ConfigFlow\Bot\UiMessageRenderer;

require_once __DIR__ . '/../src/Bootstrap.php';
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/WorkerApiStore.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/PGClient.php';
require_once __DIR__ . '/../src/TelegramClient.php';
require_once __DIR__ . '/../src/UiJsonCatalog.php';
require_once __DIR__ . '/../src/UiMessageRenderer.php';

Bootstrap::loadEnv(dirname(__DIR__) . '/.env');

$database = new Database();
$telegramToken = Config::botToken();
$telegram = $telegramToken !== '' ? new TelegramClient($telegramToken) : null;
$renderer = new UiMessageRenderer(new UiJsonCatalog());
$deliveries = listPanelDeliveriesForStatusSync($database);
if ($deliveries === []) {
    fwrite(STDOUT, "No panel deliveries to sync.\n");
    exit(0);
}

$groups = groupPanelDeliveriesByConnection($deliveries);
$summary = syncPanelDeliveryStatuses($database, $groups, $telegram, $renderer);
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
function syncPanelDeliveryStatuses(Database $database, array $groups, ?TelegramClient $telegram, UiMessageRenderer $renderer): array
{
    $summary = [
        'connections' => count($groups),
        'deliveries_total' => 0,
        'deliveries_synced' => 0,
        'fetch_errors' => 0,
        'notifications_sent' => 0,
        'cleanups_done' => 0,
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
            $result = applyPanelUserStatusToDelivery($database, $delivery, $userMap, $client, $telegram, $renderer);
            $summary['notifications_sent'] += (int) ($result['notifications'] ?? 0);
            $summary['cleanups_done'] += (int) ($result['cleanups'] ?? 0);
            $summary['deliveries_synced']++;
        }
    }

    return $summary;
}

/**
 * @param array<string,mixed> $delivery
 * @param array<string,array<string,mixed>> $userMap
 */
/**
 * @return array{notifications:int,cleanups:int}
 */
function applyPanelUserStatusToDelivery(
    Database $database,
    array $delivery,
    array $userMap,
    PGClient $client,
    ?TelegramClient $telegram,
    UiMessageRenderer $renderer
): array
{
    $deliveryId = (int) ($delivery['delivery_id'] ?? 0);
    if ($deliveryId <= 0) {
        return ['notifications' => 0, 'cleanups' => 0];
    }

    $servicePublicId = trim((string) ($delivery['service_public_id'] ?? ''));
    if ($servicePublicId === '') {
        return ['notifications' => 0, 'cleanups' => 0];
    }
    $previousStatus = trim((string) ($delivery['lifecycle_status'] ?? 'active'));
    $userId = (int) ($delivery['user_id'] ?? 0);
    $serviceName = trim((string) ($delivery['service_name'] ?? 'سرویس'));
    $sourceType = trim((string) ($delivery['source_type'] ?? ''));
    $isTest = ((int) ($delivery['is_test'] ?? 0)) === 1;
    $cleanupDueAt = trim((string) ($delivery['cleanup_due_at'] ?? ''));
    $cleanedUpAt = trim((string) ($delivery['cleaned_up_at'] ?? ''));
    $alertsState = extractPanelAlertState((string) ($delivery['meta_json'] ?? ''));

    $panelUser = $userMap[$servicePublicId] ?? null;
    if (!is_array($panelUser)) {
        $nextStatus = 'deleted';
        $database->applyPanelUserStatusToDelivery(
            $deliveryId,
            $nextStatus,
            0,
            'panel_user_missing',
            ['panel_username' => $servicePublicId],
            null,
            null,
            null,
            $cleanedUpAt !== '' ? $cleanedUpAt : gmdate('Y-m-d H:i:s')
        );
        $notified = notifyOnStatusChange($telegram, $renderer, $userId, $servicePublicId, $serviceName, $isTest, $previousStatus, $nextStatus);
        return ['notifications' => $notified ? 1 : 0, 'cleanups' => 0];
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

    if ($isTest && in_array($status, ['expired', 'depleted'], true)) {
        $deleteRes = $client->deleteUser($servicePublicId);
        if (($deleteRes['success'] ?? false) === true) {
            $cleanupReason = $status === 'expired'
                ? 'test_auto_removed_after_expire'
                : 'test_auto_removed_after_depleted';
            $database->applyPanelUserStatusToDelivery(
                $deliveryId,
                'deleted',
                0,
                $cleanupReason,
                [
                    'panel_status' => $panelStatusRaw,
                    'panel_expire' => $expireRaw,
                    'panel_used_traffic' => $usedTraffic,
                    'panel_data_limit' => $dataLimit,
                    'panel_subscription_url' => $subscriptionUrl !== '' ? $subscriptionUrl : null,
                    'panel_username' => $servicePublicId,
                ],
                $nextSubLink,
                null,
                $cleanupReason,
                gmdate('Y-m-d H:i:s')
            );
            $notified = notifyCleanupResult($telegram, $renderer, $userId, $servicePublicId, $serviceName, 'test_removed');
            return ['notifications' => $notified ? 1 : 0, 'cleanups' => 1];
        }
    }

    $nextCleanupDueAt = null;
    $nextCleanupReason = null;
    $nextCleanedUpAt = null;
    if ($status === 'active') {
        $nextCleanupDueAt = null;
        $nextCleanupReason = null;
        $nextCleanedUpAt = null;
    } elseif (!$isTest && in_array($status, ['expired', 'depleted'], true)) {
        $nextCleanupDueAt = $cleanupDueAt !== '' ? $cleanupDueAt : gmdate('Y-m-d H:i:s', time() + (7 * 86400));
        $nextCleanupReason = $status === 'expired' ? 'grace_period_expired' : 'grace_period_depleted';
        $nextCleanedUpAt = null;

        if ($cleanupDueAt !== '' && $cleanedUpAt === '' && strtotime($cleanupDueAt) !== false && strtotime($cleanupDueAt) <= time()) {
            $deleteRes = $client->deleteUser($servicePublicId);
            if (($deleteRes['success'] ?? false) === true) {
                $database->applyPanelUserStatusToDelivery(
                    $deliveryId,
                    'deleted',
                    0,
                    'removed_after_7_day_grace',
                    [
                        'panel_status' => $panelStatusRaw,
                        'panel_expire' => $expireRaw,
                        'panel_used_traffic' => $usedTraffic,
                        'panel_data_limit' => $dataLimit,
                        'panel_subscription_url' => $subscriptionUrl !== '' ? $subscriptionUrl : null,
                        'panel_username' => $servicePublicId,
                    ],
                    $nextSubLink,
                    null,
                    'removed_after_7_day_grace',
                    gmdate('Y-m-d H:i:s')
                );
                $notified = notifyCleanupResult($telegram, $renderer, $userId, $servicePublicId, $serviceName, 'normal_removed_after_grace');
                return ['notifications' => $notified ? 1 : 0, 'cleanups' => 1];
            }
        }
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
        $nextSubLink,
        $nextCleanupDueAt,
        $nextCleanupReason,
        $nextCleanedUpAt
    );
    $notifications = 0;
    $notified = notifyOnStatusChange($telegram, $renderer, $userId, $servicePublicId, $serviceName, $isTest, $previousStatus, $status);
    if ($notified) {
        $notifications++;
    }

    if ($status === 'active' && !$isTest && $sourceType === 'panel') {
        $alertEval = evaluatePanelDeliveryAlerts(
            $telegram,
            $renderer,
            $userId,
            $serviceName,
            $servicePublicId,
            $usedTraffic,
            $dataLimit,
            $expireEpoch,
            $alertsState
        );
        $notifications += (int) ($alertEval['notifications'] ?? 0);
        $alertsState = is_array($alertEval['alerts']) ? $alertEval['alerts'] : $alertsState;
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
                'alerts' => $alertsState,
            ],
            $nextSubLink,
            $nextCleanupDueAt,
            $nextCleanupReason,
            $nextCleanedUpAt
        );
    }

    return ['notifications' => $notifications, 'cleanups' => 0];
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
    UiMessageRenderer $renderer,
    int $userId,
    string $servicePublicId,
    string $serviceName,
    bool $isTest,
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

    $message = buildStatusChangeMessage($renderer, $nextStatus, $servicePublicId, $serviceName, $isTest);
    if ($message === '') {
        return false;
    }

    $telegram->sendMessage($userId, $message);
    return true;
}

function buildStatusChangeMessage(UiMessageRenderer $renderer, string $nextStatus, string $servicePublicId, string $serviceName, bool $isTest): string
{
    $serviceName = $serviceName !== '' ? $serviceName : 'سرویس شما';
    $servicePublicId = $servicePublicId !== '' ? $servicePublicId : '-';
    $key = match ($nextStatus) {
        'depleted' => $isTest ? 'messages.user.panel_sync_notifications.depleted_test' : 'messages.user.panel_sync_notifications.depleted',
        'expired' => $isTest ? 'messages.user.panel_sync_notifications.expired_test' : 'messages.user.panel_sync_notifications.expired',
        'deleted' => $isTest ? 'messages.user.panel_sync_notifications.deleted_test' : 'messages.user.panel_sync_notifications.deleted',
        'disabled' => $isTest ? 'messages.user.panel_sync_notifications.disabled_test' : 'messages.user.panel_sync_notifications.disabled',
        default => '',
    };
    if ($key === '') {
        return '';
    }

    return $renderer->render($key, [
        'service_name' => $serviceName,
        'service_public_id' => $servicePublicId,
    ], ['service_name', 'service_public_id']);
}

function notifyCleanupResult(
    ?TelegramClient $telegram,
    UiMessageRenderer $renderer,
    int $userId,
    string $servicePublicId,
    string $serviceName,
    string $type
): bool {
    if ($telegram === null || $userId <= 0) {
        return false;
    }
    $key = match ($type) {
        'test_removed' => 'messages.user.panel_sync_notifications.cleanup_test_removed',
        'normal_removed_after_grace' => 'messages.user.panel_sync_notifications.cleanup_after_grace',
        default => '',
    };
    if ($key === '') {
        return false;
    }
    $message = $renderer->render($key, [
        'service_name' => $serviceName !== '' ? $serviceName : 'سرویس شما',
        'service_public_id' => $servicePublicId !== '' ? $servicePublicId : '-',
    ], ['service_name', 'service_public_id']);
    $telegram->sendMessage($userId, $message);
    return true;
}

/**
 * @return array<string,string|null>
 */
function extractPanelAlertState(string $metaJson): array
{
    if (trim($metaJson) === '') {
        return [];
    }
    $decoded = json_decode($metaJson, true);
    if (!is_array($decoded)) {
        return [];
    }
    $alerts = $decoded['alerts'] ?? null;
    if (!is_array($alerts)) {
        return [];
    }
    $state = [];
    foreach (['usage_80_sent_at', 'usage_95_sent_at', 'expire_24h_sent_at', 'expire_6h_sent_at'] as $key) {
        $val = $alerts[$key] ?? null;
        $state[$key] = is_string($val) && trim($val) !== '' ? trim($val) : null;
    }
    return $state;
}

/**
 * @param array<string,string|null> $alertsState
 * @return array{notifications:int,alerts:array<string,string|null>}
 */
function evaluatePanelDeliveryAlerts(
    ?TelegramClient $telegram,
    UiMessageRenderer $renderer,
    int $userId,
    string $serviceName,
    string $servicePublicId,
    int $usedTraffic,
    int $dataLimit,
    int $expireEpoch,
    array $alertsState
): array {
    $now = time();
    $sent = 0;
    $state = $alertsState;

    if ($dataLimit > 0) {
        $usageRatio = $usedTraffic / $dataLimit;
        if ($usageRatio >= 0.80 && empty($state['usage_80_sent_at'])) {
            if (sendPanelDeliveryAlert($telegram, $renderer, $userId, 'usage_80', $serviceName, $servicePublicId)) {
                $state['usage_80_sent_at'] = gmdate('Y-m-d H:i:s');
                $sent++;
            }
        }
        if ($usageRatio >= 0.95 && empty($state['usage_95_sent_at'])) {
            if (sendPanelDeliveryAlert($telegram, $renderer, $userId, 'usage_95', $serviceName, $servicePublicId)) {
                $state['usage_95_sent_at'] = gmdate('Y-m-d H:i:s');
                $sent++;
            }
        }
    }

    if ($expireEpoch > $now) {
        $remaining = $expireEpoch - $now;
        if ($remaining <= 86400 && empty($state['expire_24h_sent_at'])) {
            if (sendPanelDeliveryAlert($telegram, $renderer, $userId, 'expire_24h', $serviceName, $servicePublicId)) {
                $state['expire_24h_sent_at'] = gmdate('Y-m-d H:i:s');
                $sent++;
            }
        }
        if ($remaining <= 21600 && empty($state['expire_6h_sent_at'])) {
            if (sendPanelDeliveryAlert($telegram, $renderer, $userId, 'expire_6h', $serviceName, $servicePublicId)) {
                $state['expire_6h_sent_at'] = gmdate('Y-m-d H:i:s');
                $sent++;
            }
        }
    }

    return ['notifications' => $sent, 'alerts' => $state];
}

function sendPanelDeliveryAlert(
    ?TelegramClient $telegram,
    UiMessageRenderer $renderer,
    int $userId,
    string $alertType,
    string $serviceName,
    string $servicePublicId
): bool {
    if ($telegram === null || $userId <= 0) {
        return false;
    }
    $key = match ($alertType) {
        'usage_80' => 'messages.user.panel_sync_notifications.alert_usage_80',
        'usage_95' => 'messages.user.panel_sync_notifications.alert_usage_95',
        'expire_24h' => 'messages.user.panel_sync_notifications.alert_expire_24h',
        'expire_6h' => 'messages.user.panel_sync_notifications.alert_expire_6h',
        default => '',
    };
    if ($key === '') {
        return false;
    }
    $message = $renderer->render($key, [
        'service_name' => $serviceName !== '' ? $serviceName : 'سرویس شما',
        'service_public_id' => $servicePublicId !== '' ? $servicePublicId : '-',
    ], ['service_name', 'service_public_id']);
    $telegram->sendMessage($userId, $message);
    return true;
}
