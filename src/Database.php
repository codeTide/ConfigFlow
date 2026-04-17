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
            "CREATE TABLE IF NOT EXISTS free_test_package_rules (
                package_id BIGINT PRIMARY KEY,
                max_claims INT NOT NULL DEFAULT 1,
                cooldown_days INT NOT NULL DEFAULT 0,
                is_enabled TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_free_test_enabled (is_enabled)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS free_test_claims (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT NOT NULL,
                package_id BIGINT NOT NULL,
                purchase_id BIGINT NOT NULL,
                claimed_at DATETIME NOT NULL,
                INDEX idx_free_test_claims_user_pkg (user_id, package_id),
                INDEX idx_free_test_claims_pkg (package_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS panel (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                provider VARCHAR(64) NOT NULL DEFAULT 'pasarguard',
                base_url VARCHAR(255) NOT NULL,
                username VARCHAR(191) NOT NULL,
                password TEXT NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_panel_active (is_active),
                INDEX idx_panel_provider (provider)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS service (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                type_id BIGINT NOT NULL,
                name VARCHAR(255) NOT NULL,
                mode VARCHAR(32) NOT NULL DEFAULT 'stock',
                panel_id BIGINT NULL,
                panel_ref VARCHAR(255) NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_service_type (type_id),
                INDEX idx_service_mode (mode),
                INDEX idx_service_panel (panel_id),
                INDEX idx_service_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS service_tariff (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                service_id BIGINT NOT NULL,
                title VARCHAR(255) NOT NULL,
                pricing_mode VARCHAR(32) NOT NULL DEFAULT 'fixed',
                volume_gb DECIMAL(10,2) NULL,
                duration_days INT NULL,
                price INT NULL,
                min_volume_gb DECIMAL(10,2) NULL,
                max_volume_gb DECIMAL(10,2) NULL,
                step_volume_gb DECIMAL(10,2) NULL,
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
        if ($this->tableExists('configs')) {
            $this->pdo->exec("ALTER TABLE configs MODIFY package_id BIGINT NULL");
            $this->pdo->exec("ALTER TABLE configs ADD COLUMN IF NOT EXISTS service_id BIGINT NULL AFTER package_id");
            $this->pdo->exec("ALTER TABLE configs ADD COLUMN IF NOT EXISTS tariff_id BIGINT NULL AFTER service_id");
            $this->pdo->exec("ALTER TABLE configs ADD INDEX IF NOT EXISTS idx_configs_service (service_id)");
            $this->pdo->exec("ALTER TABLE configs ADD INDEX IF NOT EXISTS idx_configs_tariff (tariff_id)");
        }
        if ($this->tableExists('payments')) {
            $this->pdo->exec("ALTER TABLE payments ADD COLUMN IF NOT EXISTS service_id BIGINT NULL AFTER package_id");
            $this->pdo->exec("ALTER TABLE payments ADD COLUMN IF NOT EXISTS tariff_id BIGINT NULL AFTER service_id");
            $this->pdo->exec("ALTER TABLE payments ADD INDEX IF NOT EXISTS idx_payments_service (service_id)");
            $this->pdo->exec("ALTER TABLE payments ADD INDEX IF NOT EXISTS idx_payments_tariff (tariff_id)");
        }
        if ($this->tableExists('pending_orders')) {
            $this->pdo->exec("ALTER TABLE pending_orders ADD COLUMN IF NOT EXISTS tariff_id BIGINT NULL AFTER service_id");
            $this->pdo->exec("ALTER TABLE pending_orders ADD INDEX IF NOT EXISTS idx_pending_tariff (tariff_id)");
        }
        if ($this->tableExists('purchases')) {
            $this->pdo->exec("ALTER TABLE purchases ADD COLUMN IF NOT EXISTS service_id BIGINT NULL AFTER package_id");
            $this->pdo->exec("ALTER TABLE purchases ADD COLUMN IF NOT EXISTS tariff_id BIGINT NULL AFTER service_id");
            $this->pdo->exec("ALTER TABLE purchases ADD INDEX IF NOT EXISTS idx_purchases_service (service_id)");
            $this->pdo->exec("ALTER TABLE purchases ADD INDEX IF NOT EXISTS idx_purchases_tariff (tariff_id)");
        }
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
                    pkg.name AS package_name,
                    cfg.service_name AS service_name
             FROM purchases p
             LEFT JOIN packages pkg ON pkg.id = p.package_id
             LEFT JOIN configs cfg ON cfg.id = p.config_id
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
            'SELECT p.id AS purchase_id, p.is_test, p.package_id, p.config_id,
                    pkg.type_id, pkg.name AS package_name,
                    cfg.service_name
             FROM purchases p
             JOIN packages pkg ON pkg.id = p.package_id
             LEFT JOIN configs cfg ON cfg.id = p.config_id
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
    public function createFreeTestRequest(int $userId, string $note): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO free_test_requests (user_id, note, status, created_at)
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

    public function listPendingFreeTestRequests(int $limit = 30): array
    {
        return $this->listFreeTestRequestsByStatus('pending', $limit, 0);
    }

    public function listFreeTestRequestsByStatus(string $status, int $limit = 30, int $offset = 0): array
    {
        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, note, created_at
             FROM free_test_requests
             WHERE status = :status
             ORDER BY id DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset
        );
        $stmt->execute(['status' => $status]);
        return $stmt->fetchAll();
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

    public function countFreeTestRequestsByStatus(string $status): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM free_test_requests WHERE status = :status');
        $stmt->execute(['status' => $status]);
        return (int) $stmt->fetchColumn();
    }

    public function countAgencyRequestsByStatus(string $status): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM agency_requests WHERE status = :status');
        $stmt->execute(['status' => $status]);
        return (int) $stmt->fetchColumn();
    }

    public function getFreeTestRequestById(int $requestId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, note, status, created_at, reviewed_at, admin_note
             FROM free_test_requests
             WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $requestId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
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

    public function reviewFreeTestRequest(int $requestId, bool $approve, ?string $adminNote = null): array
    {
        $this->pdo->beginTransaction();
        try {
            $lockStmt = $this->pdo->prepare('SELECT id, user_id, status FROM free_test_requests WHERE id = :id LIMIT 1 FOR UPDATE');
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
                'UPDATE free_test_requests
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
        return $this->pdo->query('SELECT id, name FROM config_types WHERE is_active = 1 ORDER BY id ASC')->fetchAll();
    }

    public function listTypes(): array
    {
        return $this->pdo->query(
            'SELECT id, name, description, is_active
             FROM config_types
             ORDER BY id DESC'
        )->fetchAll();
    }

    public function addType(string $name, string $description = ''): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO config_types (name, description, is_active)
             VALUES (:name, :description, 1)'
        );
        $stmt->execute([
            'name' => trim($name),
            'description' => trim($description),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function listServicesByType(int $typeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.type_id, s.name, s.mode, s.panel_id, s.panel_ref, s.is_active,
                    p.name AS panel_name
             FROM service s
             LEFT JOIN panel p ON p.id = s.panel_id
             WHERE s.type_id = :type_id
             ORDER BY s.id DESC'
        );
        $stmt->execute(['type_id' => $typeId]);
        return $stmt->fetchAll();
    }

    public function listActiveServicesByType(int $typeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.type_id, s.name, s.mode, s.panel_id, s.panel_ref, s.is_active,
                    p.name AS panel_name
             FROM service s
             LEFT JOIN panel p ON p.id = s.panel_id
             WHERE s.type_id = :type_id AND s.is_active = 1
             ORDER BY s.id DESC'
        );
        $stmt->execute(['type_id' => $typeId]);
        return $stmt->fetchAll();
    }

    /** @param array<string,mixed> $data */
    public function createService(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO service (type_id, name, mode, panel_id, panel_ref, is_active, created_at, updated_at)
             VALUES (:type_id, :name, :mode, :panel_id, :panel_ref, :is_active, :created_at, :updated_at)'
        );
        $now = gmdate('Y-m-d H:i:s');
        $stmt->execute([
            'type_id' => (int) ($data['type_id'] ?? 0),
            'name' => trim((string) ($data['name'] ?? '')),
            'mode' => (string) ($data['mode'] ?? 'stock'),
            'panel_id' => isset($data['panel_id']) ? (int) $data['panel_id'] : null,
            'panel_ref' => isset($data['panel_ref']) ? trim((string) $data['panel_ref']) : null,
            'is_active' => ((int) ($data['is_active'] ?? 1)) === 1 ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function getService(int $serviceId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.type_id, s.name, s.mode, s.panel_id, s.panel_ref, s.is_active,
                    p.name AS panel_name, p.provider AS panel_provider
             FROM service s
             LEFT JOIN panel p ON p.id = s.panel_id
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

    /** @param array<string,mixed> $data */
    public function updateServiceBasic(int $serviceId, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE service
             SET name = :name, mode = :mode, panel_id = :panel_id, panel_ref = :panel_ref, is_active = :is_active, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'name' => trim((string) ($data['name'] ?? '')),
            'mode' => (string) ($data['mode'] ?? 'stock'),
            'panel_id' => isset($data['panel_id']) ? (int) $data['panel_id'] : null,
            'panel_ref' => isset($data['panel_ref']) ? trim((string) $data['panel_ref']) : null,
            'is_active' => ((int) ($data['is_active'] ?? 1)) === 1 ? 1 : 0,
            'updated_at' => gmdate('Y-m-d H:i:s'),
            'id' => $serviceId,
        ]);
    }

    public function listPanels(bool $onlyActive = true): array
    {
        $sql = 'SELECT id, name, provider, base_url, username, password, is_active
                FROM panel';
        if ($onlyActive) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY id DESC';
        return $this->pdo->query($sql)->fetchAll();
    }

    public function getPanel(int $panelId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, provider, base_url, username, password, is_active
             FROM panel
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $panelId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** @param array<string,mixed> $data */
    public function createPanel(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO panel (name, provider, base_url, username, password, is_active, created_at, updated_at)
             VALUES (:name, :provider, :base_url, :username, :password, :is_active, :created_at, :updated_at)'
        );
        $now = gmdate('Y-m-d H:i:s');
        $stmt->execute([
            'name' => trim((string) ($data['name'] ?? '')),
            'provider' => trim((string) ($data['provider'] ?? 'pasarguard')),
            'base_url' => trim((string) ($data['base_url'] ?? '')),
            'username' => trim((string) ($data['username'] ?? '')),
            'password' => trim((string) ($data['password'] ?? '')),
            'is_active' => ((int) ($data['is_active'] ?? 1)) === 1 ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function listTariffsByService(int $serviceId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, service_id, title, pricing_mode, volume_gb, duration_days, price, min_volume_gb, max_volume_gb, step_volume_gb, price_per_gb, duration_policy, is_active
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
            'SELECT id, service_id, title, pricing_mode, volume_gb, duration_days, price, min_volume_gb, max_volume_gb, step_volume_gb, price_per_gb, duration_policy, is_active
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
            'SELECT id, service_id, title, pricing_mode, volume_gb, duration_days, price, min_volume_gb, max_volume_gb, step_volume_gb, price_per_gb, duration_policy, is_active
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
            'SELECT id, service_id, title, pricing_mode, volume_gb, duration_days, price, min_volume_gb, max_volume_gb, step_volume_gb, price_per_gb, duration_policy, is_active
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
                service_id, title, pricing_mode, volume_gb, duration_days, price,
                min_volume_gb, max_volume_gb, step_volume_gb, price_per_gb, duration_policy, is_active, created_at, updated_at
             ) VALUES (
                :service_id, :title, :pricing_mode, :volume_gb, :duration_days, :price,
                :min_volume_gb, :max_volume_gb, :step_volume_gb, :price_per_gb, :duration_policy, :is_active, :created_at, :updated_at
             )'
        );
        $now = gmdate('Y-m-d H:i:s');
        $stmt->execute([
            'service_id' => (int) ($data['service_id'] ?? 0),
            'title' => trim((string) ($data['title'] ?? '')),
            'pricing_mode' => (string) ($data['pricing_mode'] ?? 'fixed'),
            'volume_gb' => isset($data['volume_gb']) ? (float) $data['volume_gb'] : null,
            'duration_days' => isset($data['duration_days']) ? (int) $data['duration_days'] : null,
            'price' => isset($data['price']) ? (int) $data['price'] : null,
            'min_volume_gb' => isset($data['min_volume_gb']) ? (float) $data['min_volume_gb'] : null,
            'max_volume_gb' => isset($data['max_volume_gb']) ? (float) $data['max_volume_gb'] : null,
            'step_volume_gb' => isset($data['step_volume_gb']) ? (float) $data['step_volume_gb'] : null,
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
             SET title = :title, pricing_mode = :pricing_mode, volume_gb = :volume_gb, duration_days = :duration_days, price = :price,
                 min_volume_gb = :min_volume_gb, max_volume_gb = :max_volume_gb, step_volume_gb = :step_volume_gb,
                 price_per_gb = :price_per_gb, duration_policy = :duration_policy, is_active = :is_active, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'title' => trim((string) ($data['title'] ?? '')),
            'pricing_mode' => (string) ($data['pricing_mode'] ?? 'fixed'),
            'volume_gb' => isset($data['volume_gb']) ? (float) $data['volume_gb'] : null,
            'duration_days' => isset($data['duration_days']) ? (int) $data['duration_days'] : null,
            'price' => isset($data['price']) ? (int) $data['price'] : null,
            'min_volume_gb' => isset($data['min_volume_gb']) ? (float) $data['min_volume_gb'] : null,
            'max_volume_gb' => isset($data['max_volume_gb']) ? (float) $data['max_volume_gb'] : null,
            'step_volume_gb' => isset($data['step_volume_gb']) ? (float) $data['step_volume_gb'] : null,
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
             WHERE s.type_id = :type_id AND s.is_active = 1'
        );
        $stmt->execute(['type_id' => $typeId]);
        return (int) $stmt->fetchColumn();
    }

    public function countAvailableConfigsByService(int $serviceId, ?int $tariffId = null): int
    {
        $sql = 'SELECT COUNT(*)
                FROM configs
                WHERE service_id = :service_id
                  AND sold_to IS NULL
                  AND reserved_payment_id IS NULL
                  AND is_expired = 0';
        $params = ['service_id' => $serviceId];
        if ($tariffId !== null && $tariffId > 0) {
            $sql .= ' AND tariff_id = :tariff_id';
            $params['tariff_id'] = $tariffId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function listConfigsByService(int $serviceId, ?int $tariffId = null, int $limit = 20, int $offset = 0): array
    {
        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);
        $sql = 'SELECT id, service_id, tariff_id, service_name, sold_to, is_expired, inquiry_link, created_at
                FROM configs
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

    public function countConfigsByService(int $serviceId, ?int $tariffId = null): int
    {
        $sql = 'SELECT COUNT(*) FROM configs WHERE service_id = :service_id';
        $params = ['service_id' => $serviceId];
        if ($tariffId !== null && $tariffId > 0) {
            $sql .= ' AND tariff_id = :tariff_id';
            $params['tariff_id'] = $tariffId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function addConfigForService(int $serviceId, ?int $tariffId, string $serviceName, string $configText, ?string $inquiryLink = null): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO configs (
                type_id, package_id, service_id, tariff_id, service_name, config_text, inquiry_link, created_at, reserved_payment_id, sold_to, purchase_id, sold_at, is_expired
             ) VALUES (
                0, NULL, :service_id, :tariff_id, :service_name, :config_text, :inquiry_link, :created_at, NULL, NULL, NULL, NULL, 0
             )'
        );
        $stmt->execute([
            'service_id' => $serviceId,
            'tariff_id' => $tariffId !== null && $tariffId > 0 ? $tariffId : null,
            'service_name' => trim($serviceName),
            'config_text' => trim($configText),
            'inquiry_link' => $inquiryLink !== null && trim($inquiryLink) !== '' ? trim($inquiryLink) : null,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function setTypeActive(int $typeId, bool $active): void
    {
        $stmt = $this->pdo->prepare('UPDATE config_types SET is_active = :active WHERE id = :id');
        $stmt->execute([
            'active' => $active ? 1 : 0,
            'id' => $typeId,
        ]);
    }

    public function deleteType(int $typeId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM config_types WHERE id = :id');
        $stmt->execute(['id' => $typeId]);
    }

    public function getActivePackagesByType(int $typeId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, price, volume_gb, duration_days FROM packages WHERE type_id = :type_id AND active = 1 ORDER BY id ASC');
        $stmt->execute(['type_id' => $typeId]);
        return $stmt->fetchAll();
    }

    public function listPackagesByType(int $typeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, type_id, name, price, volume_gb, duration_days, active
             FROM packages
             WHERE type_id = :type_id
             ORDER BY id DESC'
        );
        $stmt->execute(['type_id' => $typeId]);
        return $stmt->fetchAll();
    }

    public function addPackage(int $typeId, string $name, float $volumeGb, int $durationDays, int $price): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO packages (type_id, name, volume_gb, duration_days, price, active)
             VALUES (:type_id, :name, :volume_gb, :duration_days, :price, 1)'
        );
        $stmt->execute([
            'type_id' => $typeId,
            'name' => trim($name),
            'volume_gb' => $volumeGb,
            'duration_days' => $durationDays,
            'price' => $price,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function setPackageActive(int $packageId, bool $active): void
    {
        $stmt = $this->pdo->prepare('UPDATE packages SET active = :active WHERE id = :id');
        $stmt->execute([
            'active' => $active ? 1 : 0,
            'id' => $packageId,
        ]);
    }

    public function deletePackage(int $packageId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM packages WHERE id = :id');
        $stmt->execute(['id' => $packageId]);
    }

    public function countAvailableConfigsForPackage(int $packageId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM configs
             WHERE package_id = :package_id
               AND sold_to IS NULL
               AND reserved_payment_id IS NULL
               AND is_expired = 0'
        );
        $stmt->execute(['package_id' => $packageId]);
        return (int) $stmt->fetchColumn();
    }

    public function listConfigsByPackage(int $packageId, int $limit = 20, int $offset = 0): array
    {
        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);
        $stmt = $this->pdo->prepare(
            'SELECT id, service_name, sold_to, is_expired, inquiry_link, created_at
             FROM configs
             WHERE package_id = :package_id
             ORDER BY id DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset
        );
        $stmt->execute(['package_id' => $packageId]);
        return $stmt->fetchAll();
    }

    public function countConfigsByPackage(int $packageId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM configs WHERE package_id = :package_id');
        $stmt->execute(['package_id' => $packageId]);
        return (int) $stmt->fetchColumn();
    }

    public function countConfigsByPackageFiltered(int $packageId, string $status = 'all', ?string $query = null): int
    {
        [$where, $params] = $this->buildConfigFilterSql($packageId, $status, $query);
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM configs WHERE ' . $where);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function listConfigsByPackageFiltered(
        int $packageId,
        string $status = 'all',
        ?string $query = null,
        int $limit = 20,
        int $offset = 0
    ): array {
        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);
        [$where, $params] = $this->buildConfigFilterSql($packageId, $status, $query);
        $stmt = $this->pdo->prepare(
            'SELECT id, service_name, sold_to, is_expired, inquiry_link, created_at
             FROM configs
             WHERE ' . $where . '
             ORDER BY id DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function addConfig(int $typeId, int $packageId, string $serviceName, string $configText, ?string $inquiryLink = null): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO configs (
                type_id, package_id, service_name, config_text, inquiry_link, created_at, reserved_payment_id, sold_to, purchase_id, sold_at, is_expired
             ) VALUES (
                :type_id, :package_id, :service_name, :config_text, :inquiry_link, :created_at, NULL, NULL, NULL, NULL, 0
             )'
        );
        $stmt->execute([
            'type_id' => $typeId,
            'package_id' => $packageId,
            'service_name' => trim($serviceName),
            'config_text' => trim($configText),
            'inquiry_link' => $inquiryLink !== null && trim($inquiryLink) !== '' ? trim($inquiryLink) : null,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function expireConfig(int $configId): void
    {
        $stmt = $this->pdo->prepare('UPDATE configs SET is_expired = 1 WHERE id = :id');
        $stmt->execute(['id' => $configId]);
    }

    public function unexpireConfig(int $configId): void
    {
        $stmt = $this->pdo->prepare('UPDATE configs SET is_expired = 0 WHERE id = :id');
        $stmt->execute(['id' => $configId]);
    }

    public function deleteConfig(int $configId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM configs
             WHERE id = :id
               AND sold_to IS NULL
               AND reserved_payment_id IS NULL'
        );
        $stmt->execute(['id' => $configId]);
        return $stmt->rowCount() > 0;
    }

    public function getPackage(int $packageId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, type_id, name, price, volume_gb, duration_days FROM packages WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $packageId]);
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

    public function listAllPackages(): array
    {
        return $this->pdo->query(
            'SELECT id, type_id, name, price, volume_gb, duration_days, active
             FROM packages
             ORDER BY id DESC'
        )->fetchAll();
    }

    public function getAgencyPrice(int $userId, int $packageId): ?int
    {
        $stmt = $this->pdo->prepare(
            'SELECT price FROM agency_prices
             WHERE user_id = :user_id AND package_id = :package_id
             LIMIT 1'
        );
        $stmt->execute([
            'user_id' => $userId,
            'package_id' => $packageId,
        ]);
        $val = $stmt->fetchColumn();
        return $val === false ? null : (int) $val;
    }

    public function setAgencyPrice(int $userId, int $packageId, int $price): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO agency_prices (user_id, package_id, price)
             VALUES (:user_id, :package_id, :price)
             ON DUPLICATE KEY UPDATE price = VALUES(price)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'package_id' => $packageId,
            'price' => $price,
        ]);
    }

    public function clearAgencyPrice(int $userId, int $packageId): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM agency_prices
             WHERE user_id = :user_id AND package_id = :package_id'
        );
        $stmt->execute([
            'user_id' => $userId,
            'package_id' => $packageId,
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

    private function buildConfigFilterSql(int $packageId, string $status, ?string $query): array
    {
        $where = ['package_id = :package_id'];
        $params = ['package_id' => $packageId];

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
            $where[] = '(service_name LIKE :q OR config_text LIKE :q OR inquiry_link LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        return [implode(' AND ', $where), $params];
    }

    public function createPayment(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO payments (kind, user_id, package_id, service_id, tariff_id, amount, payment_method, gateway_ref, status, created_at)
             VALUES (:kind, :user_id, :package_id, :service_id, :tariff_id, :amount, :payment_method, :gateway_ref, :status, :created_at)'
        );
        $stmt->execute([
            'kind' => $data['kind'],
            'user_id' => $data['user_id'],
            'package_id' => $data['package_id'] ?? null,
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

    public function walletPayPackage(int $userId, int $packageId): array
    {
        $package = $this->getPackage($packageId);
        if ($package === null) {
            return ['ok' => false, 'error' => 'package_not_found'];
        }

        if ($this->deliveryMode() === self::DELIVERY_MODE_STOCK_ONLY && !$this->packageHasAvailableStock($packageId)) {
            return ['ok' => false, 'error' => 'no_stock'];
        }

        $price = $this->effectivePackagePrice($userId, $package);
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
                'kind' => 'purchase',
                'user_id' => $userId,
                'package_id' => $packageId,
                'amount' => $price,
                'payment_method' => 'wallet',
                'status' => 'paid',
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);

            $pendingId = $this->createPendingOrder([
                'user_id' => $userId,
                'package_id' => $packageId,
                'order_mode' => self::DELIVERY_MODE_STOCK_ONLY,
                'service_id' => null,
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
                'package_id' => null,
                'amount' => $amount,
                'payment_method' => 'wallet',
                'status' => 'paid',
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);

            $pendingId = $this->createPendingOrder([
                'user_id' => $userId,
                'package_id' => null,
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

    public function walletPayServiceTariff(int $userId, int $serviceId, int $tariffId, ?float $selectedVolumeGb = null): array
    {
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
                'package_id' => null,
                'service_id' => $serviceId,
                'tariff_id' => $tariffId,
                'amount' => $amount,
                'payment_method' => 'wallet',
                'status' => 'paid',
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);

            $pendingId = $this->createPendingOrder([
                'user_id' => $userId,
                'package_id' => null,
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

    public function walletPayRenewal(int $userId, int $purchaseId, int $packageId): array
    {
        $purchase = $this->getUserPurchaseForRenewal($userId, $purchaseId);
        if (!is_array($purchase)) {
            return ['ok' => false, 'error' => 'purchase_not_found'];
        }
        if ((int) ($purchase['is_test'] ?? 0) === 1) {
            return ['ok' => false, 'error' => 'test_not_renewable'];
        }
        $package = $this->getPackage($packageId);
        if ($package === null) {
            return ['ok' => false, 'error' => 'package_not_found'];
        }
        if ((int) ($purchase['type_id'] ?? 0) !== (int) ($package['type_id'] ?? -1)) {
            return ['ok' => false, 'error' => 'type_mismatch'];
        }

        $price = $this->effectivePackagePrice($userId, $package);
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
                'package_id' => $packageId,
                'amount' => $price,
                'payment_method' => 'wallet',
                'status' => 'paid',
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);

            $pendingId = $this->createPendingOrder([
                'user_id' => $userId,
                'package_id' => $packageId,
                'order_mode' => self::DELIVERY_MODE_STOCK_ONLY,
                'service_id' => null,
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
            'INSERT INTO pending_orders (user_id, package_id, order_mode, service_id, tariff_id, selected_volume_gb, computed_amount, payment_id, amount, payment_method, created_at, status)
             VALUES (:user_id, :package_id, :order_mode, :service_id, :tariff_id, :selected_volume_gb, :computed_amount, :payment_id, :amount, :payment_method, :created_at, :status)'
        );
        $stmt->execute([
            'user_id' => (int) ($data['user_id'] ?? 0),
            'package_id' => isset($data['package_id']) ? (int) $data['package_id'] : null,
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
            "SELECT id, user_id, package_id, order_mode, service_id, tariff_id, selected_volume_gb, computed_amount, payment_id, amount, created_at
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
                "SELECT id, user_id, package_id, order_mode, service_id, tariff_id, selected_volume_gb, computed_amount, payment_id, amount, payment_method, status
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
            if ($serviceId > 0 && !($orderMode === self::DELIVERY_MODE_PANEL_ONLY && (int) ($order['tariff_id'] ?? 0) <= 0)) {
                return $this->finalizeServiceDelivery($order);
            }

            if ($mode === self::DELIVERY_MODE_STOCK_ONLY) {
                $config = $this->findAvailableConfigForPackage((int) $order['package_id']);
                if (!is_array($config)) {
                    $this->pdo->rollBack();
                    return ['ok' => false, 'error' => 'out_of_stock'];
                }
                return $this->finalizeStockDelivery($order, $config);
            }

            return $this->finalizePanelOnlyDelivery($order);
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'error' => 'db_error'];
        }
    }

    private function findAvailableConfigForPackage(int $packageId): mixed
    {
        $configStmt = $this->pdo->prepare(
            "SELECT id, service_name, config_text, inquiry_link
             FROM configs
             WHERE package_id = :package_id
               AND sold_to IS NULL
               AND reserved_payment_id IS NULL
               AND is_expired = 0
             ORDER BY id ASC
             LIMIT 1
             FOR UPDATE"
        );
        $configStmt->execute(['package_id' => $packageId]);
        return $configStmt->fetch();
    }

    private function findAvailableConfigForService(int $serviceId, ?int $tariffId = null): mixed
    {
        $sql = "SELECT id, service_name, config_text, inquiry_link
                FROM configs
                WHERE service_id = :service_id
                  AND sold_to IS NULL
                  AND reserved_payment_id IS NULL
                  AND is_expired = 0";
        $params = ['service_id' => $serviceId];
        if ($tariffId !== null && $tariffId > 0) {
            $sql .= ' AND tariff_id = :tariff_id';
            $params['tariff_id'] = $tariffId;
        }
        $sql .= ' ORDER BY id ASC LIMIT 1 FOR UPDATE';
        $configStmt = $this->pdo->prepare($sql);
        $configStmt->execute($params);
        return $configStmt->fetch();
    }

    private function serviceHasAvailableStock(int $serviceId, ?int $tariffId = null): bool
    {
        $sql = 'SELECT 1
                FROM configs
                WHERE service_id = :service_id
                  AND sold_to IS NULL
                  AND reserved_payment_id IS NULL
                  AND is_expired = 0';
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
        $step = (float) ($tariff['step_volume_gb'] ?? 0);
        if ($volumeGb <= 0 || $min <= 0 || $max < $min || $step <= 0) {
            return false;
        }
        if ($volumeGb < $min || $volumeGb > $max) {
            return false;
        }
        $ratio = ($volumeGb - $min) / $step;
        return abs($ratio - round($ratio)) < 0.00001;
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
            $config = $this->findAvailableConfigForService($serviceId, $tariffId > 0 ? $tariffId : null);
            if (!is_array($config)) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'out_of_stock'];
            }
            return $this->finalizeStockDelivery($order, $config);
        }

        if ($mode !== 'panel_auto') {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => 'unsupported_service_mode'];
        }

        $panelId = (int) ($service['panel_id'] ?? 0);
        $panel = $this->getPanel($panelId);
        if (!is_array($panel) || (int) ($panel['is_active'] ?? 0) !== 1) {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => 'panel_not_found'];
        }
        $groupIds = $this->parseGroupIds((string) ($service['panel_ref'] ?? ''));
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
            (string) ($panel['base_url'] ?? ''),
            (string) ($panel['username'] ?? ''),
            (string) ($panel['password'] ?? ''),
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

        $configInsert = $this->pdo->prepare(
            'INSERT INTO configs (type_id, package_id, service_id, tariff_id, service_name, config_text, inquiry_link, created_at, reserved_payment_id, sold_to, purchase_id, sold_at, is_expired)
             VALUES (:type_id, NULL, :service_id, :tariff_id, :service_name, :config_text, :inquiry_link, :created_at, NULL, NULL, NULL, NULL, 0)'
        );
        $configInsert->execute([
            'type_id' => (int) ($service['type_id'] ?? 0),
            'service_id' => $serviceId,
            'tariff_id' => $tariffId,
            'service_name' => (string) ($service['name'] ?? ''),
            'config_text' => $subscriptionUrl,
            'inquiry_link' => $subscriptionUrl,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
        $configId = (int) $this->pdo->lastInsertId();

        $purchaseId = $this->createPurchase(
            (int) ($order['user_id'] ?? 0),
            null,
            $configId,
            (int) ($order['amount'] ?? 0),
            (string) ($order['payment_method'] ?? 'panel'),
            false,
            $serviceId,
            $tariffId
        );

        $cfgUpdate = $this->pdo->prepare(
            'UPDATE configs SET sold_to = :sold_to, purchase_id = :purchase_id, sold_at = :sold_at WHERE id = :id'
        );
        $cfgUpdate->execute([
            'sold_to' => (int) ($order['user_id'] ?? 0),
            'purchase_id' => $purchaseId,
            'sold_at' => gmdate('Y-m-d H:i:s'),
            'id' => $configId,
        ]);

        $this->markOrderDelivered((int) ($order['id'] ?? 0), (int) ($order['payment_id'] ?? 0));
        $this->pdo->commit();
        return [
            'ok' => true,
            'user_id' => (int) ($order['user_id'] ?? 0),
            'config_text' => $subscriptionUrl,
            'service_name' => (string) ($service['name'] ?? ''),
            'inquiry_link' => $subscriptionUrl,
        ];
    }

    private function finalizePanelOnlyDelivery(array $order): array
    {
        $serviceId = (int) ($order['service_id'] ?? 0);
        $service = $this->getProvisioningService($serviceId);
        if (!is_array($service)) {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => 'service_not_found'];
        }

        $provider = $this->buildPasarGuardProvider($service);
        if (!is_array($provider)) {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => 'provider_not_configured'];
        }

        $username = $this->buildProvisionUsername((int) ($order['user_id'] ?? 0), (int) ($order['id'] ?? 0));
        $volumeGb = (float) ($order['selected_volume_gb'] ?? 0);
        if ($volumeGb <= 0) {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => 'invalid_volume'];
        }

        $durationPolicy = (string) ($service['duration_policy'] ?? 'fixed_days');
        $durationDays = $durationPolicy === 'fixed_days' ? (int) ($service['duration_days'] ?? 0) : 0;
        $dataLimitBytes = (int) max(1, round($volumeGb * 1024 * 1024 * 1024));
        $expireAt = $durationDays > 0 ? (time() + ($durationDays * 86400)) : 0;

        /** @var ProvisioningProviderInterface $providerService */
        $providerService = $provider['service'];
        $result = $providerService->provisionUser($username, $dataLimitBytes, $expireAt, $provider['group_ids']);
        if (!($result['ok'] ?? false)) {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => (string) ($result['error'] ?? 'panel_provision_failed')];
        }

        $subscriptionUrl = trim((string) ($result['subscription_url'] ?? ''));
        if ($subscriptionUrl === '') {
            $this->pdo->rollBack();
            return ['ok' => false, 'error' => 'subscription_missing'];
        }

        $configInsert = $this->pdo->prepare(
            'INSERT INTO configs (type_id, package_id, service_name, config_text, inquiry_link, created_at, reserved_payment_id, sold_to, purchase_id, sold_at, is_expired)
             VALUES (:type_id, :package_id, :service_name, :config_text, :inquiry_link, :created_at, NULL, NULL, NULL, NULL, 0)'
        );
        $configInsert->execute([
            'type_id' => 0,
            'package_id' => 0,
            'service_name' => (string) ($service['title'] ?? ''),
            'config_text' => $subscriptionUrl,
            'inquiry_link' => $subscriptionUrl,
            'created_at' => gmdate('Y-m-d H:i:s'),
        ]);
        $configId = (int) $this->pdo->lastInsertId();

        $purchaseId = $this->createPurchase(
            (int) ($order['user_id'] ?? 0),
            null,
            $configId,
            (int) ($order['amount'] ?? 0),
            (string) ($order['payment_method'] ?? 'panel')
        );

        $cfgUpdate = $this->pdo->prepare(
            'UPDATE configs SET sold_to = :sold_to, purchase_id = :purchase_id, sold_at = :sold_at WHERE id = :id'
        );
        $cfgUpdate->execute([
            'sold_to' => (int) ($order['user_id'] ?? 0),
            'purchase_id' => $purchaseId,
            'sold_at' => gmdate('Y-m-d H:i:s'),
            'id' => $configId,
        ]);

        $this->markOrderDelivered((int) ($order['id'] ?? 0), (int) ($order['payment_id'] ?? 0));
        $this->pdo->commit();

        return [
            'ok' => true,
            'user_id' => (int) ($order['user_id'] ?? 0),
            'config_text' => $subscriptionUrl,
            'service_name' => (string) ($service['title'] ?? ''),
            'inquiry_link' => $subscriptionUrl,
        ];
    }

    private function buildProvisionUsername(int $userId, int $orderId): string
    {
        return 'cf-' . $userId . '-' . $orderId . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
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
            'SELECT id, title, description, min_gb, max_gb, step_gb, price_per_gb, duration_policy, duration_days, provider, provider_group_ids, is_active
             FROM provisioning_services
             WHERE is_active = 1 AND provider = :provider
             ORDER BY id ASC'
        );
        $stmt->execute(['provider' => $provider]);
        return $stmt->fetchAll();
    }

    public function getProvisioningService(int $serviceId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, title, description, min_gb, max_gb, step_gb, price_per_gb, duration_policy, duration_days, provider, provider_group_ids, is_active
             FROM provisioning_services
             WHERE id = :id AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute(['id' => $serviceId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function listProvisioningServicesAll(): array
    {
        return $this->pdo->query(
            'SELECT id, title, description, min_gb, max_gb, step_gb, price_per_gb, duration_policy, duration_days, provider, provider_group_ids, is_active
             FROM provisioning_services
             ORDER BY id DESC'
        )->fetchAll();
    }

    /** @param array<string,mixed> $data */
    public function createProvisioningService(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO provisioning_services (title, description, min_gb, max_gb, step_gb, price_per_gb, duration_policy, duration_days, provider, provider_group_ids, is_active, created_at, updated_at)
             VALUES (:title, :description, :min_gb, :max_gb, :step_gb, :price_per_gb, :duration_policy, :duration_days, :provider, :provider_group_ids, :is_active, :created_at, :updated_at)'
        );
        $now = gmdate('Y-m-d H:i:s');
        $stmt->execute([
            'title' => trim((string) ($data['title'] ?? '')),
            'description' => trim((string) ($data['description'] ?? '')),
            'min_gb' => (float) ($data['min_gb'] ?? 0),
            'max_gb' => (float) ($data['max_gb'] ?? 0),
            'step_gb' => (float) ($data['step_gb'] ?? 1),
            'price_per_gb' => (int) ($data['price_per_gb'] ?? 0),
            'duration_policy' => (string) ($data['duration_policy'] ?? 'fixed_days'),
            'duration_days' => isset($data['duration_days']) ? (int) $data['duration_days'] : null,
            'provider' => trim((string) ($data['provider'] ?? 'pasarguard')),
            'provider_group_ids' => trim((string) ($data['provider_group_ids'] ?? '')),
            'is_active' => ((int) ($data['is_active'] ?? 1)) === 1 ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string,mixed> $data */
    public function updateProvisioningService(int $serviceId, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE provisioning_services
             SET title = :title, description = :description, min_gb = :min_gb, max_gb = :max_gb, step_gb = :step_gb, price_per_gb = :price_per_gb,
                 duration_policy = :duration_policy, duration_days = :duration_days, provider = :provider, provider_group_ids = :provider_group_ids,
                 is_active = :is_active, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'title' => trim((string) ($data['title'] ?? '')),
            'description' => trim((string) ($data['description'] ?? '')),
            'min_gb' => (float) ($data['min_gb'] ?? 0),
            'max_gb' => (float) ($data['max_gb'] ?? 0),
            'step_gb' => (float) ($data['step_gb'] ?? 1),
            'price_per_gb' => (int) ($data['price_per_gb'] ?? 0),
            'duration_policy' => (string) ($data['duration_policy'] ?? 'fixed_days'),
            'duration_days' => isset($data['duration_days']) ? (int) $data['duration_days'] : null,
            'provider' => trim((string) ($data['provider'] ?? 'pasarguard')),
            'provider_group_ids' => trim((string) ($data['provider_group_ids'] ?? '')),
            'is_active' => ((int) ($data['is_active'] ?? 1)) === 1 ? 1 : 0,
            'updated_at' => gmdate('Y-m-d H:i:s'),
            'id' => $serviceId,
        ]);
    }

    public function updateProvisioningServiceActive(int $serviceId, bool $active): void
    {
        $stmt = $this->pdo->prepare('UPDATE provisioning_services SET is_active = :active, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'active' => $active ? 1 : 0,
            'updated_at' => gmdate('Y-m-d H:i:s'),
            'id' => $serviceId,
        ]);
    }

    public function deleteProvisioningService(int $serviceId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM provisioning_services WHERE id = :id');
        $stmt->execute(['id' => $serviceId]);
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

    private function finalizeStockDelivery(array $order, array $config): array
    {
        $purchaseId = $this->createPurchase(
            (int) $order['user_id'],
            (int) $order['package_id'],
            (int) $config['id'],
            (int) $order['amount'],
            (string) $order['payment_method'],
            false,
            isset($order['service_id']) ? (int) $order['service_id'] : null,
            isset($order['tariff_id']) ? (int) $order['tariff_id'] : null
        );

        $cfgUpdate = $this->pdo->prepare(
            'UPDATE configs
             SET sold_to = :sold_to, purchase_id = :purchase_id, sold_at = :sold_at
             WHERE id = :id'
        );
        $cfgUpdate->execute([
            'sold_to' => (int) $order['user_id'],
            'purchase_id' => $purchaseId,
            'sold_at' => gmdate('Y-m-d H:i:s'),
            'id' => (int) $config['id'],
        ]);

        $this->markOrderDelivered((int) $order['id'], (int) ($order['payment_id'] ?? 0));
        $this->pdo->commit();

        return [
            'ok' => true,
            'user_id' => (int) $order['user_id'],
            'config_text' => (string) $config['config_text'],
            'service_name' => (string) ($config['service_name'] ?? ''),
            'inquiry_link' => (string) ($config['inquiry_link'] ?? ''),
        ];
    }

    private function createPurchase(int $userId, ?int $packageId, int $configId, int $amount, string $paymentMethod, bool $isTest = false, ?int $serviceId = null, ?int $tariffId = null): int
    {
        $purchaseStmt = $this->pdo->prepare(
            'INSERT INTO purchases (user_id, package_id, service_id, tariff_id, config_id, amount, payment_method, created_at, is_test)
             VALUES (:user_id, :package_id, :service_id, :tariff_id, :config_id, :amount, :payment_method, :created_at, :is_test)'
        );
        $purchaseStmt->execute([
            'user_id' => $userId,
            'package_id' => $packageId,
            'service_id' => $serviceId,
            'tariff_id' => $tariffId,
            'config_id' => $configId,
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
        $stmt = $this->pdo->prepare('SELECT id, user_id, package_id, service_id, tariff_id, amount, payment_method, gateway_ref, tx_hash, crypto_amount_claimed, status, verify_attempts, last_verify_at FROM payments WHERE id = :id LIMIT 1');
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

    public function getActivePackagesByTypeWithStock(int $typeId, bool $stockOnly = false): array
    {
        $sql = 'SELECT p.id, p.type_id, p.name, p.price, p.volume_gb, p.duration_days,
'
            . '       (SELECT COUNT(*) FROM configs c WHERE c.package_id = p.id AND c.sold_to IS NULL AND c.reserved_payment_id IS NULL AND c.is_expired = 0) AS stock
'
            . 'FROM packages p WHERE p.type_id = :type_id AND p.active = 1';
        if ($stockOnly) {
            $sql .= ' HAVING stock > 0';
        }
        $sql .= ' ORDER BY p.id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['type_id' => $typeId]);
        return $stmt->fetchAll();
    }

    public function getAgencyPriceConfig(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT price_mode, global_type, global_val FROM agency_price_config WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return ['price_mode' => 'package', 'global_type' => 'pct', 'global_val' => 0];
        }
        return [
            'price_mode' => (string) ($row['price_mode'] ?? 'package'),
            'global_type' => (string) ($row['global_type'] ?? 'pct'),
            'global_val' => (int) ($row['global_val'] ?? 0),
        ];
    }

    public function getAgencyTypeDiscount(int $userId, int $typeId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT discount_type, discount_value FROM agency_type_discount WHERE user_id = :user_id AND type_id = :type_id LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId, 'type_id' => $typeId]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return null;
        }
        return [
            'discount_type' => (string) ($row['discount_type'] ?? 'pct'),
            'discount_value' => (int) ($row['discount_value'] ?? 0),
        ];
    }

    public function effectivePackagePrice(int $userId, array $package): int
    {
        $base = (int) ($package['price'] ?? 0);
        $user = $this->getUser($userId);
        if (!is_array($user) || (int) ($user['is_agent'] ?? 0) !== 1) {
            return $base;
        }

        $packageId = (int) ($package['id'] ?? 0);
        $typeId = (int) ($package['type_id'] ?? 0);

        // Python-parity precedence: package > type > global
        $pkgCustom = $this->getAgencyPrice($userId, $packageId);
        if ($pkgCustom !== null) {
            return max(0, $pkgCustom);
        }

        $typeDiscount = $this->getAgencyTypeDiscount($userId, $typeId);
        if ($typeDiscount !== null) {
            $dType = (string) ($typeDiscount['discount_type'] ?? 'pct');
            $dValue = (int) ($typeDiscount['discount_value'] ?? 0);
            return $dType === 'pct'
                ? max(0, $base - (int) round($base * $dValue / 100))
                : max(0, $base - $dValue);
        }

        $config = $this->getAgencyPriceConfig($userId);
        $gType = (string) ($config['global_type'] ?? 'pct');
        $gVal = (int) ($config['global_val'] ?? 0);
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



    public function packageHasAvailableStock(int $packageId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM configs WHERE package_id = :package_id AND sold_to IS NULL AND reserved_payment_id IS NULL AND is_expired = 0 LIMIT 1'
        );
        $stmt->execute(['package_id' => $packageId]);
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

        $packageId = (int) $this->settingValue($prefix . '_package', '0');
        if ($packageId <= 0) {
            return;
        }

        $cfgStmt = $this->pdo->prepare(
            'SELECT id FROM configs WHERE package_id = :package_id AND sold_to IS NULL AND reserved_payment_id IS NULL AND is_expired = 0 ORDER BY id ASC LIMIT 1 FOR UPDATE'
        );
        $cfgStmt->execute(['package_id' => $packageId]);
        $cfgId = (int) ($cfgStmt->fetchColumn() ?: 0);
        if ($cfgId <= 0) {
            return;
        }

        $purchaseId = $this->createPurchase($referrerId, $packageId, $cfgId, 0, 'referral_gift');
        $cfgUpdate = $this->pdo->prepare('UPDATE configs SET sold_to = :sold_to, purchase_id = :purchase_id, sold_at = :sold_at WHERE id = :id');
        $cfgUpdate->execute([
            'sold_to' => $referrerId,
            'purchase_id' => $purchaseId,
            'sold_at' => gmdate('Y-m-d H:i:s'),
            'id' => $cfgId,
        ]);
    }

    public function listFreeTestRules(): array
    {
        $stmt = $this->pdo->query(
            "SELECT r.package_id, r.max_claims, r.cooldown_days, r.is_enabled, p.name AS package_name
             FROM free_test_package_rules r
             LEFT JOIN packages p ON p.id = r.package_id
             ORDER BY r.package_id ASC"
        );
        return $stmt->fetchAll();
    }

    public function saveFreeTestRule(int $packageId, int $maxClaims, int $cooldownDays, bool $enabled): void
    {
        $maxClaims = max(0, $maxClaims);
        $cooldownDays = max(0, $cooldownDays);
        $now = gmdate('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO free_test_package_rules (package_id, max_claims, cooldown_days, is_enabled, created_at, updated_at)
             VALUES (:package_id, :max_claims, :cooldown_days, :is_enabled, :created_at, :updated_at)
             ON DUPLICATE KEY UPDATE
                max_claims = VALUES(max_claims),
                cooldown_days = VALUES(cooldown_days),
                is_enabled = VALUES(is_enabled),
                updated_at = VALUES(updated_at)'
        );
        $stmt->execute([
            'package_id' => $packageId,
            'max_claims' => $maxClaims,
            'cooldown_days' => $cooldownDays,
            'is_enabled' => $enabled ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function resetFreeTestQuota(int $targetUserId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM free_test_claims WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $targetUserId]);
    }

    public function claimFreeTest(int $userId): array
    {
        $rules = $this->listFreeTestRules();
        if ($rules === []) {
            return ['ok' => false, 'error' => 'no_rule'];
        }

        $now = gmdate('Y-m-d H:i:s');
        foreach ($rules as $rule) {
            if ((int) ($rule['is_enabled'] ?? 0) !== 1) {
                continue;
            }
            $packageId = (int) ($rule['package_id'] ?? 0);
            if ($packageId <= 0) {
                continue;
            }

            $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM free_test_claims WHERE user_id = :user_id AND package_id = :package_id');
            $countStmt->execute(['user_id' => $userId, 'package_id' => $packageId]);
            $claimCount = (int) $countStmt->fetchColumn();
            $maxClaims = (int) ($rule['max_claims'] ?? 1);
            if ($maxClaims > 0 && $claimCount >= $maxClaims) {
                continue;
            }

            $cooldownDays = (int) ($rule['cooldown_days'] ?? 0);
            if ($cooldownDays > 0) {
                $lastStmt = $this->pdo->prepare(
                    'SELECT claimed_at FROM free_test_claims WHERE user_id = :user_id AND package_id = :package_id ORDER BY id DESC LIMIT 1'
                );
                $lastStmt->execute(['user_id' => $userId, 'package_id' => $packageId]);
                $lastAt = (string) ($lastStmt->fetchColumn() ?: '');
                if ($lastAt !== '') {
                    $lastTs = strtotime($lastAt);
                    if ($lastTs !== false) {
                        $nextAllowed = strtotime('+' . $cooldownDays . ' days', $lastTs);
                        if ($nextAllowed !== false && time() < $nextAllowed) {
                            continue;
                        }
                    }
                }
            }

            $this->pdo->beginTransaction();
            try {
                $cfgStmt = $this->pdo->prepare(
                    'SELECT id, service_name, config_text, inquiry_link
                     FROM configs
                     WHERE package_id = :package_id AND sold_to IS NULL AND reserved_payment_id IS NULL AND is_expired = 0
                     ORDER BY id ASC LIMIT 1 FOR UPDATE'
                );
                $cfgStmt->execute(['package_id' => $packageId]);
                $cfg = $cfgStmt->fetch();
                if (!is_array($cfg)) {
                    $this->pdo->rollBack();
                    continue;
                }

                $configId = (int) ($cfg['id'] ?? 0);
                $purchaseId = $this->createPurchase($userId, $packageId, $configId, 0, 'free_test', true);
                $updateCfg = $this->pdo->prepare('UPDATE configs SET sold_to = :sold_to, purchase_id = :purchase_id, sold_at = :sold_at WHERE id = :id');
                $updateCfg->execute([
                    'sold_to' => $userId,
                    'purchase_id' => $purchaseId,
                    'sold_at' => $now,
                    'id' => $configId,
                ]);

                $insertClaim = $this->pdo->prepare(
                    'INSERT INTO free_test_claims (user_id, package_id, purchase_id, claimed_at)
                     VALUES (:user_id, :package_id, :purchase_id, :claimed_at)'
                );
                $insertClaim->execute([
                    'user_id' => $userId,
                    'package_id' => $packageId,
                    'purchase_id' => $purchaseId,
                    'claimed_at' => $now,
                ]);

                $this->pdo->commit();
                return [
                    'ok' => true,
                    'package_id' => $packageId,
                    'purchase_id' => $purchaseId,
                    'service_name' => (string) ($cfg['service_name'] ?? ''),
                    'config_text' => (string) ($cfg['config_text'] ?? ''),
                    'inquiry_link' => (string) ($cfg['inquiry_link'] ?? ''),
                ];
            } catch (\Throwable $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
            }
        }

        return ['ok' => false, 'error' => 'not_eligible_or_no_stock'];
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
