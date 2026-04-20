<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

use PDO;

final class Database implements WorkerApiStore
{
    private const DELIVERY_MODE_STOCK_ONLY = 'stock_only';
    private const DELIVERY_MODE_PANEL_ONLY = 'panel_only';

    private PDO $pdo;

    public function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            Config::dbHost(),
            Config::dbPort(),
            Config::dbName()
        );

        $this->pdo = new PDO($dsn, Config::dbUser(), Config::dbPass(), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->ensureRuntimeMigrations();
    }

    private function ensureRuntimeMigrations(): void
    {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS free_test_service_rules (
                service_id BIGINT PRIMARY KEY,
                is_enabled TINYINT(1) NOT NULL DEFAULT 0,
                claim_mode ENUM('cooldown','once_until_reset') NOT NULL DEFAULT 'once_until_reset',
                cooldown_days INT NULL,
                max_claims INT NOT NULL DEFAULT 1,
                volume_gb DECIMAL(10,2) NULL,
                duration_days INT NULL,
                priority INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_free_test_service_enabled (is_enabled),
                INDEX idx_free_test_service_priority (priority)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        if ($this->tableExists('free_test_service_rules')) {
            $this->pdo->exec("ALTER TABLE free_test_service_rules ADD COLUMN IF NOT EXISTS volume_gb DECIMAL(10,2) NULL AFTER max_claims");
            $this->pdo->exec("ALTER TABLE free_test_service_rules ADD COLUMN IF NOT EXISTS duration_days INT NULL AFTER volume_gb");
            if ($this->columnExists('free_test_service_rules', 'default_volume_gb')) {
                $this->pdo->exec("UPDATE free_test_service_rules SET volume_gb = default_volume_gb WHERE volume_gb IS NULL AND default_volume_gb IS NOT NULL");
                $this->pdo->exec("ALTER TABLE free_test_service_rules DROP COLUMN IF EXISTS default_volume_gb");
            }
            if ($this->columnExists('free_test_service_rules', 'default_duration_days')) {
                $this->pdo->exec("UPDATE free_test_service_rules SET duration_days = default_duration_days WHERE duration_days IS NULL AND default_duration_days IS NOT NULL");
                $this->pdo->exec("ALTER TABLE free_test_service_rules DROP COLUMN IF EXISTS default_duration_days");
            }
        }
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS free_test_service_claims (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT NOT NULL,
                service_id BIGINT NOT NULL,
                purchase_id BIGINT NOT NULL,
                claimed_at DATETIME NOT NULL,
                INDEX idx_free_test_service_claims_user_service (user_id, service_id),
                INDEX idx_free_test_service_claims_service (service_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $this->pdo->exec("DROP TABLE IF EXISTS free_test_claims");
        $this->pdo->exec("DROP TABLE IF EXISTS free_test_tariff_rules");
        $this->pdo->exec("DROP TABLE IF EXISTS free_test_requests");
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS service (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                service_code VARCHAR(32) NOT NULL UNIQUE,
                name VARCHAR(255) NOT NULL,
                mode VARCHAR(32) NOT NULL DEFAULT 'stock',
                panel_provider VARCHAR(64) NULL,
                panel_base_url VARCHAR(255) NULL,
                panel_username VARCHAR(191) NULL,
                panel_password TEXT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_service_mode (mode),
                INDEX idx_service_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        if ($this->tableExists('service')) {
            $this->pdo->exec("ALTER TABLE service ADD COLUMN IF NOT EXISTS service_code VARCHAR(32) NULL AFTER id");
            $this->pdo->exec("ALTER TABLE service ADD COLUMN IF NOT EXISTS panel_provider VARCHAR(64) NULL AFTER mode");
            $this->pdo->exec("ALTER TABLE service ADD COLUMN IF NOT EXISTS panel_base_url VARCHAR(255) NULL AFTER panel_provider");
            $this->pdo->exec("ALTER TABLE service ADD COLUMN IF NOT EXISTS panel_username VARCHAR(191) NULL AFTER panel_base_url");
            $this->pdo->exec("ALTER TABLE service ADD COLUMN IF NOT EXISTS panel_password TEXT NULL AFTER panel_username");
            $this->pdo->exec("ALTER TABLE service DROP INDEX IF EXISTS idx_service_type");
            $this->pdo->exec("ALTER TABLE service DROP COLUMN IF EXISTS panel_ref");
            $this->pdo->exec("ALTER TABLE service DROP COLUMN IF EXISTS panel_id");
            $this->pdo->exec("ALTER TABLE service DROP INDEX IF EXISTS idx_service_panel");
            $this->pdo->exec("ALTER TABLE service ADD UNIQUE INDEX IF NOT EXISTS uniq_service_code (service_code)");
            $this->pdo->exec("UPDATE service SET service_code = CONCAT('SVC', id) WHERE service_code IS NULL OR TRIM(service_code) = ''");
        }
        $this->pdo->exec("DROP TABLE IF EXISTS panel");
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS service_tariff (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                service_id BIGINT NOT NULL,
                pricing_mode VARCHAR(32) NOT NULL DEFAULT 'fixed',
                volume_gb DECIMAL(10,2) NULL,
                duration_days INT NULL,
                price INT NULL,
                min_volume_gb DECIMAL(10,2) NULL,
                max_volume_gb DECIMAL(10,2) NULL,
                price_per_gb INT NULL,
                duration_policy VARCHAR(32) NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_service_tariff_service (service_id),
                INDEX idx_service_tariff_mode (pricing_mode),
                INDEX idx_service_tariff_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        if ($this->tableExists('service_tariff')) {
            $this->pdo->exec("ALTER TABLE service_tariff DROP COLUMN IF EXISTS title");
            $this->pdo->exec("ALTER TABLE service_tariff DROP COLUMN IF EXISTS step_volume_gb");
        }
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS service_stock_items (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                service_id BIGINT NOT NULL,
                tariff_id BIGINT NULL,
                inventory_bucket VARCHAR(32) NOT NULL DEFAULT 'sale',
                sub_link TEXT NOT NULL,
                config_link TEXT NULL,
                volume_gb DECIMAL(10,2) NULL,
                duration_days INT NULL,
                created_at DATETIME NOT NULL,
                reserved_payment_id BIGINT NULL,
                sold_to BIGINT NULL,
                purchase_id BIGINT NULL,
                sold_at DATETIME NULL,
                is_expired TINYINT(1) NOT NULL DEFAULT 0,
                INDEX idx_stock_service (service_id),
                INDEX idx_stock_tariff (tariff_id),
                INDEX idx_stock_bucket (inventory_bucket),
                INDEX idx_stock_available (service_id, tariff_id, inventory_bucket, sold_to, reserved_payment_id, is_expired),
                INDEX idx_stock_available_service (service_id, inventory_bucket, sold_to, reserved_payment_id, is_expired)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        if ($this->tableExists('service_stock_items')) {
            $this->pdo->exec("ALTER TABLE service_stock_items ADD COLUMN IF NOT EXISTS config_link TEXT NULL AFTER sub_link");
            if ($this->columnExists('service_stock_items', 'access_url')) {
                $this->pdo->exec("UPDATE service_stock_items SET config_link = access_url WHERE config_link IS NULL AND access_url IS NOT NULL");
                $this->pdo->exec("ALTER TABLE service_stock_items DROP COLUMN IF EXISTS access_url");
            }
            $this->pdo->exec("ALTER TABLE service_stock_items DROP COLUMN IF EXISTS config_uuid");
            $this->pdo->exec("ALTER TABLE service_stock_items DROP COLUMN IF EXISTS stock_item_uuid");
            $this->pdo->exec("ALTER TABLE service_stock_items DROP COLUMN IF EXISTS raw_payload");
            $this->pdo->exec("ALTER TABLE service_stock_items ADD INDEX IF NOT EXISTS idx_stock_available_service (service_id, inventory_bucket, sold_to, reserved_payment_id, is_expired)");
        }
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS user_service_deliveries (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                purchase_id BIGINT NOT NULL,
                user_id BIGINT NOT NULL,
                service_id BIGINT NOT NULL,
                tariff_id BIGINT NULL,
                source_type ENUM('stock','panel') NOT NULL,
                stock_item_id BIGINT NULL,
                sub_link TEXT NOT NULL,
                access_url TEXT NULL,
                stock_item_uuid VARCHAR(191) NULL,
                volume_gb DECIMAL(10,2) NULL,
                duration_days INT NULL,
                delivered_at DATETIME NOT NULL,
                meta_json LONGTEXT NULL,
                INDEX idx_deliveries_purchase (purchase_id),
                INDEX idx_deliveries_user (user_id),
                INDEX idx_deliveries_service (service_id, tariff_id),
                INDEX idx_deliveries_source (source_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        if ($this->tableExists('user_service_deliveries')) {
            $this->pdo->exec("ALTER TABLE user_service_deliveries MODIFY COLUMN tariff_id BIGINT NULL");
        }
        if ($this->tableExists('payments')) {
            $this->pdo->exec("ALTER TABLE payments ADD COLUMN IF NOT EXISTS service_id BIGINT NULL");
            $this->pdo->exec("ALTER TABLE payments ADD COLUMN IF NOT EXISTS tariff_id BIGINT NULL AFTER service_id");
            $this->pdo->exec("ALTER TABLE payments ADD INDEX IF NOT EXISTS idx_payments_service (service_id)");
            $this->pdo->exec("ALTER TABLE payments ADD INDEX IF NOT EXISTS idx_payments_tariff (tariff_id)");
            $legacyTariffColumn = 'pack' . 'age_id';
            $this->pdo->exec("ALTER TABLE payments DROP COLUMN IF EXISTS `{$legacyTariffColumn}`");
        }
        if ($this->tableExists('pending_orders')) {
            $this->pdo->exec("ALTER TABLE pending_orders ADD COLUMN IF NOT EXISTS tariff_id BIGINT NULL AFTER service_id");
            $this->pdo->exec("ALTER TABLE pending_orders ADD INDEX IF NOT EXISTS idx_pending_tariff (tariff_id)");
            $legacyTariffColumn = 'pack' . 'age_id';
            $this->pdo->exec("ALTER TABLE pending_orders DROP COLUMN IF EXISTS `{$legacyTariffColumn}`");
        }
        if ($this->tableExists('purchases')) {
            $this->pdo->exec("ALTER TABLE purchases ADD COLUMN IF NOT EXISTS service_id BIGINT NULL");
            $this->pdo->exec("ALTER TABLE purchases ADD COLUMN IF NOT EXISTS tariff_id BIGINT NULL AFTER service_id");
            $this->pdo->exec("ALTER TABLE purchases ADD INDEX IF NOT EXISTS idx_purchases_service (service_id)");
            $this->pdo->exec("ALTER TABLE purchases ADD INDEX IF NOT EXISTS idx_purchases_tariff (tariff_id)");
            $legacyTariffColumn = 'pack' . 'age_id';
            $legacyConfigColumn = 'con' . 'fig_id';
            $this->pdo->exec("ALTER TABLE purchases DROP COLUMN IF EXISTS `{$legacyTariffColumn}`");
            $this->pdo->exec("ALTER TABLE purchases DROP COLUMN IF EXISTS `{$legacyConfigColumn}`");
        }
        $legacyProvisionTable = 'provision' . 'ing_services';
        $this->pdo->exec("DROP TABLE IF EXISTS `{$legacyProvisionTable}`");
        $legacyTariffTable = 'pack' . 'ages';
        $legacyStockTable = 'con' . 'figs';
        $this->pdo->exec("DROP TABLE IF EXISTS `{$legacyTariffTable}`");
        $this->pdo->exec("DROP TABLE IF EXISTS `{$legacyStockTable}`");
        $legacyAgencyTable = 'agency_' . 'prices';
        $this->pdo->exec("DROP TABLE IF EXISTS `{$legacyAgencyTable}`");
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = :table_name'
        );
        $stmt->execute(['table_name' => $table]);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    private function columnExists(string $table, string $column): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = :table_name AND column_name = :column_name'
        );
        $stmt->execute([
            'table_name' => $table,
            'column_name' => $column,
        ]);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function ensureUser(array $fromUser): bool
    {
        $userId = (int) ($fromUser['id'] ?? 0);
        $fullName = trim((string) (($fromUser['first_name'] ?? '') . ' ' . ($fromUser['last_name'] ?? '')));
        $username = (string) ($fromUser['username'] ?? '');
        $now = gmdate('Y-m-d H:i:s');

        $select = $this->pdo->prepare('SELECT user_id FROM users WHERE user_id = :user_id');
        $select->execute(['user_id' => $userId]);
        $exists = (bool) $select->fetchColumn();

        if (!$exists) {
            $insert = $this->pdo->prepare(
                'INSERT INTO users (user_id, full_name, username, balance, joined_at, last_seen_at, first_start_notified, status, is_agent)
                 VALUES (:user_id, :full_name, :username, 0, :joined_at, :last_seen_at, 0, :status, 0)'
            );
            $insert->execute([
                'user_id' => $userId,
                'full_name' => $fullName,
                'username' => $username,
                'joined_at' => $now,
                'last_seen_at' => $now,
                'status' => 'unsafe',
            ]);

            return true;
        }

        $update = $this->pdo->prepare(
            'UPDATE users SET full_name = :full_name, username = :username, last_seen_at = :last_seen_at WHERE user_id = :user_id'
        );
        $update->execute([
            'full_name' => $fullName,
            'username' => $username,
            'last_seen_at' => $now,
            'user_id' => $userId,
        ]);

        return false;
    }

    public function userStatus(int $userId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT status FROM users WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);

        $row = $stmt->fetch();

        return $row['status'] ?? null;
    }

    public function getUser(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT user_id, full_name, username, balance, status, is_agent FROM users WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function isAdminUser(int $userId): bool
    {
        if (in_array($userId, Config::adminIds(), true)) {
            return true;
        }
        $stmt = $this->pdo->prepare('SELECT user_id FROM admin_users WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        return (bool) $stmt->fetchColumn();
    }

    public function listAdminUsers(): array
    {
        return $this->pdo->query(
            'SELECT user_id, added_by, added_at, permissions
             FROM admin_users
             ORDER BY added_at DESC'
        )->fetchAll();
    }

    public function upsertAdminUser(int $userId, int $addedBy, array $permissions): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO admin_users (user_id, added_by, added_at, permissions)
             VALUES (:user_id, :added_by, :added_at, :permissions)
             ON DUPLICATE KEY UPDATE permissions = VALUES(permissions), added_by = VALUES(added_by), added_at = VALUES(added_at)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'added_by' => $addedBy,
            'added_at' => gmdate('Y-m-d H:i:s'),
            'permissions' => json_encode($permissions, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function removeAdminUser(int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM admin_users WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
    }

    public function getAdminPermissions(int $userId): array
    {
        if (in_array($userId, Config::adminIds(), true)) {
            return ['full' => true];
        }
        $stmt = $this->pdo->prepare('SELECT permissions FROM admin_users WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $raw = (string) ($stmt->fetchColumn() ?: '{}');
        $perms = json_decode($raw, true);
        return is_array($perms) ? $perms : [];
    }

    public function setAdminPermission(int $userId, string $permKey, bool $enabled): void
    {
        $perms = $this->getAdminPermissions($userId);
        $perms[$permKey] = $enabled;
        $this->upsertAdminUser($userId, $userId, $perms);
    }

    public function listUserIdsForBroadcast(string $scope): array
    {
        if ($scope === 'customers') {
            $stmt = $this->pdo->query('SELECT DISTINCT user_id FROM purchases ORDER BY user_id ASC');
            return array_map(static fn ($r) => (int) $r['user_id'], $stmt->fetchAll());
        }
        if ($scope === 'agents') {
            $stmt = $this->pdo->query('SELECT user_id FROM users WHERE is_agent = 1 ORDER BY user_id ASC');
            return array_map(static fn ($r) => (int) $r['user_id'], $stmt->fetchAll());
        }
        if ($scope === 'admins') {
            $ids = Config::adminIds();
            foreach ($this->listAdminUsers() as $row) {
                $ids[] = (int) ($row['user_id'] ?? 0);
            }
            return array_values(array_unique(array_filter($ids)));
        }
        $stmt = $this->pdo->query('SELECT user_id FROM users ORDER BY user_id ASC');
        return array_map(static fn ($r) => (int) $r['user_id'], $stmt->fetchAll());
    }

    public function countUserPurchases(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM purchases WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    public function listUserPurchasesSummary(int $userId, int $limit = 8): array
    {
        $limit = max(1, min($limit, 20));
        $stmt = $this->pdo->prepare(
            'SELECT p.id, p.amount, p.created_at, p.is_test,
                    CONCAT(s.name, " / ", COALESCE(CAST(t.volume_gb AS CHAR), "dynamic")) AS tariff_name,
                    s.name AS service_name
             FROM purchases p
             LEFT JOIN service s ON s.id = p.service_id
             LEFT JOIN service_tariff t ON t.id = p.tariff_id
             WHERE p.user_id = :user_id
             ORDER BY p.id DESC
             LIMIT ' . $limit
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function getUserPurchaseForRenewal(int $userId, int $purchaseId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.id AS purchase_id, p.is_test, p.service_id, p.tariff_id,
                    CONCAT(s.name, " / ", COALESCE(CAST(t.volume_gb AS CHAR), "dynamic")) AS tariff_name,
                    s.name AS service_name
             FROM purchases p
             JOIN service_tariff t ON t.id = p.tariff_id
             JOIN service s ON s.id = p.service_id
             WHERE p.user_id = :user_id AND p.id = :purchase_id
             LIMIT 1'
        );
        $stmt->execute([
            'user_id' => $userId,
            'purchase_id' => $purchaseId,
        ]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function referralStats(int $userId): array
    {
        $refCountStmt = $this->pdo->prepare('SELECT COUNT(*) FROM referrals WHERE referrer_id = :user_id');
        $refCountStmt->execute(['user_id' => $userId]);
        $refCount = (int) $refCountStmt->fetchColumn();

        $purchaseStatsStmt = $this->pdo->prepare(
            'SELECT COUNT(p.id) AS purchase_count, COALESCE(SUM(p.amount), 0) AS total_amount
             FROM referrals r
             LEFT JOIN purchases p ON p.user_id = r.referee_id
             WHERE r.referrer_id = :user_id'
        );
        $purchaseStatsStmt->execute(['user_id' => $userId]);
        $purchaseStats = $purchaseStatsStmt->fetch();

        return [
            'total_referrals' => $refCount,
            'purchase_count' => (int) ($purchaseStats['purchase_count'] ?? 0),
            'total_purchase_amount' => (int) ($purchaseStats['total_amount'] ?? 0),
        ];
    }



    public function listPinnedMessages(): array
    {
        return $this->pdo->query('SELECT id, text, created_at FROM pinned_messages ORDER BY id ASC')->fetchAll();
    }

    public function getPinnedMessage(int $pinId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, text, created_at FROM pinned_messages WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $pinId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function addPinnedMessage(string $text): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO pinned_messages (text, created_at) VALUES (:text, :created_at)');
        $stmt->execute(['text' => trim($text), 'created_at' => gmdate('Y-m-d H:i:s')]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updatePinnedMessage(int $pinId, string $text): void
    {
        $stmt = $this->pdo->prepare('UPDATE pinned_messages SET text = :text WHERE id = :id');
        $stmt->execute(['text' => trim($text), 'id' => $pinId]);
    }

    public function deletePinnedMessage(int $pinId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM pinned_messages WHERE id = :id');
        $stmt->execute(['id' => $pinId]);
        $stmt2 = $this->pdo->prepare('DELETE FROM pinned_message_sends WHERE pin_id = :pin_id');
        $stmt2->execute(['pin_id' => $pinId]);
    }

    public function savePinnedSend(int $pinId, int $userId, int $messageId): void
    {
        $check = $this->pdo->prepare('SELECT id FROM pinned_message_sends WHERE pin_id = :pin_id AND user_id = :user_id LIMIT 1');
        $check->execute(['pin_id' => $pinId, 'user_id' => $userId]);
        $existingId = (int) ($check->fetchColumn() ?: 0);
        if ($existingId > 0) {
            $upd = $this->pdo->prepare('UPDATE pinned_message_sends SET message_id = :message_id WHERE id = :id');
            $upd->execute(['message_id' => $messageId, 'id' => $existingId]);
            return;
        }

        $stmt = $this->pdo->prepare('INSERT INTO pinned_message_sends (pin_id, user_id, message_id) VALUES (:pin_id, :user_id, :message_id)');
        $stmt->execute(['pin_id' => $pinId, 'user_id' => $userId, 'message_id' => $messageId]);
    }

    public function getPinnedSends(int $pinId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, pin_id, user_id, message_id FROM pinned_message_sends WHERE pin_id = :pin_id ORDER BY id ASC');
        $stmt->execute(['pin_id' => $pinId]);
        return $stmt->fetchAll();
    }

    public function deletePinnedSends(int $pinId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM pinned_message_sends WHERE pin_id = :pin_id');
        $stmt->execute(['pin_id' => $pinId]);
    }
    public function createAgencyRequest(int $userId, string $note): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO agency_requests (user_id, note, status, created_at)
             VALUES (:user_id, :note, :status, :created_at)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'note' => $note,
            'status' => 'pending',
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function listPendingAgencyRequests(int $limit = 30): array
    {
        return $this->listAgencyRequestsByStatus('pending', $limit, 0);
    }

    public function listAgencyRequestsByStatus(string $status, int $limit = 30, int $offset = 0): array
    {
        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, note, created_at
             FROM agency_requests
             WHERE status = :status
             ORDER BY id DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset
        );
        $stmt->execute(['status' => $status]);
        return $stmt->fetchAll();
    }

    public function countAgencyRequestsByStatus(string $status): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM agency_requests WHERE status = :status');
        $stmt->execute(['status' => $status]);
        return (int) $stmt->fetchColumn();
    }

    public function getAgencyRequestById(int $requestId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, note, status, created_at, reviewed_at, admin_note
             FROM agency_requests
             WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $requestId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function reviewAgencyRequest(int $requestId, bool $approve, ?string $adminNote = null): array
    {
        $this->pdo->beginTransaction();
        try {
            $lockStmt = $this->pdo->prepare('SELECT id, user_id, status FROM agency_requests WHERE id = :id LIMIT 1 FOR UPDATE');
            $lockStmt->execute(['id' => $requestId]);
            $row = $lockStmt->fetch();
            if (!is_array($row)) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'not_found'];
            }
            if (($row['status'] ?? '') !== 'pending') {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'already_reviewed'];
            }

            $status = $approve ? 'approved' : 'rejected';
            $update = $this->pdo->prepare(
                'UPDATE agency_requests
                 SET status = :status,
                     admin_note = :admin_note,
                     reviewed_at = :reviewed_at
                 WHERE id = :id'
            );
            $update->execute([
                'status' => $status,
                'admin_note' => $adminNote,
                'reviewed_at' => gmdate('Y-m-d H:i:s'),
                'id' => $requestId,
            ]);

            $this->pdo->commit();
            return ['ok' => true, 'status' => $status, 'user_id' => (int) $row['user_id']];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'error' => 'db_error'];
        }
    }

    public function setUserState(int $userId, string $stateName, array $payload = []): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO user_states (user_id, state_name, state_payload, updated_at)
             VALUES (:user_id, :state_name, :state_payload, :updated_at)
             ON DUPLICATE KEY UPDATE state_name = VALUES(state_name), state_payload = VALUES(state_payload), updated_at = VALUES(updated_at)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'state_name' => $stateName,
            'state_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    public function getUserState(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT state_name, state_payload FROM user_states WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return null;
        }

        $payload = json_decode((string) ($row['state_payload'] ?? '{}'), true);
        return [
            'state_name' => (string) ($row['state_name'] ?? ''),
            'payload' => is_array($payload) ? $payload : [],
        ];
    }

    public function clearUserState(int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM user_states WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
    }

    public function getActiveTypes(): array
    {
        return [
            ['id' => 1, 'name' => 'Services'],
        ];
    }

    public function listTypes(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Services',
                'description' => 'Service-scoped virtual group',
                'is_active' => 1,
            ],
        ];
    }

    public function getTypeById(int $typeId): ?array
    {
        if ($typeId <= 0) {
            return null;
        }

        return [
            'id' => $typeId,
            'name' => 'Services',
            'description' => 'Service-scoped virtual group',
            'is_active' => 1,
        ];
    }

    public function createType(string $name, string $description = ''): int
    {
        return 1;
    }

    public function updateTypeName(int $typeId, string $name): void
    {
        // no-op: type groups are removed in service-centric model.
    }

    public function updateTypeActive(int $typeId, bool $active): void
    {
        // no-op: type groups are removed in service-centric model.
    }

    public function deleteType(int $typeId): void
    {
        // no-op: type groups are removed in service-centric model.
    }

    public function countServicesByType(int $typeId): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM service');

        return (int) $stmt->fetchColumn();
    }

    public function countServices(): int
    {
        return $this->countServicesByType(0);
    }

    public function listServicesByType(int $typeId): array
    {
        $stmt = $this->pdo->query(
            'SELECT s.id, s.service_code, s.name, s.mode, s.panel_provider, s.panel_base_url, s.panel_username, s.panel_password, s.is_active
             FROM service s
             ORDER BY s.id DESC'
        );
        return $stmt->fetchAll();
    }

    public function listServices(): array
    {
        return $this->listServicesByType(0);
    }

    public function listActiveServicesByType(int $typeId): array
    {
        $stmt = $this->pdo->query(
            'SELECT s.id, s.service_code, s.name, s.mode, s.panel_provider, s.panel_base_url, s.panel_username, s.panel_password, s.is_active
             FROM service s
             WHERE s.is_active = 1
             ORDER BY s.id DESC'
        );
        return $stmt->fetchAll();
    }

    public function listActiveServices(): array
    {
        return $this->listActiveServicesByType(0);
    }

    /** @param array<string,mixed> $data */
    public function createService(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO service (service_code, name, mode, panel_provider, panel_base_url, panel_username, panel_password, is_active, created_at, updated_at)
             VALUES (:service_code, :name, :mode, :panel_provider, :panel_base_url, :panel_username, :panel_password, :is_active, :created_at, :updated_at)'
        );
        $now = gmdate('Y-m-d H:i:s');
        $stmt->execute([
            'service_code' => isset($data['service_code']) ? trim((string) $data['service_code']) : $this->generateServiceCode(),
            'name' => trim((string) ($data['name'] ?? '')),
            'mode' => (string) ($data['mode'] ?? 'stock'),
            'panel_provider' => isset($data['panel_provider']) ? trim((string) $data['panel_provider']) : null,
            'panel_base_url' => isset($data['panel_base_url']) ? trim((string) $data['panel_base_url']) : null,
            'panel_username' => isset($data['panel_username']) ? trim((string) $data['panel_username']) : null,
            'panel_password' => isset($data['panel_password']) ? trim((string) $data['panel_password']) : null,
            'is_active' => ((int) ($data['is_active'] ?? 1)) === 1 ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function getService(int $serviceId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.service_code, s.name, s.mode, s.panel_provider, s.panel_base_url, s.panel_username, s.panel_password, s.is_active
             FROM service s
             WHERE s.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $serviceId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function updateServiceActive(int $serviceId, bool $active): void
    {
        $stmt = $this->pdo->prepare('UPDATE service SET is_active = :active, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'active' => $active ? 1 : 0,
            'updated_at' => gmdate('Y-m-d H:i:s'),
            'id' => $serviceId,
        ]);
    }

    public function deleteService(int $serviceId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM service WHERE id = :id');
        $stmt->execute(['id' => $serviceId]);
    }

    public function serviceNameExists(string $name, ?int $excludeServiceId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM service WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name))';
        $params = ['name' => trim($name)];
        if ($excludeServiceId !== null && $excludeServiceId > 0) {
            $sql .= ' AND id <> :exclude_id';
            $params['exclude_id'] = $excludeServiceId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return ((int) $stmt->fetchColumn()) > 0;
    }

    /** @param array<string,mixed> $data */
    public function updateServiceBasic(int $serviceId, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE service
             SET name = :name, mode = :mode, panel_provider = :panel_provider, panel_base_url = :panel_base_url, panel_username = :panel_username, panel_password = :panel_password, is_active = :is_active, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'name' => trim((string) ($data['name'] ?? '')),
            'mode' => (string) ($data['mode'] ?? 'stock'),
            'panel_provider' => isset($data['panel_provider']) ? trim((string) $data['panel_provider']) : null,
            'panel_base_url' => isset($data['panel_base_url']) ? trim((string) $data['panel_base_url']) : null,
            'panel_username' => isset($data['panel_username']) ? trim((string) $data['panel_username']) : null,
            'panel_password' => isset($data['panel_password']) ? trim((string) $data['panel_password']) : null,
            'is_active' => ((int) ($data['is_active'] ?? 1)) === 1 ? 1 : 0,
            'updated_at' => gmdate('Y-m-d H:i:s'),
            'id' => $serviceId,
        ]);
    }

    public function listTariffsByService(int $serviceId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, service_id, pricing_mode, volume_gb, duration_days, price, min_volume_gb, max_volume_gb, price_per_gb, duration_policy, is_active
             FROM service_tariff
             WHERE service_id = :service_id
             ORDER BY id DESC'
        );
        $stmt->execute(['service_id' => $serviceId]);
        return $stmt->fetchAll();
    }

    public function getServiceTariff(int $tariffId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, service_id, pricing_mode, volume_gb, duration_days, price, min_volume_gb, max_volume_gb, price_per_gb, duration_policy, is_active
             FROM service_tariff
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $tariffId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function getServiceTariffForService(int $serviceId, int $tariffId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, service_id, pricing_mode, volume_gb, duration_days, price, min_volume_gb, max_volume_gb, price_per_gb, duration_policy, is_active
             FROM service_tariff
             WHERE id = :id AND service_id = :service_id AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute([
            'id' => $tariffId,
            'service_id' => $serviceId,
        ]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function listActiveTariffsByService(int $serviceId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, service_id, pricing_mode, volume_gb, duration_days, price, min_volume_gb, max_volume_gb, price_per_gb, duration_policy, is_active
             FROM service_tariff
             WHERE service_id = :service_id AND is_active = 1
             ORDER BY id DESC'
        );
        $stmt->execute(['service_id' => $serviceId]);
        return $stmt->fetchAll();
    }

    /** @param array<string,mixed> $data */
    public function createServiceTariff(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO service_tariff (
                service_id, pricing_mode, volume_gb, duration_days, price,
                min_volume_gb, max_volume_gb, price_per_gb, duration_policy, is_active, created_at, updated_at
             ) VALUES (
                :service_id, :pricing_mode, :volume_gb, :duration_days, :price,
                :min_volume_gb, :max_volume_gb, :price_per_gb, :duration_policy, :is_active, :created_at, :updated_at
             )'
        );
        $now = gmdate('Y-m-d H:i:s');
        $stmt->execute([
            'service_id' => (int) ($data['service_id'] ?? 0),
            'pricing_mode' => (string) ($data['pricing_mode'] ?? 'fixed'),
            'volume_gb' => isset($data['volume_gb']) ? (float) $data['volume_gb'] : null,
            'duration_days' => isset($data['duration_days']) ? (int) $data['duration_days'] : null,
            'price' => isset($data['price']) ? (int) $data['price'] : null,
            'min_volume_gb' => isset($data['min_volume_gb']) ? (float) $data['min_volume_gb'] : null,
            'max_volume_gb' => isset($data['max_volume_gb']) ? (float) $data['max_volume_gb'] : null,
            'price_per_gb' => isset($data['price_per_gb']) ? (int) $data['price_per_gb'] : null,
            'duration_policy' => isset($data['duration_policy']) ? (string) $data['duration_policy'] : null,
            'is_active' => ((int) ($data['is_active'] ?? 1)) === 1 ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string,mixed> $data */
    public function updateServiceTariff(int $tariffId, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE service_tariff
             SET pricing_mode = :pricing_mode, volume_gb = :volume_gb, duration_days = :duration_days, price = :price,
                 min_volume_gb = :min_volume_gb, max_volume_gb = :max_volume_gb,
                 price_per_gb = :price_per_gb, duration_policy = :duration_policy, is_active = :is_active, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'pricing_mode' => (string) ($data['pricing_mode'] ?? 'fixed'),
            'volume_gb' => isset($data['volume_gb']) ? (float) $data['volume_gb'] : null,
            'duration_days' => isset($data['duration_days']) ? (int) $data['duration_days'] : null,
            'price' => isset($data['price']) ? (int) $data['price'] : null,
            'min_volume_gb' => isset($data['min_volume_gb']) ? (float) $data['min_volume_gb'] : null,
            'max_volume_gb' => isset($data['max_volume_gb']) ? (float) $data['max_volume_gb'] : null,
            'price_per_gb' => isset($data['price_per_gb']) ? (int) $data['price_per_gb'] : null,
            'duration_policy' => isset($data['duration_policy']) ? (string) $data['duration_policy'] : null,
            'is_active' => ((int) ($data['is_active'] ?? 1)) === 1 ? 1 : 0,
            'updated_at' => gmdate('Y-m-d H:i:s'),
            'id' => $tariffId,
        ]);
    }

    public function deleteServiceTariff(int $tariffId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM service_tariff WHERE id = :id');
        $stmt->execute(['id' => $tariffId]);
    }

    public function countTariffsByService(int $serviceId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM service_tariff WHERE service_id = :service_id');
        $stmt->execute(['service_id' => $serviceId]);
        return (int) $stmt->fetchColumn();
    }

    public function countServicesWithTariffsByType(int $typeId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(DISTINCT s.id)
             FROM service s
             JOIN service_tariff t ON t.service_id = s.id AND t.is_active = 1
             WHERE s.is_active = 1'
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function countAvailableStockItemsByService(int $serviceId, ?int $tariffId = null): int
    {
        $sql = 'SELECT COUNT(*)
                FROM service_stock_items
                WHERE service_id = :service_id
                  AND sold_to IS NULL
                  AND reserved_payment_id IS NULL
                  AND is_expired = 0
                  AND inventory_bucket = \'sale\'';
        $params = ['service_id' => $serviceId];
        if ($tariffId !== null && $tariffId > 0) {
            $sql .= ' AND tariff_id = :tariff_id';
            $params['tariff_id'] = $tariffId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function listStockItemsByService(int $serviceId, ?int $tariffId = null, int $limit = 20, int $offset = 0): array
    {
        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);
        $sql = 'SELECT id, service_id, tariff_id, sold_to, is_expired, sub_link AS sub_link, created_at
                FROM service_stock_items
                WHERE service_id = :service_id';
        $params = ['service_id' => $serviceId];
        if ($tariffId !== null && $tariffId > 0) {
            $sql .= ' AND tariff_id = :tariff_id';
            $params['tariff_id'] = $tariffId;
        }
        $sql .= ' ORDER BY id DESC LIMIT ' . $limit . ' OFFSET ' . $offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countStockItemsByService(int $serviceId, ?int $tariffId = null): int
    {
        $sql = 'SELECT COUNT(*) FROM service_stock_items WHERE service_id = :service_id';
        $params = ['service_id' => $serviceId];
        if ($tariffId !== null && $tariffId > 0) {
            $sql .= ' AND tariff_id = :tariff_id';
            $params['tariff_id'] = $tariffId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function addStockItemForService(
        int $serviceId,
        ?int $tariffId,
        string $serviceName,
        string $stock_itemText,
        ?string $inquiryLink = null,
        string $inventoryBucket = 'sale',
        ?float $volumeGb = null,
        ?int $durationDays = null,
        ?string $configLink = null
    ): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO service_stock_items (
                service_id, tariff_id, inventory_bucket, sub_link, config_link, volume_gb, duration_days, created_at, reserved_payment_id, sold_to, purchase_id, sold_at, is_expired
             ) VALUES (
                :service_id, :tariff_id, :inventory_bucket, :sub_link, :config_link, :volume_gb, :duration_days, :created_at, NULL, NULL, NULL, NULL, 0
             )'
        );
        $stmt->execute([
            'service_id' => $serviceId,
            'tariff_id' => $tariffId !== null && $tariffId > 0 ? $tariffId : null,
            'inventory_bucket' => $inventoryBucket === 'free_test' ? 'free_test' : 'sale',
            'sub_link' => $inquiryLink !== null && trim($inquiryLink) !== '' ? trim($inquiryLink) : trim($stock_itemText),
            'config_link' => $configLink !== null && trim($configLink) !== '' ? trim($configLink) : null,
            'volume_gb' => $volumeGb,
            'duration_days' => $durationDays,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function getActiveTariffs(int $typeId): array
    {
        $stmt = $this->pdo->query(
            "SELECT t.id,
                    CONCAT(s.name, ' / ', COALESCE(CAST(t.volume_gb AS CHAR), 'dynamic')) AS name,
                    COALESCE(t.price, 0) AS price,
                    t.volume_gb,
                    t.duration_days
             FROM service_tariff t
             JOIN service s ON s.id = t.service_id
             WHERE s.is_active = 1 AND t.is_active = 1
             ORDER BY t.id ASC"
        );
        return $stmt->fetchAll();
    }

    public function listTariffs(int $typeId): array
    {
        $stmt = $this->pdo->query(
            "SELECT t.id,
                    CONCAT(s.name, ' / ', COALESCE(CAST(t.volume_gb AS CHAR), 'dynamic')) AS name,
                    COALESCE(t.price, 0) AS price,
                    t.volume_gb,
                    t.duration_days,
                    t.is_active AS active
             FROM service_tariff t
             JOIN service s ON s.id = t.service_id
             ORDER BY t.id DESC"
        );
        return $stmt->fetchAll();
    }

    public function countAvailableStockItemsByTariff(int $tariffId): int
    {
        $tariff = $this->getServiceTariff($tariffId);
        if (!is_array($tariff)) {
            return 0;
        }
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM service_stock_items
             WHERE service_id = :service_id
               AND (:tariff_id IS NULL OR tariff_id = :tariff_id)
               AND sold_to IS NULL
               AND reserved_payment_id IS NULL
               AND is_expired = 0
               AND inventory_bucket = 'sale'"
        );
        $stmt->execute(['service_id' => (int) $tariff['service_id'], 'tariff_id' => $tariffId]);
        return (int) $stmt->fetchColumn();
    }

    public function listStockItemsByTariff(int $tariffId, int $limit = 20, int $offset = 0): array
    {
        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);
        $stmt = $this->pdo->prepare(
            'SELECT id, sold_to, is_expired, sub_link AS sub_link, created_at
             FROM service_stock_items
             WHERE tariff_id = :tariff_id
             ORDER BY id DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset
        );
        $stmt->execute(['tariff_id' => $tariffId]);
        return $stmt->fetchAll();
    }

    public function countStockItemsByTariff(int $tariffId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM service_stock_items WHERE tariff_id = :tariff_id');
        $stmt->execute(['tariff_id' => $tariffId]);
        return (int) $stmt->fetchColumn();
    }

    public function countStockItemsByTariffFiltered(int $tariffId, string $status = 'all', ?string $query = null): int
    {
        [$where, $params] = $this->buildStockItemFilterSql($tariffId, $status, $query);
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM service_stock_items WHERE ' . $where);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function listStockItemsByTariffFiltered(
        int $tariffId,
        string $status = 'all',
        ?string $query = null,
        int $limit = 20,
        int $offset = 0
    ): array {
        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);
        [$where, $params] = $this->buildStockItemFilterSql($tariffId, $status, $query);
        $stmt = $this->pdo->prepare(
            'SELECT id, sold_to, is_expired, sub_link AS sub_link, created_at
             FROM service_stock_items
             WHERE ' . $where . '
             ORDER BY id DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function addStockItemForTariff(int $tariffId, string $serviceName, string $stock_itemText, ?string $inquiryLink = null): int
    {
        $tariff = $this->getServiceTariff($tariffId);
        if (!is_array($tariff)) {
            return 0;
        }
        return $this->addStockItemForService(
            (int) $tariff['service_id'],
            $tariffId,
            $serviceName,
            $stock_itemText,
            $inquiryLink,
            'sale'
        );
    }

    public function expireStockItem(int $stock_itemId): void
    {
        $stmt = $this->pdo->prepare('UPDATE service_stock_items SET is_expired = 1 WHERE id = :id');
        $stmt->execute(['id' => $stock_itemId]);
    }

    public function unexpireStockItem(int $stock_itemId): void
    {
        $stmt = $this->pdo->prepare('UPDATE service_stock_items SET is_expired = 0 WHERE id = :id');
        $stmt->execute(['id' => $stock_itemId]);
    }

    public function deleteStockItem(int $stock_itemId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM service_stock_items
             WHERE id = :id
               AND sold_to IS NULL
               AND reserved_payment_id IS NULL'
        );
        $stmt->execute(['id' => $stock_itemId]);
        return $stmt->rowCount() > 0;
    }

    public function getTariff(int $tariffId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT t.id,
                    CONCAT(s.name, ' / ', COALESCE(CAST(t.volume_gb AS CHAR), 'dynamic')) AS name,
                    COALESCE(t.price, 0) AS price,
                    t.volume_gb,
                    t.duration_days,
                    t.service_id
             FROM service_tariff t
             JOIN service s ON s.id = t.service_id
             WHERE t.id = :id
             LIMIT 1"
        );
        $stmt->execute(['id' => $tariffId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function listUsers(int $limit = 30): array
    {
        $limit = max(1, min($limit, 200));
        $stmt = $this->pdo->prepare(
            'SELECT user_id, full_name, username, balance, status, is_agent
             FROM users
             ORDER BY user_id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function listAllTariffs(): array
    {
        return $this->pdo->query(
            "SELECT t.id,
                    CONCAT(s.name, ' / ', COALESCE(CAST(t.volume_gb AS CHAR), 'dynamic')) AS name,
                    COALESCE(t.price, 0) AS price,
                    t.volume_gb,
                    t.duration_days,
                    t.is_active AS active
             FROM service_tariff t
             JOIN service s ON s.id = t.service_id
             ORDER BY t.id DESC"
        )->fetchAll();
    }

    public function getAgencyPrice(int $userId, int $tariffId): ?int
    {
        $pkg = $this->getTariff($tariffId);
        if (!is_array($pkg)) {
            return null;
        }
        $stmt = $this->pdo->prepare(
            'SELECT price FROM agency_service_prices
             WHERE user_id = :user_id AND service_id = :service_id AND tariff_id = :tariff_id
             LIMIT 1'
        );
        $stmt->execute([
            'user_id' => $userId,
            'service_id' => (int) ($pkg['service_id'] ?? 0),
            'tariff_id' => $tariffId,
        ]);
        $val = $stmt->fetchColumn();
        return $val === false ? null : (int) $val;
    }

    public function setAgencyPrice(int $userId, int $tariffId, int $price): void
    {
        $pkg = $this->getTariff($tariffId);
        if (!is_array($pkg)) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO agency_service_prices (user_id, service_id, tariff_id, price)
             VALUES (:user_id, :service_id, :tariff_id, :price)
             ON DUPLICATE KEY UPDATE price = VALUES(price)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'service_id' => (int) ($pkg['service_id'] ?? 0),
            'tariff_id' => $tariffId,
            'price' => $price,
        ]);
    }

    public function clearAgencyPrice(int $userId, int $tariffId): void
    {
        $pkg = $this->getTariff($tariffId);
        if (!is_array($pkg)) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'DELETE FROM agency_service_prices
             WHERE user_id = :user_id AND service_id = :service_id AND tariff_id = :tariff_id'
        );
        $stmt->execute([
            'user_id' => $userId,
            'service_id' => (int) ($pkg['service_id'] ?? 0),
            'tariff_id' => $tariffId,
        ]);
    }

    public function isWorkerApiEnabled(): bool
    {
        $stmt = $this->pdo->prepare("SELECT `value` FROM settings WHERE `key` = 'worker_api_enabled' LIMIT 1");
        $stmt->execute();
        return (string) ($stmt->fetchColumn() ?: '0') === '1';
    }

    public function workerApiKey(): string
    {
        $stmt = $this->pdo->prepare("SELECT `value` FROM settings WHERE `key` = 'worker_api_key' LIMIT 1");
        $stmt->execute();
        return trim((string) ($stmt->fetchColumn() ?: ''));
    }

    public function setUserStatus(int $userId, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET status = :status WHERE user_id = :user_id');
        $stmt->execute([
            'status' => $status,
            'user_id' => $userId,
        ]);
    }

    public function setUserAgent(int $userId, bool $isAgent): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET is_agent = :is_agent WHERE user_id = :user_id');
        $stmt->execute([
            'is_agent' => $isAgent ? 1 : 0,
            'user_id' => $userId,
        ]);
    }

    public function updateUserBalance(int $userId, int $amountDelta): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET balance = balance + :delta WHERE user_id = :user_id');
        $stmt->execute([
            'delta' => $amountDelta,
            'user_id' => $userId,
        ]);
    }

    private function buildStockItemFilterSql(int $tariffId, string $status, ?string $query): array
    {
        $where = ['tariff_id = :tariff_id'];
        $params = ['tariff_id' => $tariffId];

        if ($status === 'available') {
            $where[] = 'sold_to IS NULL';
            $where[] = 'reserved_payment_id IS NULL';
            $where[] = 'is_expired = 0';
        } elseif ($status === 'sold') {
            $where[] = 'sold_to IS NOT NULL';
        } elseif ($status === 'expired') {
            $where[] = 'is_expired = 1';
        }

        $q = trim((string) ($query ?? ''));
        if ($q !== '') {
            $where[] = '(sub_link LIKE :q OR config_link LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        return [implode(' AND ', $where), $params];
    }

    public function createPayment(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO payments (kind, user_id, service_id, tariff_id, amount, payment_method, gateway_ref, status, created_at)
             VALUES (:kind, :user_id, :service_id, :tariff_id, :amount, :payment_method, :gateway_ref, :status, :created_at)'
        );
        $stmt->execute([
            'kind' => $data['kind'],
            'user_id' => $data['user_id'],
            'service_id' => $data['service_id'] ?? null,
            'tariff_id' => $data['tariff_id'] ?? null,
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'gateway_ref' => $data['gateway_ref'] ?? null,
            'status' => $data['status'],
            'created_at' => $data['created_at'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function walletPayTariff(int $userId, int $tariffId): array
    {
        return ['ok' => false, 'error' => 'tariff_flow_removed'];
    }

    public function walletPayPanelService(int $userId, int $serviceId, float $selectedVolumeGb): array
    {
        $service = $this->getProvisioningService($serviceId);
        if (!is_array($service)) {
            return ['ok' => false, 'error' => 'service_not_found'];
        }
        if (!$this->validatePanelServiceVolume($service, $selectedVolumeGb)) {
            return ['ok' => false, 'error' => 'invalid_volume'];
        }

        $amount = $this->calculatePanelServiceAmount($service, $selectedVolumeGb);
        $this->pdo->beginTransaction();
        try {
            $user = $this->getUser($userId);
            $balance = (int) ($user['balance'] ?? 0);
            if ($balance < $amount) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'insufficient_balance', 'amount' => $amount, 'balance' => $balance];
            }

            $newBalance = $balance - $amount;
            $update = $this->pdo->prepare('UPDATE users SET balance = :balance WHERE user_id = :user_id');
            $update->execute(['balance' => $newBalance, 'user_id' => $userId]);

            $paymentId = $this->createPayment([
                'kind' => 'purchase',
                'user_id' => $userId,
                'service_id' => $serviceId,
                'amount' => $amount,
                'payment_method' => 'wallet',
                'status' => 'paid',
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);

            $pendingId = $this->createPendingOrder([
                'user_id' => $userId,
                'order_mode' => self::DELIVERY_MODE_PANEL_ONLY,
                'service_id' => $serviceId,
                'selected_volume_gb' => $selectedVolumeGb,
                'computed_amount' => $amount,
                'payment_id' => $paymentId,
                'amount' => $amount,
                'payment_method' => 'wallet',
                'created_at' => gmdate('Y-m-d H:i:s'),
                'status' => 'paid_waiting_delivery',
            ]);

            $this->pdo->commit();
            return [
                'ok' => true,
                'payment_id' => $paymentId,
                'pending_order_id' => $pendingId,
                'amount' => $amount,
                'new_balance' => $newBalance,
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'error' => 'db_error'];
        }
    }

    public function walletPayServiceTariff(int $userId, int $serviceId, ?int $tariffId = null, ?float $selectedVolumeGb = null): array
    {
        if ($tariffId === null || $tariffId <= 0) {
            $tariff = $this->getServiceTariff($serviceId);
            if (!is_array($tariff)) {
                return ['ok' => false, 'error' => 'tariff_not_found'];
            }
            $serviceId = (int) ($tariff['service_id'] ?? 0);
            $tariffId = (int) ($tariff['id'] ?? 0);
        }
        $service = $this->getService($serviceId);
        if (!is_array($service) || (int) ($service['is_active'] ?? 0) !== 1) {
            return ['ok' => false, 'error' => 'service_not_found'];
        }
        $tariff = $this->getServiceTariffForService($serviceId, $tariffId);
        if (!is_array($tariff)) {
            return ['ok' => false, 'error' => 'tariff_not_found'];
        }
        $pricingMode = (string) ($tariff['pricing_mode'] ?? 'fixed');
        $volumeForOrder = null;
        if ($pricingMode === 'per_gb') {
            if ($selectedVolumeGb === null || !$this->isValidTariffVolumeSelection($tariff, $selectedVolumeGb)) {
                return ['ok' => false, 'error' => 'invalid_volume'];
            }
            $volumeForOrder = $selectedVolumeGb;
        }
        $amount = $this->calculateServiceTariffAmount($tariff, $volumeForOrder);
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'invalid_tariff_price'];
        }
        if ($service['mode'] === 'stock' && !$this->serviceHasAvailableStock($serviceId, $tariffId)) {
            return ['ok' => false, 'error' => 'no_stock'];
        }

        $this->pdo->beginTransaction();
        try {
            $user = $this->getUser($userId);
            $balance = (int) ($user['balance'] ?? 0);
            if ($balance < $amount) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'insufficient_balance', 'amount' => $amount, 'balance' => $balance];
            }

            $newBalance = $balance - $amount;
            $update = $this->pdo->prepare('UPDATE users SET balance = :balance WHERE user_id = :user_id');
            $update->execute(['balance' => $newBalance, 'user_id' => $userId]);

            $paymentId = $this->createPayment([
                'kind' => 'purchase',
                'user_id' => $userId,
                'service_id' => $serviceId,
                'tariff_id' => $tariffId,
                'amount' => $amount,
                'payment_method' => 'wallet',
                'status' => 'paid',
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);

            $pendingId = $this->createPendingOrder([
                'user_id' => $userId,
                'service_id' => $serviceId,
                'tariff_id' => $tariffId,
                'selected_volume_gb' => $volumeForOrder,
                'computed_amount' => $amount,
                'payment_id' => $paymentId,
                'amount' => $amount,
                'payment_method' => 'wallet',
                'created_at' => gmdate('Y-m-d H:i:s'),
                'status' => 'paid_waiting_delivery',
            ]);

            $this->pdo->commit();
            return [
                'ok' => true,
                'payment_id' => $paymentId,
                'pending_order_id' => $pendingId,
                'amount' => $amount,
                'new_balance' => $newBalance,
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'error' => 'db_error'];
        }
    }

    public function walletPayRenewal(int $userId, int $purchaseId, int $tariffId): array
    {
        $purchase = $this->getUserPurchaseForRenewal($userId, $purchaseId);
        if (!is_array($purchase)) {
            return ['ok' => false, 'error' => 'purchase_not_found'];
        }
        if ((int) ($purchase['is_test'] ?? 0) === 1) {
            return ['ok' => false, 'error' => 'test_not_renewable'];
        }
        $serviceId = (int) ($purchase['service_id'] ?? 0);
        $tariffId = (int) ($purchase['tariff_id'] ?? 0);
        if ($serviceId <= 0 || $tariffId <= 0) {
            return ['ok' => false, 'error' => 'service_or_tariff_missing'];
        }
        $tariff = $this->getServiceTariffForService($serviceId, $tariffId);
        if (!is_array($tariff)) {
            return ['ok' => false, 'error' => 'tariff_not_found'];
        }
        $price = $this->calculateServiceTariffAmount($tariff, null);
        $this->pdo->beginTransaction();
        try {
            $user = $this->getUser($userId);
            $balance = (int) ($user['balance'] ?? 0);
            if ($balance < $price) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'insufficient_balance', 'price' => $price, 'balance' => $balance];
            }

            $newBalance = $balance - $price;
            $update = $this->pdo->prepare('UPDATE users SET balance = :balance WHERE user_id = :user_id');
            $update->execute(['balance' => $newBalance, 'user_id' => $userId]);

            $paymentId = $this->createPayment([
                'kind' => 'renewal',
                'user_id' => $userId,
                'service_id' => $serviceId,
                'tariff_id' => $tariffId,
                'amount' => $price,
                'payment_method' => 'wallet',
                'status' => 'paid',
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);

            $pendingId = $this->createPendingOrder([
                'user_id' => $userId,
                'service_id' => $serviceId,
                'tariff_id' => $tariffId,
                'order_mode' => self::DELIVERY_MODE_STOCK_ONLY,
                'selected_volume_gb' => null,
                'computed_amount' => null,
                'payment_id' => $paymentId,
                'amount' => $price,
                'payment_method' => 'wallet',
                'created_at' => gmdate('Y-m-d H:i:s'),
                'status' => 'paid_waiting_delivery',
            ]);
            $this->pdo->commit();
            return ['ok' => true, 'payment_id' => $paymentId, 'pending_order_id' => $pendingId, 'price' => $price, 'new_balance' => $newBalance];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'error' => 'db_error'];
        }
    }

    public function listWaitingWalletChargePayments(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, user_id, amount, created_at
             FROM payments
             WHERE kind = 'wallet_charge' AND status = 'waiting_admin'
             ORDER BY id ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function listWaitingAdminPayments(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, kind, user_id, amount, payment_method, created_at
             FROM payments
             WHERE status = 'waiting_admin'
             ORDER BY id ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function applyWalletChargeDecision(int $paymentId, bool $approve): array
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, user_id, amount, status, kind
                 FROM payments
                 WHERE id = :id
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmt->execute(['id' => $paymentId]);
            $payment = $stmt->fetch();
            if (!is_array($payment)) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'not_found'];
            }

            if (($payment['kind'] ?? '') !== 'wallet_charge' || ($payment['status'] ?? '') !== 'waiting_admin') {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'not_actionable'];
            }

            $newStatus = $approve ? 'approved' : 'rejected';
            $update = $this->pdo->prepare(
                "UPDATE payments
                 SET status = :status, approved_at = :approved_at
                 WHERE id = :id"
            );
            $update->execute([
                'status' => $newStatus,
                'approved_at' => gmdate('Y-m-d H:i:s'),
                'id' => $paymentId,
            ]);

            if ($approve) {
                $amount = (int) $payment['amount'];
                $balanceUpdate = $this->pdo->prepare(
                    'UPDATE users SET balance = balance + :amount WHERE user_id = :user_id'
                );
                $balanceUpdate->execute([
                    'amount' => $amount,
                    'user_id' => (int) $payment['user_id'],
                ]);
            }

            $this->pdo->commit();
            return [
                'ok' => true,
                'status' => $newStatus,
                'user_id' => (int) $payment['user_id'],
                'amount' => (int) $payment['amount'],
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'error' => 'db_error'];
        }
    }

    public function applyAdminPaymentDecision(int $paymentId, bool $approve): array
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, user_id, amount, status, kind
                 FROM payments
                 WHERE id = :id
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmt->execute(['id' => $paymentId]);
            $payment = $stmt->fetch();
            if (!is_array($payment)) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'not_found'];
            }
            if (($payment['status'] ?? '') !== 'waiting_admin') {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'not_actionable'];
            }

            $newStatus = $approve ? 'approved' : 'rejected';
            $update = $this->pdo->prepare(
                "UPDATE payments
                 SET status = :status, approved_at = :approved_at
                 WHERE id = :id"
            );
            $update->execute([
                'status' => $newStatus,
                'approved_at' => gmdate('Y-m-d H:i:s'),
                'id' => $paymentId,
            ]);

            $kind = (string) ($payment['kind'] ?? '');
            if ($kind === 'wallet_charge') {
                if ($approve) {
                    $balanceUpdate = $this->pdo->prepare('UPDATE users SET balance = balance + :amount WHERE user_id = :user_id');
                    $balanceUpdate->execute([
                        'amount' => (int) $payment['amount'],
                        'user_id' => (int) $payment['user_id'],
                    ]);
                }
            } elseif ($kind === 'purchase' || $kind === 'renewal') {
                $pendingStatus = $approve ? 'paid_waiting_delivery' : 'cancelled';
                $pendingUpdate = $this->pdo->prepare('UPDATE pending_orders SET status = :status WHERE payment_id = :payment_id');
                $pendingUpdate->execute([
                    'status' => $pendingStatus,
                    'payment_id' => $paymentId,
                ]);
            }

            $this->pdo->commit();
            return [
                'ok' => true,
                'status' => $newStatus,
                'kind' => $kind,
                'user_id' => (int) $payment['user_id'],
                'amount' => (int) $payment['amount'],
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'error' => 'db_error'];
        }
    }

    public function createPendingOrder(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO pending_orders (user_id, order_mode, service_id, tariff_id, selected_volume_gb, computed_amount, payment_id, amount, payment_method, created_at, status)
             VALUES (:user_id, :order_mode, :service_id, :tariff_id, :selected_volume_gb, :computed_amount, :payment_id, :amount, :payment_method, :created_at, :status)'
        );
        $stmt->execute([
            'user_id' => (int) ($data['user_id'] ?? 0),
            'order_mode' => (string) ($data['order_mode'] ?? self::DELIVERY_MODE_STOCK_ONLY),
            'service_id' => isset($data['service_id']) ? (int) $data['service_id'] : null,
            'tariff_id' => isset($data['tariff_id']) ? (int) $data['tariff_id'] : null,
            'selected_volume_gb' => isset($data['selected_volume_gb']) ? (float) $data['selected_volume_gb'] : null,
            'computed_amount' => isset($data['computed_amount']) ? (int) $data['computed_amount'] : null,
            'payment_id' => isset($data['payment_id']) ? (int) $data['payment_id'] : null,
            'amount' => (int) ($data['amount'] ?? 0),
            'payment_method' => (string) ($data['payment_method'] ?? ''),
            'created_at' => (string) ($data['created_at'] ?? gmdate('Y-m-d H:i:s')),
            'status' => (string) ($data['status'] ?? 'waiting'),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function listPendingDeliveries(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, user_id, order_mode, service_id, tariff_id, selected_volume_gb, computed_amount, payment_id, amount, created_at
             FROM pending_orders
             WHERE status = 'paid_waiting_delivery'
             ORDER BY id ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function deliverPendingOrder(int $orderId): array
    {
        $this->pdo->beginTransaction();
        try {
            $orderStmt = $this->pdo->prepare(
                "SELECT id, user_id, order_mode, service_id, tariff_id, selected_volume_gb, computed_amount, payment_id, amount, payment_method, status
                 FROM pending_orders
                 WHERE id = :id
                 LIMIT 1
                 FOR UPDATE"
            );
            $orderStmt->execute(['id' => $orderId]);
            $order = $orderStmt->fetch();
            if (!is_array($order)) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'not_found'];
            }
            $orderStatus = (string) ($order['status'] ?? '');
            if (!in_array($orderStatus, ['paid_waiting_delivery', 'worker_queued'], true)) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'not_actionable'];
            }

            $orderMode = trim((string) ($order['order_mode'] ?? ''));
            $mode = in_array($orderMode, [self::DELIVERY_MODE_STOCK_ONLY, self::DELIVERY_MODE_PANEL_ONLY], true)
                ? $orderMode
                : $this->deliveryMode();

            $serviceId = (int) ($order['service_id'] ?? 0);
            if ($serviceId > 0 && (int) ($order['tariff_id'] ?? 0) > 0) {
                return $this->finalizeServiceDelivery($order);
            }
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => 'service_or_tariff_missing'];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'error' => 'db_error'];
        }
    }

    private function findAvailableStockItemForTariff(int $tariffId): mixed
    {
        return false;
    }

    private function findAvailableStockItemForService(int $serviceId, ?int $tariffId = null): mixed
    {
        $sql = "SELECT id, sub_link, config_link, volume_gb, duration_days
                FROM service_stock_items
                WHERE service_id = :service_id
                  AND sold_to IS NULL
                  AND reserved_payment_id IS NULL
                  AND is_expired = 0
                  AND inventory_bucket = 'sale'";
        $params = ['service_id' => $serviceId];
        if ($tariffId !== null && $tariffId > 0) {
            $sql .= ' AND tariff_id = :tariff_id';
            $params['tariff_id'] = $tariffId;
        }
        $sql .= ' ORDER BY id ASC LIMIT 1 FOR UPDATE';
        $stock_itemStmt = $this->pdo->prepare($sql);
        $stock_itemStmt->execute($params);
        return $stock_itemStmt->fetch();
    }

    private function serviceHasAvailableStock(int $serviceId, ?int $tariffId = null): bool
    {
        $sql = "SELECT 1
                FROM service_stock_items
                WHERE service_id = :service_id
                  AND sold_to IS NULL
                  AND reserved_payment_id IS NULL
                  AND is_expired = 0
                  AND inventory_bucket = 'sale'";
        $params = ['service_id' => $serviceId];
        if ($tariffId !== null && $tariffId > 0) {
            $sql .= ' AND tariff_id = :tariff_id';
            $params['tariff_id'] = $tariffId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    private function isValidTariffVolumeSelection(array $tariff, float $volumeGb): bool
    {
        $min = (float) ($tariff['min_volume_gb'] ?? 0);
        $max = (float) ($tariff['max_volume_gb'] ?? 0);
        if ($volumeGb <= 0 || $min <= 0) {
            return false;
        }
        if ($volumeGb < $min) {
            return false;
        }
        if ($max > 0 && $volumeGb > $max) {
            return false;
        }
        return true;
    }

    public function calculateServiceTariffAmount(array $tariff, ?float $selectedVolumeGb = null): int
    {
        $mode = (string) ($tariff['pricing_mode'] ?? 'fixed');
        if ($mode === 'fixed') {
            return max(0, (int) ($tariff['price'] ?? 0));
        }
        if ($selectedVolumeGb === null || !$this->isValidTariffVolumeSelection($tariff, $selectedVolumeGb)) {
            return 0;
        }
        $pricePerGb = (int) ($tariff['price_per_gb'] ?? 0);
        return max(0, (int) round($pricePerGb * $selectedVolumeGb));
    }

    private function finalizeServiceDelivery(array $order): array
    {
        $serviceId = (int) ($order['service_id'] ?? 0);
        $tariffId = (int) ($order['tariff_id'] ?? 0);
        $service = $this->getService($serviceId);
        if (!is_array($service) || (int) ($service['is_active'] ?? 0) !== 1) {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => 'service_not_found'];
        }
        $tariff = $this->getServiceTariffForService($serviceId, $tariffId);
        if (!is_array($tariff)) {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => 'tariff_not_found'];
        }

        $mode = (string) ($service['mode'] ?? 'stock');
        if ($mode === 'stock') {
            $stock_item = $this->findAvailableStockItemForService($serviceId, $tariffId > 0 ? $tariffId : null);
            if (!is_array($stock_item)) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'out_of_stock'];
            }
            return $this->finalizeStockDelivery($order, $stock_item);
        }

        if ($mode !== 'panel_auto') {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => 'unsupported_service_mode'];
        }

        $baseUrl = trim((string) ($service['panel_base_url'] ?? ''));
        $panelUsername = trim((string) ($service['panel_username'] ?? ''));
        $panelPassword = trim((string) ($service['panel_password'] ?? ''));
        if ($baseUrl === '' || $panelUsername === '' || $panelPassword === '') {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => 'panel_not_found'];
        }
        $groupIds = $this->resolveDefaultGroupIds($baseUrl, $panelUsername, $panelPassword);
        if ($groupIds === []) {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => 'panel_ref_invalid'];
        }

        $username = $this->buildProvisionUsername((int) ($order['user_id'] ?? 0), (int) ($order['id'] ?? 0));
        $pricingMode = (string) ($tariff['pricing_mode'] ?? 'fixed');
        $volumeGb = $pricingMode === 'fixed'
            ? (float) ($tariff['volume_gb'] ?? 0)
            : (float) ($order['selected_volume_gb'] ?? 0);
        if ($pricingMode === 'per_gb' && !$this->isValidTariffVolumeSelection($tariff, $volumeGb)) {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => 'invalid_volume'];
        }
        if ($volumeGb <= 0) {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => 'invalid_volume'];
        }

        $durationDays = 0;
        if ($pricingMode === 'fixed') {
            $durationDays = max(0, (int) ($tariff['duration_days'] ?? 0));
        } else {
            $policy = (string) ($tariff['duration_policy'] ?? 'fixed_days');
            $durationDays = $policy === 'fixed_days' ? max(0, (int) ($tariff['duration_days'] ?? 0)) : 0;
        }

        $provider = new PasarGuardProvisioningProvider(
            $baseUrl,
            $panelUsername,
            $panelPassword,
            $groupIds
        );
        $dataLimitBytes = (int) max(1, round($volumeGb * 1024 * 1024 * 1024));
        $expireAt = $durationDays > 0 ? (time() + ($durationDays * 86400)) : 0;
        $result = $provider->provisionUser($username, $dataLimitBytes, $expireAt, $groupIds);
        if (!($result['ok'] ?? false)) {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => (string) ($result['error'] ?? 'panel_provision_failed')];
        }
        $subscriptionUrl = trim((string) ($result['subscription_url'] ?? ''));
        if ($subscriptionUrl === '') {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => 'subscription_missing'];
        }

        $purchaseId = $this->createPurchase(
            (int) ($order['user_id'] ?? 0),
            null,
            null,
            (int) ($order['amount'] ?? 0),
            (string) ($order['payment_method'] ?? 'panel'),
            false,
            $serviceId,
            $tariffId
        );
        $this->recordUserServiceDelivery($purchaseId, (int) ($order['user_id'] ?? 0), $serviceId, $tariffId, 'panel', null, $subscriptionUrl, null, null, $volumeGb, $durationDays, null);

        $this->markOrderDelivered((int) ($order['id'] ?? 0), (int) ($order['payment_id'] ?? 0));
        $this->pdo->commit();
        return [
            'ok' => true,
            'user_id' => (int) ($order['user_id'] ?? 0),
            'raw_payload' => $subscriptionUrl,
            'service_name' => (string) ($service['name'] ?? ''),
            'sub_link' => $subscriptionUrl,
        ];
    }

    private function finalizePanelOnlyDelivery(array $order): array
    {
        $this->pdo->rollBack();
        return ['ok' => false, 'error' => 'legacy_panel_mode_removed'];
    }

    private function buildProvisionUsername(int $userId, int $orderId): string
    {
        $suffix = substr(bin2hex(random_bytes(3)), 0, 6);
        $base = 'ft_' . max(1, $userId) . '_' . max(1, $orderId) . '_' . $suffix;
        $normalized = preg_replace('/[^a-zA-Z0-9_]+/', '_', $base) ?? 'ft_user_' . $suffix;
        return substr($normalized, 0, 32);
    }

    /** @param array<string,mixed> $serviceRow
     *  @return array{service:ProvisioningProviderInterface,group_ids:array<int>}|null
     */
    private function buildPasarGuardProvider(array $serviceRow): ?array
    {
        $baseUrl = trim($this->settingValue('pg_base_url', ''));
        $username = trim($this->settingValue('pg_username', ''));
        $password = trim($this->settingValue('pg_password', ''));
        if ($baseUrl === '' || $username === '' || $password === '') {
            return null;
        }

        $groupIds = $this->parseGroupIds((string) ($serviceRow['provider_group_ids'] ?? ''));
        if ($groupIds === []) {
            return null;
        }

        return [
            'service' => new PasarGuardProvisioningProvider($baseUrl, $username, $password, $groupIds),
            'group_ids' => $groupIds,
        ];
    }

    /** @return array<int> */
    private function parseGroupIds(string $raw): array
    {
        $parts = preg_split('/[\\s,]+/', trim($raw)) ?: [];
        $ids = [];
        foreach ($parts as $part) {
            $id = (int) preg_replace('/\\D+/', '', $part);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    private function generateServiceCode(int $length = 10): string
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $maxIndex = strlen($alphabet) - 1;
        for ($attempt = 0; $attempt < 8; $attempt++) {
            $code = '';
            $bytes = random_bytes($length);
            for ($i = 0; $i < $length; $i++) {
                $code .= $alphabet[ord($bytes[$i]) % ($maxIndex + 1)];
            }
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM service WHERE service_code = :code');
            $stmt->execute(['code' => $code]);
            if ((int) $stmt->fetchColumn() === 0) {
                return $code;
            }
        }

        return 'SVC' . time() . substr(bin2hex(random_bytes(3)), 0, 6);
    }

    /** @return array<int> */
    private function resolveDefaultGroupIds(string $baseUrl, string $username, string $password): array
    {
        $client = new PGClient($baseUrl, $username, $password);
        $response = $client->getGroups(0, 50);
        if (!(bool) ($response['success'] ?? false)) {
            return [];
        }
        $groups = is_array($response['data'] ?? null) ? $response['data'] : [];
        foreach ($groups as $group) {
            $groupId = isset($group['id']) ? (int) $group['id'] : 0;
            if ($groupId > 0) {
                return [$groupId];
            }
        }

        return [];
    }

    private function deliveryMode(): string
    {
        $mode = trim($this->settingValue('delivery_mode', self::DELIVERY_MODE_STOCK_ONLY));
        if (!in_array($mode, [self::DELIVERY_MODE_STOCK_ONLY, self::DELIVERY_MODE_PANEL_ONLY], true)) {
            return self::DELIVERY_MODE_STOCK_ONLY;
        }
        return $mode;
    }

    public function listActiveProvisioningServices(string $provider = 'pasarguard'): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT t.id, s.name AS title, '' AS description,
                    t.min_volume_gb AS min_gb, t.max_volume_gb AS max_gb, 1 AS step_gb,
                    t.price_per_gb, COALESCE(t.duration_policy, 'fixed_days') AS duration_policy, t.duration_days,
                    COALESCE(s.panel_provider, 'pasarguard') AS provider,
                    '' AS provider_group_ids,
                    t.is_active
             FROM service_tariff t
             JOIN service s ON s.id = t.service_id
             WHERE s.mode = 'panel_auto'
               AND s.is_active = 1
               AND t.is_active = 1
               AND COALESCE(s.panel_provider, 'pasarguard') = :provider
             ORDER BY t.id ASC"
        );
        $stmt->execute(['provider' => $provider]);
        return $stmt->fetchAll();
    }

    public function getProvisioningService(int $serviceId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT t.id, s.name AS title, '' AS description,
                    t.min_volume_gb AS min_gb, t.max_volume_gb AS max_gb, 1 AS step_gb,
                    t.price_per_gb, COALESCE(t.duration_policy, 'fixed_days') AS duration_policy, t.duration_days,
                    COALESCE(s.panel_provider, 'pasarguard') AS provider,
                    '' AS provider_group_ids,
                    t.is_active
             FROM service_tariff t
             JOIN service s ON s.id = t.service_id
             WHERE t.id = :id AND t.is_active = 1 AND s.is_active = 1 AND s.mode = 'panel_auto'
             LIMIT 1"
        );
        $stmt->execute(['id' => $serviceId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function listProvisioningServicesAll(): array
    {
        return $this->listActiveProvisioningServices();
    }

    /** @param array<string,mixed> $data */
    public function createProvisioningService(array $data): int
    {
        return 0;
    }

    /** @param array<string,mixed> $data */
    public function updateProvisioningService(int $serviceId, array $data): void
    {
        return;
    }

    public function updateProvisioningServiceActive(int $serviceId, bool $active): void
    {
        return;
    }

    public function deleteProvisioningService(int $serviceId): void
    {
        return;
    }

    public function calculatePanelServiceAmount(array $service, float $selectedVolumeGb): int
    {
        $pricePerGb = (int) ($service['price_per_gb'] ?? 0);
        return max(0, (int) round($pricePerGb * $selectedVolumeGb));
    }

    public function validatePanelServiceVolume(array $service, float $selectedVolumeGb): bool
    {
        $min = (float) ($service['min_gb'] ?? 0);
        $max = (float) ($service['max_gb'] ?? 0);
        $step = (float) ($service['step_gb'] ?? 1);
        if ($step <= 0 || $selectedVolumeGb < $min || $selectedVolumeGb > $max) {
            return false;
        }

        $diff = ($selectedVolumeGb - $min) / $step;
        return abs($diff - round($diff)) < 0.00001;
    }

    private function finalizeStockDelivery(array $order, array $stock_item): array
    {
        $serviceId = (int) ($order['service_id'] ?? 0);
        $tariffId = (int) ($order['tariff_id'] ?? 0);
        if ($serviceId <= 0 || $tariffId <= 0) {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => 'service_or_tariff_missing'];
        }
        $purchaseId = $this->createPurchase(
            (int) $order['user_id'],
            null,
            null,
            (int) $order['amount'],
            (string) $order['payment_method'],
            false,
            $serviceId > 0 ? $serviceId : null,
            $tariffId > 0 ? $tariffId : null
        );

        $cfgUpdate = $this->pdo->prepare(
            'UPDATE service_stock_items
             SET sold_to = :sold_to, purchase_id = :purchase_id, sold_at = :sold_at
             WHERE id = :id'
        );
        $cfgUpdate->execute([
            'sold_to' => (int) $order['user_id'],
            'purchase_id' => $purchaseId,
            'sold_at' => gmdate('Y-m-d H:i:s'),
            'id' => (int) $stock_item['id'],
        ]);
        if ($serviceId > 0 && $tariffId > 0) {
            $this->recordUserServiceDelivery(
                $purchaseId,
                (int) $order['user_id'],
                $serviceId,
                $tariffId,
                'stock',
                (int) $stock_item['id'],
                (string) ($stock_item['sub_link'] ?? ''),
                (string) ($stock_item['config_link'] ?? ''),
                null,
                isset($stock_item['volume_gb']) ? (float) $stock_item['volume_gb'] : null,
                isset($stock_item['duration_days']) ? (int) $stock_item['duration_days'] : null,
                $this->buildStockConfigText($stock_item)
            );
        }

        $this->markOrderDelivered((int) $order['id'], (int) ($order['payment_id'] ?? 0));
        $this->pdo->commit();

        return [
            'ok' => true,
            'user_id' => (int) $order['user_id'],
            'raw_payload' => $this->buildStockConfigText($stock_item),
            'service_name' => '',
            'sub_link' => (string) ($stock_item['sub_link'] ?? ''),
        ];
    }

    /** @param array<string,mixed> $stockItem */
    private function buildStockConfigText(array $stockItem): string
    {
        $subLink = trim((string) ($stockItem['sub_link'] ?? ''));
        $configLink = trim((string) ($stockItem['config_link'] ?? ''));
        $volumeGb = isset($stockItem['volume_gb']) ? trim((string) $stockItem['volume_gb']) : '';
        $durationDays = isset($stockItem['duration_days']) ? (int) $stockItem['duration_days'] : 0;

        $lines = [];
        if ($subLink !== '') {
            $lines[] = '🔗 لینک ساب:';
            $lines[] = $subLink;
        }
        if ($configLink !== '') {
            $lines[] = '';
            $lines[] = '🔐 لینک تکی:';
            $lines[] = $configLink;
        }
        if ($volumeGb !== '') {
            $lines[] = '';
            $lines[] = '📦 حجم: ' . $volumeGb . ' گیگ';
        }
        $lines[] = '⏳ مدت: ' . ($durationDays > 0 ? ($durationDays . ' روز') : 'نامحدود');

        return trim(implode("\n", $lines));
    }

    private function createPurchase(int $userId, ?int $legacyTariffId, ?int $legacyStockItemId, int $amount, string $paymentMethod, bool $isTest = false, ?int $serviceId = null, ?int $tariffId = null): int
    {
        $purchaseStmt = $this->pdo->prepare(
            'INSERT INTO purchases (user_id, service_id, tariff_id, amount, payment_method, created_at, is_test)
             VALUES (:user_id, :service_id, :tariff_id, :amount, :payment_method, :created_at, :is_test)'
        );
        $purchaseStmt->execute([
            'user_id' => $userId,
            'service_id' => $serviceId,
            'tariff_id' => $tariffId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'created_at' => gmdate('Y-m-d H:i:s'),
            'is_test' => $isTest ? 1 : 0,
        ]);
        $purchaseId = (int) $this->pdo->lastInsertId();

        if ($paymentMethod !== 'referral_gift') {
            $this->processReferralPurchaseReward($userId);
        }

        return $purchaseId;
    }

    private function recordUserServiceDelivery(
        int $purchaseId,
        int $userId,
        int $serviceId,
        ?int $tariffId,
        string $sourceType,
        ?int $stockItemId,
        string $subLink,
        ?string $accessUrl,
        ?string $stock_itemUuid,
        ?float $volumeGb,
        ?int $durationDays,
        ?string $metaJson
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO user_service_deliveries (
                purchase_id, user_id, service_id, tariff_id, source_type, stock_item_id, sub_link, access_url, stock_item_uuid, volume_gb, duration_days, delivered_at, meta_json
             ) VALUES (
                :purchase_id, :user_id, :service_id, :tariff_id, :source_type, :stock_item_id, :sub_link, :access_url, :stock_item_uuid, :volume_gb, :duration_days, :delivered_at, :meta_json
             )'
        );
        $stmt->execute([
            'purchase_id' => $purchaseId,
            'user_id' => $userId,
            'service_id' => $serviceId,
            'tariff_id' => $tariffId,
            'source_type' => $sourceType === 'panel' ? 'panel' : 'stock',
            'stock_item_id' => $stockItemId,
            'sub_link' => $subLink,
            'access_url' => $accessUrl,
            'stock_item_uuid' => $stock_itemUuid,
            'volume_gb' => $volumeGb,
            'duration_days' => $durationDays,
            'delivered_at' => gmdate('Y-m-d H:i:s'),
            'meta_json' => $metaJson,
        ]);
    }

    private function markOrderDelivered(int $orderId, int $paymentId): void
    {
        $ordUpdate = $this->pdo->prepare("UPDATE pending_orders SET status = 'delivered' WHERE id = :id");
        $ordUpdate->execute(['id' => $orderId]);

        if ($paymentId > 0) {
            $payUpdate = $this->pdo->prepare("UPDATE payments SET status = 'completed', approved_at = :approved_at WHERE id = :id");
            $payUpdate->execute([
                'approved_at' => gmdate('Y-m-d H:i:s'),
                'id' => $paymentId,
            ]);
        }
    }

    public function getPaymentById(int $paymentId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, user_id, service_id, tariff_id, amount, payment_method, gateway_ref, tx_hash, crypto_amount_claimed, status, verify_attempts, last_verify_at FROM payments WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $paymentId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function markPaymentAndPendingPaid(int $paymentId): void
    {
        $payStmt = $this->pdo->prepare("UPDATE payments SET status = 'paid', approved_at = :approved_at WHERE id = :id");
        $payStmt->execute(['approved_at' => gmdate('Y-m-d H:i:s'), 'id' => $paymentId]);

        $orderStmt = $this->pdo->prepare("UPDATE pending_orders SET status = 'paid_waiting_delivery' WHERE payment_id = :payment_id");
        $orderStmt->execute(['payment_id' => $paymentId]);
    }

    public function markPaymentAndPendingPaidIfWaitingGateway(int $paymentId): bool
    {
        $this->pdo->beginTransaction();
        try {
            $rowStmt = $this->pdo->prepare('SELECT status FROM payments WHERE id = :id LIMIT 1 FOR UPDATE');
            $rowStmt->execute(['id' => $paymentId]);
            $row = $rowStmt->fetch();
            if (!is_array($row)) {
                $this->pdo->rollBack();
                return false;
            }
            if (($row['status'] ?? '') !== 'waiting_gateway') {
                $this->pdo->rollBack();
                return false;
            }

            $payStmt = $this->pdo->prepare("UPDATE payments SET status = 'paid', verified_at = :verified_at WHERE id = :id");
            $payStmt->execute(['verified_at' => gmdate('Y-m-d H:i:s'), 'id' => $paymentId]);

            $orderStmt = $this->pdo->prepare("UPDATE pending_orders SET status = 'paid_waiting_delivery' WHERE payment_id = :payment_id");
            $orderStmt->execute(['payment_id' => $paymentId]);

            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    public function setPaymentGatewayRef(int $paymentId, string $gatewayRef): void
    {
        $stmt = $this->pdo->prepare('UPDATE payments SET gateway_ref = :gateway_ref WHERE id = :id');
        $stmt->execute(['gateway_ref' => $gatewayRef, 'id' => $paymentId]);
    }

    public function setPaymentProviderPayload(int $paymentId, array $payload): void
    {
        $stmt = $this->pdo->prepare('UPDATE payments SET provider_payload = :provider_payload WHERE id = :id');
        $stmt->execute([
            'provider_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'id' => $paymentId,
        ]);
    }

    public function registerVerifyAttempt(int $paymentId, int $cooldownSeconds = 20, int $maxAttempts = 15): array
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT verify_attempts, last_verify_at FROM payments WHERE id = :id LIMIT 1 FOR UPDATE');
            $stmt->execute(['id' => $paymentId]);
            $row = $stmt->fetch();
            if (!is_array($row)) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'not_found'];
            }

            $attempts = (int) ($row['verify_attempts'] ?? 0);
            $lastAt = (string) ($row['last_verify_at'] ?? '');
            if ($attempts >= $maxAttempts) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'max_attempts'];
            }

            if ($lastAt !== '') {
                $lastTs = strtotime($lastAt);
                if ($lastTs !== false && (time() - $lastTs) < $cooldownSeconds) {
                    $this->pdo->rollBack();
                    return ['ok' => false, 'error' => 'cooldown'];
                }
            }

            $update = $this->pdo->prepare(
                'UPDATE payments
                 SET verify_attempts = verify_attempts + 1,
                     last_verify_at = :last_verify_at
                 WHERE id = :id'
            );
            $update->execute([
                'last_verify_at' => gmdate('Y-m-d H:i:s'),
                'id' => $paymentId,
            ]);

            $this->pdo->commit();
            return ['ok' => true, 'attempts' => $attempts + 1];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'error' => 'db_error'];
        }
    }

    public function attachPaymentReceipt(int $paymentId, ?string $fileId, ?string $text): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE payments
             SET receipt_file_id = :receipt_file_id,
                 receipt_text = :receipt_text,
                 status = :status
             WHERE id = :id'
        );
        $stmt->execute([
            'receipt_file_id' => $fileId,
            'receipt_text' => $text,
            'status' => 'waiting_admin',
            'id' => $paymentId,
        ]);

        $pending = $this->pdo->prepare('UPDATE pending_orders SET status = :status WHERE payment_id = :payment_id');
        $pending->execute([
            'status' => 'waiting_admin',
            'payment_id' => $paymentId,
        ]);
    }

    public function submitCryptoTxHash(int $paymentId, string $txHash, ?float $claimedAmount = null): bool
    {
        $this->pdo->beginTransaction();
        try {
            $paymentStmt = $this->pdo->prepare('SELECT status FROM payments WHERE id = :id LIMIT 1 FOR UPDATE');
            $paymentStmt->execute(['id' => $paymentId]);
            $row = $paymentStmt->fetch();
            if (!is_array($row)) {
                $this->pdo->rollBack();
                return false;
            }
            if (($row['status'] ?? '') !== 'waiting_admin' && ($row['status'] ?? '') !== 'waiting_payment') {
                $this->pdo->rollBack();
                return false;
            }

            $update = $this->pdo->prepare(
                "UPDATE payments
                 SET tx_hash = :tx_hash,
                     crypto_amount_claimed = :crypto_amount_claimed,
                     provider_payload = :provider_payload,
                     status = 'waiting_admin'
                 WHERE id = :id"
            );
            $update->execute([
                'tx_hash' => $txHash,
                'crypto_amount_claimed' => $claimedAmount,
                'provider_payload' => json_encode([
                    'source' => 'user_tx_hash',
                    'tx_hash' => $txHash,
                    'claimed_amount' => $claimedAmount,
                    'submitted_at' => gmdate('c'),
                ], JSON_UNESCAPED_UNICODE),
                'id' => $paymentId,
            ]);

            $pending = $this->pdo->prepare("UPDATE pending_orders SET status = 'waiting_admin' WHERE payment_id = :payment_id");
            $pending->execute(['payment_id' => $paymentId]);

            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    public function getActiveTariffsWithStock(int $typeId, bool $stockOnly = false): array
    {
        $sql = 'SELECT t.id,
                       CONCAT(s.name, " / ", COALESCE(CAST(t.volume_gb AS CHAR), "dynamic")) AS name,
                       COALESCE(t.price, 0) AS price,
                       t.volume_gb,
                       t.duration_days,
                       (SELECT COUNT(*)
                        FROM service_stock_items c
                        WHERE c.service_id = t.service_id
                          AND (c.tariff_id = t.id OR c.tariff_id IS NULL)
                          AND c.sold_to IS NULL
                          AND c.reserved_payment_id IS NULL
                          AND c.is_expired = 0
                          AND c.inventory_bucket = "sale") AS stock
                FROM service_tariff t
                JOIN service s ON s.id = t.service_id
                WHERE s.is_active = 1 AND t.is_active = 1';
        if ($stockOnly) {
            $sql .= ' HAVING stock > 0';
        }
        $sql .= ' ORDER BY p.id ASC';
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function getAgencyPriceStockItem(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT price_mode, global_type, global_val FROM agency_price_config WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return ['price_mode' => 'service', 'global_type' => 'pct', 'global_val' => 0];
        }
        return [
            'price_mode' => (string) ($row['price_mode'] ?? 'service'),
            'global_type' => (string) ($row['global_type'] ?? 'pct'),
            'global_val' => (int) ($row['global_val'] ?? 0),
        ];
    }

    public function effectiveTariffPrice(int $userId, array $tariff): int
    {
        $base = (int) ($tariff['price'] ?? 0);
        $user = $this->getUser($userId);
        if (!is_array($user) || (int) ($user['is_agent'] ?? 0) !== 1) {
            return $base;
        }

        $tariffId = (int) ($tariff['id'] ?? 0);

        // Service-centric precedence: tariff > global
        $pkgCustom = $this->getAgencyPrice($userId, $tariffId);
        if ($pkgCustom !== null) {
            return max(0, $pkgCustom);
        }

        $stock_item = $this->getAgencyPriceStockItem($userId);
        $gType = (string) ($stock_item['global_type'] ?? 'pct');
        $gVal = (int) ($stock_item['global_val'] ?? 0);
        return $gType === 'pct'
            ? max(0, $base - (int) round($base * $gVal / 100))
            : max(0, $base - $gVal);
    }

    public function hasAcceptedPurchaseRules(int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM purchase_rule_acceptances WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        return (bool) $stmt->fetchColumn();
    }

    public function acceptPurchaseRules(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO purchase_rule_acceptances (user_id, accepted_at) VALUES (:user_id, :accepted_at) ON DUPLICATE KEY UPDATE accepted_at = VALUES(accepted_at)'
        );
        $stmt->execute(['user_id' => $userId, 'accepted_at' => gmdate('Y-m-d H:i:s')]);
    }



    public function tariffHasAvailableStock(int $tariffId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1
             FROM service_stock_items
             WHERE tariff_id = :tariff_id
               AND sold_to IS NULL
               AND reserved_payment_id IS NULL
               AND is_expired = 0
               AND inventory_bucket = 'sale'
             LIMIT 1"
        );
        $stmt->execute(['tariff_id' => $tariffId]);
        return (bool) $stmt->fetchColumn();
    }

    public function addReferral(int $referrerId, int $refereeId): void
    {
        if ($referrerId <= 0 || $refereeId <= 0 || $referrerId === $refereeId) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO referrals (referrer_id, referee_id, created_at, start_reward_given, purchase_reward_given) VALUES (:referrer_id, :referee_id, :created_at, 0, 0)'
        );
        $stmt->execute([
            'referrer_id' => $referrerId,
            'referee_id' => $refereeId,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);

        if ($stmt->rowCount() > 0) {
            $this->processReferralStartReward($referrerId);
        }
    }


    private function processReferralStartReward(int $referrerId): void
    {
        if ((string) $this->settingValue('referral_start_reward_enabled', '0') !== '1') {
            return;
        }
        $requiredCount = max(1, (int) $this->settingValue('referral_start_reward_count', '1'));

        $stmt = $this->pdo->prepare(
            'SELECT referee_id FROM referrals WHERE referrer_id = :referrer_id AND start_reward_given = 0 ORDER BY id ASC LIMIT ' . $requiredCount
        );
        $stmt->execute(['referrer_id' => $referrerId]);
        $rows = $stmt->fetchAll();
        if (count($rows) < $requiredCount) {
            return;
        }

        $mark = $this->pdo->prepare('UPDATE referrals SET start_reward_given = 1 WHERE referrer_id = :referrer_id AND referee_id = :referee_id');
        foreach ($rows as $row) {
            $mark->execute(['referrer_id' => $referrerId, 'referee_id' => (int) ($row['referee_id'] ?? 0)]);
        }
        $this->grantReferralReward($referrerId, 'referral_start_reward');
    }

    private function processReferralPurchaseReward(int $buyerUserId): void
    {
        if ((string) $this->settingValue('referral_purchase_reward_enabled', '0') !== '1') {
            return;
        }

        $refStmt = $this->pdo->prepare('SELECT referrer_id FROM referrals WHERE referee_id = :referee_id LIMIT 1');
        $refStmt->execute(['referee_id' => $buyerUserId]);
        $referrerId = (int) ($refStmt->fetchColumn() ?: 0);
        if ($referrerId <= 0) {
            return;
        }

        $requiredCount = max(1, (int) $this->settingValue('referral_purchase_reward_count', '1'));
        $stmt = $this->pdo->prepare(
            'SELECT DISTINCT r.referee_id
'
            . 'FROM referrals r JOIN purchases p ON p.user_id = r.referee_id AND p.is_test = 0
'
            . 'WHERE r.referrer_id = :referrer_id AND r.purchase_reward_given = 0
'
            . 'ORDER BY r.id ASC LIMIT ' . $requiredCount
        );
        $stmt->execute(['referrer_id' => $referrerId]);
        $rows = $stmt->fetchAll();
        if (count($rows) < $requiredCount) {
            return;
        }

        $mark = $this->pdo->prepare('UPDATE referrals SET purchase_reward_given = 1 WHERE referrer_id = :referrer_id AND referee_id = :referee_id');
        foreach ($rows as $row) {
            $mark->execute(['referrer_id' => $referrerId, 'referee_id' => (int) ($row['referee_id'] ?? 0)]);
        }
        $this->grantReferralReward($referrerId, 'referral_purchase_reward');
    }

    private function grantReferralReward(int $referrerId, string $prefix): void
    {
        $rewardType = (string) $this->settingValue($prefix . '_type', 'wallet');
        if ($rewardType === 'wallet') {
            $amount = (int) $this->settingValue($prefix . '_amount', '0');
            if ($amount > 0) {
                $upd = $this->pdo->prepare('UPDATE users SET balance = balance + :amount WHERE user_id = :user_id');
                $upd->execute(['amount' => $amount, 'user_id' => $referrerId]);
            }
            return;
        }

        $tariffId = (int) $this->settingValue($prefix . '_tariff', '0');
        if ($tariffId <= 0) {
            return;
        }
        $tariff = $this->getServiceTariff($tariffId);
        if (!is_array($tariff)) {
            return;
        }
        $serviceId = (int) ($tariff['service_id'] ?? 0);
        if ($serviceId <= 0) {
            return;
        }

        $cfgStmt = $this->pdo->prepare(
            "SELECT id, sub_link, config_link, volume_gb, duration_days
             FROM service_stock_items
             WHERE service_id = :service_id
               AND tariff_id = :tariff_id
               AND sold_to IS NULL
               AND reserved_payment_id IS NULL
               AND is_expired = 0
               AND inventory_bucket = 'sale'
             ORDER BY id ASC LIMIT 1 FOR UPDATE"
        );
        $cfgStmt->execute(['service_id' => $serviceId, 'tariff_id' => $tariffId]);
        $cfg = $cfgStmt->fetch();
        $cfgId = is_array($cfg) ? (int) ($cfg['id'] ?? 0) : 0;
        if ($cfgId <= 0) {
            return;
        }

        $purchaseId = $this->createPurchase($referrerId, null, null, 0, 'referral_gift', false, $serviceId, $tariffId);
        $cfgUpdate = $this->pdo->prepare('UPDATE service_stock_items SET sold_to = :sold_to, purchase_id = :purchase_id, sold_at = :sold_at WHERE id = :id');
        $cfgUpdate->execute([
            'sold_to' => $referrerId,
            'purchase_id' => $purchaseId,
            'sold_at' => gmdate('Y-m-d H:i:s'),
            'id' => $cfgId,
        ]);
        $this->recordUserServiceDelivery(
            $purchaseId,
            $referrerId,
            $serviceId,
            $tariffId,
            'stock',
            $cfgId,
            (string) ($cfg['sub_link'] ?? ''),
            (string) ($cfg['config_link'] ?? ''),
            null,
            isset($cfg['volume_gb']) ? (float) $cfg['volume_gb'] : null,
            isset($cfg['duration_days']) ? (int) $cfg['duration_days'] : null,
            $this->buildStockConfigText($cfg)
        );
    }

    public function getFreeTestRuleForService(int $serviceId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT service_id, is_enabled, claim_mode, cooldown_days, max_claims, volume_gb, duration_days, priority, created_at, updated_at
             FROM free_test_service_rules
             WHERE service_id = :service_id
             LIMIT 1'
        );
        $stmt->execute(['service_id' => $serviceId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function saveFreeTestRuleForService(int $serviceId, bool $isEnabled, string $claimMode, ?int $cooldownDays, int $maxClaims = 1, int $priority = 0): void
    {
        $claimMode = $claimMode === 'cooldown' ? 'cooldown' : 'once_until_reset';
        $maxClaims = max(1, $maxClaims);
        $cooldownDays = $claimMode === 'cooldown' ? max(1, (int) ($cooldownDays ?? 0)) : null;
        $now = gmdate('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare(
            'INSERT INTO free_test_service_rules (service_id, is_enabled, claim_mode, cooldown_days, max_claims, priority, created_at, updated_at)
             VALUES (:service_id, :is_enabled, :claim_mode, :cooldown_days, :max_claims, :priority, :created_at, :updated_at)
             ON DUPLICATE KEY UPDATE
                is_enabled = VALUES(is_enabled),
                claim_mode = VALUES(claim_mode),
                cooldown_days = VALUES(cooldown_days),
                max_claims = VALUES(max_claims),
                priority = VALUES(priority),
                updated_at = VALUES(updated_at)'
        );
        $stmt->execute([
            'service_id' => $serviceId,
            'is_enabled' => $isEnabled ? 1 : 0,
            'claim_mode' => $claimMode,
            'cooldown_days' => $cooldownDays,
            'max_claims' => $maxClaims,
            'priority' => $priority,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function saveFreeTestStockDefaultsForService(int $serviceId, float $defaultVolumeGb, ?int $defaultDurationDays): void
    {
        $defaultVolumeGb = max(0.01, $defaultVolumeGb);
        if ($defaultDurationDays !== null) {
            $defaultDurationDays = max(0, $defaultDurationDays);
        }
        $now = gmdate('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO free_test_service_rules (service_id, is_enabled, claim_mode, cooldown_days, max_claims, volume_gb, duration_days, priority, created_at, updated_at)
             VALUES (:service_id, 0, :claim_mode, NULL, 1, :volume_gb, :duration_days, 0, :created_at, :updated_at)
             ON DUPLICATE KEY UPDATE
                volume_gb = VALUES(volume_gb),
                duration_days = VALUES(duration_days),
                updated_at = VALUES(updated_at)'
        );
        $stmt->execute([
            'service_id' => $serviceId,
            'claim_mode' => 'once_until_reset',
            'volume_gb' => $defaultVolumeGb,
            'duration_days' => $defaultDurationDays,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function listEnabledFreeTestServices(bool $onlyEligibleForUser = false, ?int $userId = null): array
    {
        $stmt = $this->pdo->query(
            "SELECT s.id AS service_id, s.name AS service_name, s.mode, s.is_active,
                    r.is_enabled, r.claim_mode, r.cooldown_days, r.max_claims, r.priority,
                    (SELECT COUNT(*) FROM service_stock_items c WHERE c.service_id = s.id AND c.sold_to IS NULL AND c.reserved_payment_id IS NULL AND c.is_expired = 0 AND c.inventory_bucket = 'free_test') AS available_stock,
                    (SELECT COUNT(*) FROM free_test_service_claims fc WHERE fc.service_id = s.id) AS total_claims
             FROM free_test_service_rules r
             JOIN service s ON s.id = r.service_id
             WHERE r.is_enabled = 1
             ORDER BY r.priority DESC, s.id ASC"
        );
        $rows = $stmt->fetchAll();
        if (!$onlyEligibleForUser || $userId === null || $userId <= 0) {
            return $rows;
        }

        $eligible = [];
        foreach ($rows as $row) {
            $serviceId = (int) ($row['service_id'] ?? 0);
            if ($serviceId <= 0) {
                continue;
            }
            if ($this->canUserClaimFreeTestForService($userId, $serviceId, $row)) {
                $eligible[] = $row;
            }
        }
        return $eligible;
    }

    public function listUserVisibleFreeTestServices(int $userId): array
    {
        $stmt = $this->pdo->query(
            "SELECT s.id AS service_id, s.name AS service_name, s.mode, s.is_active,
                    r.is_enabled, r.claim_mode, r.cooldown_days, r.max_claims, r.volume_gb, r.duration_days, r.priority,
                    (SELECT COUNT(*) FROM service_stock_items c WHERE c.service_id = s.id AND c.sold_to IS NULL AND c.reserved_payment_id IS NULL AND c.is_expired = 0 AND c.inventory_bucket = 'free_test') AS available_stock
             FROM free_test_service_rules r
             JOIN service s ON s.id = r.service_id
             WHERE r.is_enabled = 1
               AND s.is_active = 1
               AND s.mode IN ('stock', 'panel_auto')
             ORDER BY r.priority DESC, s.id ASC"
        );
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $serviceId = (int) ($row['service_id'] ?? 0);
            $evaluation = $this->evaluateFreeTestClaimForService($userId, $serviceId, is_array($row) ? $row : null, null);
            $row['maybe_claimable_for_user'] = ($evaluation['ok'] ?? false) === true ? 1 : 0;
            $row['maybe_claim_block_reason'] = (string) ($evaluation['error_code'] ?? '');
        }
        unset($row);
        return $rows;
    }

    public function claimFreeTestForService(int $userId, int $serviceId): array
    {
        $evaluation = $this->evaluateFreeTestClaimForService($userId, $serviceId);
        if (($evaluation['ok'] ?? false) !== true) {
            return $evaluation;
        }
        $service = is_array($evaluation['service'] ?? null) ? $evaluation['service'] : $this->getService($serviceId);
        $rule = is_array($evaluation['rule'] ?? null) ? $evaluation['rule'] : $this->getFreeTestRuleForService($serviceId);
        $mode = (string) ($evaluation['mode'] ?? ($service['mode'] ?? 'stock'));
        if ($mode === 'panel_auto') {
            return $this->claimFreeTestFromPanelService(
                $userId,
                $serviceId,
                is_array($service) ? $service : [],
                is_array($rule) ? $rule : [],
                isset($evaluation['group_ids']) && is_array($evaluation['group_ids']) ? $evaluation['group_ids'] : null
            );
        }

        return $this->claimFreeTestFromStockService($userId, $serviceId, is_array($service) ? $service : null);
    }

    private function claimFreeTestFromStockService(int $userId, int $serviceId, ?array $service = null): array
    {
        $serviceName = is_array($service) ? (string) ($service['name'] ?? '') : '';
        $now = gmdate('Y-m-d H:i:s');
        $this->pdo->beginTransaction();
        try {
            $cfgStmt = $this->pdo->prepare(
                "SELECT id, service_id, sub_link, config_link, volume_gb, duration_days
                 FROM service_stock_items
                 WHERE service_id = :service_id
                   AND sold_to IS NULL
                   AND reserved_payment_id IS NULL
                   AND is_expired = 0
                   AND inventory_bucket = 'free_test'
                 ORDER BY id ASC
                 LIMIT 1 FOR UPDATE"
            );
            $cfgStmt->execute(['service_id' => $serviceId]);
            $cfg = $cfgStmt->fetch();
            if (!is_array($cfg)) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error_code' => 'free_test_stock_empty'];
            }

            $stockItemId = (int) ($cfg['id'] ?? 0);
            $purchaseId = $this->createPurchase($userId, null, null, 0, 'free_test', true, $serviceId, null);
            $updateCfg = $this->pdo->prepare('UPDATE service_stock_items SET sold_to = :sold_to, purchase_id = :purchase_id, sold_at = :sold_at WHERE id = :id');
            $updateCfg->execute([
                'sold_to' => $userId,
                'purchase_id' => $purchaseId,
                'sold_at' => $now,
                'id' => $stockItemId,
            ]);

            $insertClaim = $this->pdo->prepare(
                'INSERT INTO free_test_service_claims (user_id, service_id, purchase_id, claimed_at)
                 VALUES (:user_id, :service_id, :purchase_id, :claimed_at)'
            );
            $insertClaim->execute([
                'user_id' => $userId,
                'service_id' => $serviceId,
                'purchase_id' => $purchaseId,
                'claimed_at' => $now,
            ]);
            $configText = $this->buildStockConfigText($cfg);
            $this->recordUserServiceDelivery(
                $purchaseId,
                $userId,
                $serviceId,
                null,
                'stock',
                $stockItemId,
                (string) ($cfg['sub_link'] ?? ''),
                (string) ($cfg['config_link'] ?? ''),
                null,
                isset($cfg['volume_gb']) ? (float) $cfg['volume_gb'] : null,
                isset($cfg['duration_days']) ? (int) $cfg['duration_days'] : null,
                $configText
            );

            $this->pdo->commit();
            return [
                'ok' => true,
                'service_id' => $serviceId,
                'purchase_id' => $purchaseId,
                'service_name' => $serviceName,
                'mode' => 'stock',
                'raw_payload' => $configText,
                'sub_link' => (string) ($cfg['sub_link'] ?? ''),
            ];
        } catch (\Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'error_code' => 'free_test_claim_failed'];
        }
    }

    private function claimFreeTestFromPanelService(int $userId, int $serviceId, array $service, array $rule, ?array $resolvedGroupIds = null): array
    {
        $defaultVolumeGb = isset($rule['volume_gb']) ? (float) $rule['volume_gb'] : 0.0;
        if ($defaultVolumeGb <= 0) {
            return ['ok' => false, 'error_code' => 'free_test_panel_config_invalid'];
        }
        $durationDays = isset($rule['duration_days']) ? max(0, (int) $rule['duration_days']) : 0;
        $baseUrl = trim((string) ($service['panel_base_url'] ?? ''));
        $panelUsername = trim((string) ($service['panel_username'] ?? ''));
        $panelPassword = trim((string) ($service['panel_password'] ?? ''));
        $groupIds = is_array($resolvedGroupIds) && $resolvedGroupIds !== []
            ? array_values(array_map('intval', $resolvedGroupIds))
            : $this->resolveDefaultGroupIds($baseUrl, $panelUsername, $panelPassword);
        if ($groupIds === []) {
            return ['ok' => false, 'error_code' => 'free_test_panel_group_missing'];
        }

        $this->pdo->beginTransaction();
        try {
            $purchaseId = $this->createPurchase($userId, null, null, 0, 'free_test', true, $serviceId, null);
            $username = $this->buildProvisionUsername($userId, $purchaseId);
            $provider = new PasarGuardProvisioningProvider(
                $baseUrl,
                $panelUsername,
                $panelPassword,
                $groupIds
            );
            $dataLimitBytes = (int) max(1, round($defaultVolumeGb * 1024 * 1024 * 1024));
            $expireAt = $durationDays > 0 ? (time() + ($durationDays * 86400)) : 0;
            $result = $provider->provisionUser($username, $dataLimitBytes, $expireAt, $groupIds);
            if (!($result['ok'] ?? false)) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error_code' => 'free_test_panel_provision_failed', 'provider_error' => (string) ($result['error'] ?? 'panel_provision_failed')];
            }
            $subscriptionUrl = trim((string) ($result['subscription_url'] ?? ''));
            if ($subscriptionUrl === '') {
                $this->pdo->rollBack();
                return ['ok' => false, 'error_code' => 'free_test_delivery_failed'];
            }

            $now = gmdate('Y-m-d H:i:s');
            $insertClaim = $this->pdo->prepare(
                'INSERT INTO free_test_service_claims (user_id, service_id, purchase_id, claimed_at)
                 VALUES (:user_id, :service_id, :purchase_id, :claimed_at)'
            );
            $insertClaim->execute([
                'user_id' => $userId,
                'service_id' => $serviceId,
                'purchase_id' => $purchaseId,
                'claimed_at' => $now,
            ]);

            $rawData = is_array($result['raw'] ?? null) ? $result['raw'] : [];
            $metaJson = json_encode([
                'provider' => 'pasarguard',
                'username' => $username,
                'created_user_id' => isset($rawData['id']) ? (int) $rawData['id'] : null,
                'status' => (string) ($rawData['status'] ?? 'active'),
                'expire' => $expireAt,
                'data_limit' => $dataLimitBytes,
                'group_ids' => $groupIds,
                'subscription_url' => $subscriptionUrl,
                'raw' => $rawData,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->recordUserServiceDelivery(
                $purchaseId,
                $userId,
                $serviceId,
                null,
                'panel',
                null,
                $subscriptionUrl,
                null,
                null,
                $defaultVolumeGb,
                $durationDays,
                $metaJson === false ? null : $metaJson
            );
            $this->pdo->commit();
            return [
                'ok' => true,
                'service_id' => $serviceId,
                'purchase_id' => $purchaseId,
                'service_name' => (string) ($service['name'] ?? ''),
                'mode' => 'panel_auto',
                'username' => $username,
                'volume_gb' => $defaultVolumeGb,
                'duration_days' => $durationDays,
                'raw_payload' => $subscriptionUrl,
                'sub_link' => $subscriptionUrl,
            ];
        } catch (\Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'error_code' => 'free_test_claim_failed'];
        }
    }

    private function claimFreeTestFromPanelService(int $userId, int $serviceId, array $service, array $rule): array
    {
        $defaultVolumeGb = isset($rule['volume_gb']) ? (float) $rule['volume_gb'] : 0.0;
        if ($defaultVolumeGb <= 0) {
            return ['ok' => false, 'error' => 'not_eligible_or_no_stock'];
        }
        $durationDays = isset($rule['duration_days']) ? max(0, (int) $rule['duration_days']) : 0;
        $baseUrl = trim((string) ($service['panel_base_url'] ?? ''));
        $panelUsername = trim((string) ($service['panel_username'] ?? ''));
        $panelPassword = trim((string) ($service['panel_password'] ?? ''));
        $groupIds = $this->resolveDefaultGroupIds($baseUrl, $panelUsername, $panelPassword);
        if ($groupIds === []) {
            return ['ok' => false, 'error' => 'panel_ref_invalid'];
        }

        $this->pdo->beginTransaction();
        try {
            $purchaseId = $this->createPurchase($userId, null, null, 0, 'free_test', true, $serviceId, null);
            $username = $this->buildProvisionUsername($userId, $purchaseId);
            $provider = new PasarGuardProvisioningProvider(
                $baseUrl,
                $panelUsername,
                $panelPassword,
                $groupIds
            );
            $dataLimitBytes = (int) max(1, round($defaultVolumeGb * 1024 * 1024 * 1024));
            $expireAt = $durationDays > 0 ? (time() + ($durationDays * 86400)) : 0;
            $result = $provider->provisionUser($username, $dataLimitBytes, $expireAt, $groupIds);
            if (!($result['ok'] ?? false)) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => (string) ($result['error'] ?? 'panel_provision_failed')];
            }
            $subscriptionUrl = trim((string) ($result['subscription_url'] ?? ''));
            if ($subscriptionUrl === '') {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'subscription_missing'];
            }

            $now = gmdate('Y-m-d H:i:s');
            $insertClaim = $this->pdo->prepare(
                'INSERT INTO free_test_service_claims (user_id, service_id, purchase_id, claimed_at)
                 VALUES (:user_id, :service_id, :purchase_id, :claimed_at)'
            );
            $insertClaim->execute([
                'user_id' => $userId,
                'service_id' => $serviceId,
                'purchase_id' => $purchaseId,
                'claimed_at' => $now,
            ]);

            $this->recordUserServiceDelivery(
                $purchaseId,
                $userId,
                $serviceId,
                null,
                'panel',
                null,
                $subscriptionUrl,
                null,
                null,
                $defaultVolumeGb,
                $durationDays,
                null
            );
            $this->pdo->commit();
            return [
                'ok' => true,
                'service_id' => $serviceId,
                'purchase_id' => $purchaseId,
                'service_name' => (string) ($service['name'] ?? ''),
                'raw_payload' => $subscriptionUrl,
                'sub_link' => $subscriptionUrl,
            ];
        } catch (\Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'error' => 'claim_failed'];
        }
    }

    public function resetFreeTestQuotaForUser(int $userId, ?int $serviceId = null): void
    {
        if ($serviceId !== null && $serviceId > 0) {
            $stmt = $this->pdo->prepare('DELETE FROM free_test_service_claims WHERE user_id = :user_id AND service_id = :service_id');
            $stmt->execute(['user_id' => $userId, 'service_id' => $serviceId]);
            return;
        }

        $stmt = $this->pdo->prepare('DELETE FROM free_test_service_claims WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
    }

    public function resetFreeTestQuotaForService(int $serviceId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM free_test_service_claims WHERE service_id = :service_id');
        $stmt->execute(['service_id' => $serviceId]);
    }

    public function countAvailableFreeTestServices(int $userId): int
    {
        return count($this->listEnabledFreeTestServices(true, $userId));
    }

    public function countEnabledFreeTestServices(): int
    {
        return count($this->listEnabledFreeTestServices(false, null));
    }

    public function countFreeTestClaimsForService(int $serviceId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM free_test_service_claims WHERE service_id = :service_id');
        $stmt->execute(['service_id' => $serviceId]);
        return (int) $stmt->fetchColumn();
    }

    public function countAvailableFreeTestStockByService(int $serviceId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM service_stock_items
             WHERE service_id = :service_id
               AND inventory_bucket = 'free_test'
               AND sold_to IS NULL
               AND reserved_payment_id IS NULL
               AND is_expired = 0"
        );
        $stmt->execute(['service_id' => $serviceId]);
        return (int) $stmt->fetchColumn();
    }

    private function canUserClaimFreeTestForService(int $userId, int $serviceId, ?array $rule = null): bool
    {
        $evaluation = $this->evaluateFreeTestClaimForService($userId, $serviceId, $rule);
        return ($evaluation['ok'] ?? false) === true;
    }

    public function evaluateFreeTestClaimForService(int $userId, int $serviceId, ?array $rule = null, ?array $service = null): array
    {
        if ($userId <= 0 || $serviceId <= 0) {
            return ['ok' => false, 'error_code' => 'service_not_found'];
        }
        $service ??= $this->getService($serviceId);
        if (!is_array($service)) {
            return ['ok' => false, 'error_code' => 'service_not_found'];
        }
        if ((int) ($service['is_active'] ?? 0) !== 1) {
            return ['ok' => false, 'error_code' => 'service_inactive'];
        }
        $mode = (string) ($service['mode'] ?? '');
        if (!in_array($mode, ['stock', 'panel_auto'], true)) {
            return ['ok' => false, 'error_code' => 'invalid_service_mode'];
        }
        $rule ??= $this->getFreeTestRuleForService($serviceId);
        if (!is_array($rule) || (int) ($rule['is_enabled'] ?? 0) !== 1) {
            return ['ok' => false, 'error_code' => 'free_test_disabled'];
        }

        $groupIds = [];
        if ($mode === 'stock') {
            if ($this->countAvailableFreeTestStockByService($serviceId) <= 0) {
                return ['ok' => false, 'error_code' => 'free_test_stock_empty'];
            }
        } else {
            $volumeGb = isset($rule['volume_gb']) ? (float) $rule['volume_gb'] : 0.0;
            if ($volumeGb <= 0) {
                return ['ok' => false, 'error_code' => 'free_test_panel_config_invalid'];
            }
            $baseUrl = trim((string) ($service['panel_base_url'] ?? ''));
            $panelUsername = trim((string) ($service['panel_username'] ?? ''));
            $panelPassword = trim((string) ($service['panel_password'] ?? ''));
            if ($baseUrl === '' || $panelUsername === '' || $panelPassword === '') {
                return ['ok' => false, 'error_code' => 'free_test_panel_config_invalid'];
            }
            $groupIds = $this->resolveDefaultGroupIds($baseUrl, $panelUsername, $panelPassword);
            if ($groupIds === []) {
                return ['ok' => false, 'error_code' => 'free_test_panel_group_missing'];
            }
        }

        $claimMode = (string) ($rule['claim_mode'] ?? 'once_until_reset');
        $maxClaims = max(1, (int) ($rule['max_claims'] ?? 1));
        $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM free_test_service_claims WHERE user_id = :user_id AND service_id = :service_id');
        $countStmt->execute(['user_id' => $userId, 'service_id' => $serviceId]);
        $claimCount = (int) $countStmt->fetchColumn();
        if ($claimMode === 'once_until_reset') {
            if ($claimCount >= 1) {
                return ['ok' => false, 'error_code' => 'free_test_quota_exhausted'];
            }
            return ['ok' => true, 'mode' => $mode, 'service' => $service, 'rule' => $rule, 'group_ids' => $groupIds];
        }
        if ($claimMode !== 'cooldown') {
            return ['ok' => false, 'error_code' => 'free_test_claim_failed'];
        }
        if ($claimCount >= $maxClaims) {
            return ['ok' => false, 'error_code' => 'free_test_quota_exhausted'];
        }
        $cooldownDays = max(1, (int) ($rule['cooldown_days'] ?? 0));
        $lastStmt = $this->pdo->prepare(
            'SELECT claimed_at
             FROM free_test_service_claims
             WHERE user_id = :user_id AND service_id = :service_id
             ORDER BY id DESC LIMIT 1'
        );
        $lastStmt->execute(['user_id' => $userId, 'service_id' => $serviceId]);
        $lastAt = (string) ($lastStmt->fetchColumn() ?: '');
        if ($lastAt !== '') {
            $lastTs = strtotime($lastAt);
            $nextAllowed = $lastTs !== false ? strtotime('+' . $cooldownDays . ' days', $lastTs) : false;
            if ($nextAllowed !== false && time() < $nextAllowed) {
                $remaining = max(0, $nextAllowed - time());
                return [
                    'ok' => false,
                    'error_code' => 'free_test_cooldown_active',
                    'next_allowed_at' => gmdate('Y-m-d H:i:s', $nextAllowed),
                    'remaining_seconds' => $remaining,
                    'remaining_days' => (int) ceil($remaining / 86400),
                ];
            }
        }
        return ['ok' => true, 'mode' => $mode, 'service' => $service, 'rule' => $rule, 'group_ids' => $groupIds];
    }

    public function getServiceByCode(string $serviceCode): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.service_code, s.name, s.mode, s.panel_provider, s.panel_base_url, s.panel_username, s.panel_password, s.is_active
             FROM service s
             WHERE s.service_code = :code
             LIMIT 1'
        );
        $stmt->execute(['code' => trim($serviceCode)]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function listAllServices(): array
    {
        $stmt = $this->pdo->query(
            'SELECT s.id, s.service_code, s.name, s.mode, s.panel_provider, s.panel_base_url, s.panel_username, s.panel_password, s.is_active
             FROM service s
             ORDER BY s.id DESC'
        );
        return $stmt->fetchAll();
    }

    private function settingValue(string $key, string $default = ''): string
    {
        $stmt = $this->pdo->prepare('SELECT value FROM settings WHERE `key` = :key LIMIT 1');
        $stmt->execute(['key' => $key]);
        $value = $stmt->fetchColumn();
        if ($value === false || $value === null) {
            return $default;
        }
        return (string) $value;
    }

}
