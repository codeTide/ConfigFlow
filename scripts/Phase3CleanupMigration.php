<?php

declare(strict_types=1);

use ConfigFlow\Bot\Bootstrap;
use ConfigFlow\Bot\Database;

require_once __DIR__ . '/../src/Bootstrap.php';
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/WorkerApiStore.php';
require_once __DIR__ . '/../src/Database.php';

Bootstrap::loadEnv(__DIR__ . '/../.env');

$rollback = in_array('--rollback', $argv, true);
$db = new Database();
$pdo = $db->pdo();

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("CREATE TABLE IF NOT EXISTS phase3_migration_meta (
    id INT PRIMARY KEY,
    status VARCHAR(32) NOT NULL,
    report_json TEXT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$pdo->exec("CREATE TABLE IF NOT EXISTS phase3_package_map (
    package_id BIGINT PRIMARY KEY,
    service_id BIGINT NOT NULL,
    tariff_id BIGINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$pdo->exec("CREATE TABLE IF NOT EXISTS phase3_provisioning_service_map (
    provisioning_service_id BIGINT PRIMARY KEY,
    service_id BIGINT NOT NULL,
    tariff_id BIGINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$pdo->exec("CREATE TABLE IF NOT EXISTS phase3_created_entities (
    entity_type VARCHAR(32) NOT NULL,
    entity_id BIGINT NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$pdo->exec("CREATE TABLE IF NOT EXISTS phase3_backup_configs (
    id BIGINT PRIMARY KEY,
    package_id BIGINT NULL,
    service_id BIGINT NULL,
    tariff_id BIGINT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$pdo->exec("CREATE TABLE IF NOT EXISTS phase3_backup_pending_orders (
    id BIGINT PRIMARY KEY,
    package_id BIGINT NULL,
    service_id BIGINT NULL,
    tariff_id BIGINT NULL,
    order_mode VARCHAR(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$pdo->exec("CREATE TABLE IF NOT EXISTS phase3_backup_payments (
    id BIGINT PRIMARY KEY,
    package_id BIGINT NULL,
    service_id BIGINT NULL,
    tariff_id BIGINT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$pdo->exec("CREATE TABLE IF NOT EXISTS phase3_backup_purchases (
    id BIGINT PRIMARY KEY,
    package_id BIGINT NULL,
    service_id BIGINT NULL,
    tariff_id BIGINT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$getMeta = static function () use ($pdo): ?array {
    $stmt = $pdo->query('SELECT id, status, report_json FROM phase3_migration_meta WHERE id = 1 LIMIT 1');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
};

if ($rollback) {
    $meta = $getMeta();
    if (!is_array($meta) || ($meta['status'] ?? '') !== 'migrated') {
        echo "No migrated state found; rollback skipped.\n";
        exit(0);
    }

    $pdo->beginTransaction();
    try {
        $pdo->exec('UPDATE configs c JOIN phase3_backup_configs b ON b.id = c.id SET c.package_id = b.package_id, c.service_id = b.service_id, c.tariff_id = b.tariff_id');
        $pdo->exec('UPDATE pending_orders o JOIN phase3_backup_pending_orders b ON b.id = o.id SET o.package_id = b.package_id, o.service_id = b.service_id, o.tariff_id = b.tariff_id, o.order_mode = b.order_mode');
        $pdo->exec('UPDATE payments p JOIN phase3_backup_payments b ON b.id = p.id SET p.package_id = b.package_id, p.service_id = b.service_id, p.tariff_id = b.tariff_id');
        $pdo->exec('UPDATE purchases p JOIN phase3_backup_purchases b ON b.id = p.id SET p.package_id = b.package_id, p.service_id = b.service_id, p.tariff_id = b.tariff_id');

        $svcIds = $pdo->query("SELECT entity_id FROM phase3_created_entities WHERE entity_type = 'service'")->fetchAll(PDO::FETCH_COLUMN);
        $tariffIds = $pdo->query("SELECT entity_id FROM phase3_created_entities WHERE entity_type = 'tariff'")->fetchAll(PDO::FETCH_COLUMN);

        if ($tariffIds !== []) {
            $pdo->exec('DELETE FROM service_tariff WHERE id IN (' . implode(',', array_map('intval', $tariffIds)) . ')');
        }
        if ($svcIds !== []) {
            $pdo->exec('DELETE FROM service WHERE id IN (' . implode(',', array_map('intval', $svcIds)) . ')');
        }

        $pdo->exec('TRUNCATE TABLE phase3_package_map');
        $pdo->exec('TRUNCATE TABLE phase3_provisioning_service_map');
        $pdo->exec('TRUNCATE TABLE phase3_created_entities');

        $stmt = $pdo->prepare('REPLACE INTO phase3_migration_meta (id, status, report_json, updated_at) VALUES (1, :status, :report_json, :updated_at)');
        $stmt->execute([
            'status' => 'rolled_back',
            'report_json' => json_encode(['rolled_back' => true], JSON_UNESCAPED_UNICODE),
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);

        $pdo->commit();
        echo "Phase3 rollback done.\n";
        exit(0);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        fwrite(STDERR, 'Rollback failed: ' . $e->getMessage() . "\n");
        exit(1);
    }
}

$meta = $getMeta();
if (is_array($meta) && ($meta['status'] ?? '') === 'migrated') {
    echo "Phase3 migration already applied.\n";
    echo (string) ($meta['report_json'] ?? '{}') . "\n";
    exit(0);
}

$pdo->beginTransaction();
try {
    $pdo->exec('TRUNCATE TABLE phase3_package_map');
    $pdo->exec('TRUNCATE TABLE phase3_provisioning_service_map');
    $pdo->exec('TRUNCATE TABLE phase3_created_entities');
    $pdo->exec('TRUNCATE TABLE phase3_backup_configs');
    $pdo->exec('TRUNCATE TABLE phase3_backup_pending_orders');
    $pdo->exec('TRUNCATE TABLE phase3_backup_payments');
    $pdo->exec('TRUNCATE TABLE phase3_backup_purchases');

    $pdo->exec('INSERT INTO phase3_backup_configs (id, package_id, service_id, tariff_id) SELECT id, package_id, service_id, tariff_id FROM configs');
    $pdo->exec('INSERT INTO phase3_backup_pending_orders (id, package_id, service_id, tariff_id, order_mode) SELECT id, package_id, service_id, tariff_id, order_mode FROM pending_orders');
    $pdo->exec('INSERT INTO phase3_backup_payments (id, package_id, service_id, tariff_id) SELECT id, package_id, service_id, tariff_id FROM payments');
    $pdo->exec('INSERT INTO phase3_backup_purchases (id, package_id, service_id, tariff_id) SELECT id, package_id, service_id, tariff_id FROM purchases');

    $settingsStmt = $pdo->prepare("SELECT `key`, `value` FROM settings WHERE `key` IN ('pg_base_url','pg_username','pg_password')");
    $settingsStmt->execute();
    $settings = [];
    foreach ($settingsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $settings[(string) $row['key']] = (string) $row['value'];
    }
    $panelBaseUrl = (string) ($settings['pg_base_url'] ?? '');
    $panelUsername = (string) ($settings['pg_username'] ?? '');
    $panelPassword = (string) ($settings['pg_password'] ?? '');

    $logEntity = $pdo->prepare('INSERT INTO phase3_created_entities (entity_type, entity_id, created_at) VALUES (:type,:id,:created_at)');

    $report = [
        'created_services_from_packages' => 0,
        'created_tariffs_from_packages' => 0,
        'created_services_from_provisioning' => 0,
        'created_tariffs_from_provisioning' => 0,
        'updated_configs' => 0,
        'updated_pending_orders' => 0,
        'updated_payments' => 0,
        'updated_purchases' => 0,
        'panel_created' => 0,
    ];

    $pkgRows = $pdo->query('SELECT id, type_id, name, volume_gb, duration_days, price FROM packages')->fetchAll(PDO::FETCH_ASSOC);
    $insSvc = $pdo->prepare('INSERT INTO service (type_id, name, mode, panel_provider, panel_base_url, panel_username, panel_password, is_active, created_at, updated_at) VALUES (:type_id,:name,:mode,:panel_provider,:panel_base_url,:panel_username,:panel_password,1,:created_at,:updated_at)');
    $insTariff = $pdo->prepare('INSERT INTO service_tariff (service_id,pricing_mode,volume_gb,duration_days,price,min_volume_gb,max_volume_gb,price_per_gb,duration_policy,is_active,created_at,updated_at) VALUES (:service_id,:pricing_mode,:volume_gb,:duration_days,:price,NULL,NULL,NULL,NULL,1,:created_at,:updated_at)');
    $insMap = $pdo->prepare('INSERT INTO phase3_package_map (package_id,service_id,tariff_id) VALUES (:package_id,:service_id,:tariff_id)');
    foreach ($pkgRows as $pkg) {
        $now = gmdate('Y-m-d H:i:s');
        $insSvc->execute([
            'type_id' => (int) $pkg['type_id'],
            'name' => (string) ('[Migrated] ' . ($pkg['name'] ?? 'Package ' . $pkg['id'])),
            'mode' => 'stock',
            'panel_provider' => null,
            'panel_base_url' => null,
            'panel_username' => null,
            'panel_password' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $serviceId = (int) $pdo->lastInsertId();
        $logEntity->execute(['type' => 'service', 'id' => $serviceId, 'created_at' => $now]);
        $report['created_services_from_packages']++;

        $insTariff->execute([
            'service_id' => $serviceId,
            'pricing_mode' => 'fixed',
            'volume_gb' => (float) $pkg['volume_gb'],
            'duration_days' => (int) $pkg['duration_days'],
            'price' => (int) $pkg['price'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $tariffId = (int) $pdo->lastInsertId();
        $logEntity->execute(['type' => 'tariff', 'id' => $tariffId, 'created_at' => $now]);
        $report['created_tariffs_from_packages']++;

        $insMap->execute([
            'package_id' => (int) $pkg['id'],
            'service_id' => $serviceId,
            'tariff_id' => $tariffId,
        ]);
    }

    $provExists = (bool) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'provisioning_services'")->fetchColumn();
    if ($provExists) {
        $typeId = (int) $pdo->query('SELECT id FROM config_types ORDER BY id ASC LIMIT 1')->fetchColumn();
        if ($typeId <= 0) {
            $now = gmdate('Y-m-d H:i:s');
            $pdo->prepare('INSERT INTO config_types (name, description, is_active) VALUES (:name,:description,1)')->execute([
                'name' => 'Migrated Services',
                'description' => 'Auto-created for phase3 migration',
            ]);
            $typeId = (int) $pdo->lastInsertId();
        }

        $provRows = $pdo->query('SELECT id,title,min_gb,max_gb,step_gb,price_per_gb,duration_policy,duration_days,provider_group_ids,is_active FROM provisioning_services')->fetchAll(PDO::FETCH_ASSOC);
        $insProvMap = $pdo->prepare('INSERT INTO phase3_provisioning_service_map (provisioning_service_id,service_id,tariff_id) VALUES (:legacy_id,:service_id,:tariff_id)');
        $insProvTariff = $pdo->prepare('INSERT INTO service_tariff (service_id,pricing_mode,volume_gb,duration_days,price,min_volume_gb,max_volume_gb,price_per_gb,duration_policy,is_active,created_at,updated_at) VALUES (:service_id,:pricing_mode,NULL,:duration_days,NULL,:min_volume_gb,:max_volume_gb,:price_per_gb,:duration_policy,1,:created_at,:updated_at)');
        foreach ($provRows as $legacy) {
            $now = gmdate('Y-m-d H:i:s');
            $insSvc->execute([
                'type_id' => $typeId,
                'name' => (string) ('[Migrated] ' . ($legacy['title'] ?? 'Provisioning ' . $legacy['id'])),
                'mode' => 'panel_auto',
                'panel_provider' => 'pasarguard',
                'panel_base_url' => $panelBaseUrl !== '' ? $panelBaseUrl : null,
                'panel_username' => $panelUsername !== '' ? $panelUsername : null,
                'panel_password' => $panelPassword !== '' ? $panelPassword : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $serviceId = (int) $pdo->lastInsertId();
            $logEntity->execute(['type' => 'service', 'id' => $serviceId, 'created_at' => $now]);
            $report['created_services_from_provisioning']++;

            $insProvTariff->execute([
                'service_id' => $serviceId,
                'pricing_mode' => 'per_gb',
                'duration_days' => (int) ($legacy['duration_days'] ?? 0),
                'min_volume_gb' => (float) ($legacy['min_gb'] ?? 0),
                'max_volume_gb' => (float) ($legacy['max_gb'] ?? 0),
                'price_per_gb' => (int) ($legacy['price_per_gb'] ?? 0),
                'duration_policy' => (string) (($legacy['duration_policy'] ?? 'fixed_days') !== '' ? $legacy['duration_policy'] : 'fixed_days'),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $tariffId = (int) $pdo->lastInsertId();
            $logEntity->execute(['type' => 'tariff', 'id' => $tariffId, 'created_at' => $now]);
            $report['created_tariffs_from_provisioning']++;

            $insProvMap->execute([
                'legacy_id' => (int) $legacy['id'],
                'service_id' => $serviceId,
                'tariff_id' => $tariffId,
            ]);
        }
    }

    $pdo->exec('UPDATE configs c JOIN phase3_package_map m ON m.package_id = c.package_id SET c.service_id = m.service_id, c.tariff_id = m.tariff_id WHERE c.package_id IS NOT NULL');
    $report['updated_configs'] += (int) $pdo->query('SELECT ROW_COUNT()')->fetchColumn();

    $pdo->exec('UPDATE payments p JOIN phase3_package_map m ON m.package_id = p.package_id SET p.service_id = m.service_id, p.tariff_id = m.tariff_id WHERE p.package_id IS NOT NULL AND (p.service_id IS NULL OR p.tariff_id IS NULL)');
    $report['updated_payments'] += (int) $pdo->query('SELECT ROW_COUNT()')->fetchColumn();

    $pdo->exec('UPDATE pending_orders o JOIN phase3_package_map m ON m.package_id = o.package_id SET o.service_id = m.service_id, o.tariff_id = m.tariff_id WHERE o.package_id IS NOT NULL AND (o.service_id IS NULL OR o.tariff_id IS NULL)');
    $report['updated_pending_orders'] += (int) $pdo->query('SELECT ROW_COUNT()')->fetchColumn();

    $pdo->exec('UPDATE purchases p JOIN phase3_package_map m ON m.package_id = p.package_id SET p.service_id = m.service_id, p.tariff_id = m.tariff_id WHERE p.package_id IS NOT NULL AND (p.service_id IS NULL OR p.tariff_id IS NULL)');
    $report['updated_purchases'] += (int) $pdo->query('SELECT ROW_COUNT()')->fetchColumn();

    if ($provExists) {
        $pdo->exec("UPDATE pending_orders o JOIN phase3_provisioning_service_map m ON m.provisioning_service_id = o.service_id SET o.service_id = m.service_id, o.tariff_id = m.tariff_id, o.order_mode = 'stock_only' WHERE o.order_mode = 'panel_only' AND (o.tariff_id IS NULL OR o.tariff_id = 0)");
        $report['updated_pending_orders'] += (int) $pdo->query('SELECT ROW_COUNT()')->fetchColumn();

        $pdo->exec('UPDATE payments p JOIN phase3_provisioning_service_map m ON m.provisioning_service_id = p.service_id SET p.service_id = m.service_id, p.tariff_id = m.tariff_id WHERE p.package_id IS NULL AND (p.tariff_id IS NULL OR p.tariff_id = 0)');
        $report['updated_payments'] += (int) $pdo->query('SELECT ROW_COUNT()')->fetchColumn();
    }

    $stmt = $pdo->prepare('REPLACE INTO phase3_migration_meta (id, status, report_json, updated_at) VALUES (1, :status, :report_json, :updated_at)');
    $stmt->execute([
        'status' => 'migrated',
        'report_json' => json_encode($report, JSON_UNESCAPED_UNICODE),
        'updated_at' => gmdate('Y-m-d H:i:s'),
    ]);

    $pdo->commit();

    echo "Phase3 migration done.\n";
    echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Phase3 migration failed: ' . $e->getMessage() . "\n");
    exit(1);
}
