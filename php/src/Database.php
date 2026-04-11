<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

use PDO;

final class Database
{
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

    public function countUserPurchases(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM purchases WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);

        return (int) $stmt->fetchColumn();
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

    public function getActivePackagesByType(int $typeId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, price, volume_gb, duration_days FROM packages WHERE type_id = :type_id AND active = 1 ORDER BY id ASC');
        $stmt->execute(['type_id' => $typeId]);
        return $stmt->fetchAll();
    }

    public function getPackage(int $packageId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, type_id, name, price, volume_gb, duration_days FROM packages WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $packageId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function createPayment(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO payments (kind, user_id, package_id, amount, payment_method, gateway_ref, status, created_at)
             VALUES (:kind, :user_id, :package_id, :amount, :payment_method, :gateway_ref, :status, :created_at)'
        );
        $stmt->execute([
            'kind' => $data['kind'],
            'user_id' => $data['user_id'],
            'package_id' => $data['package_id'],
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

        $price = (int) $package['price'];
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

            $pendingStmt = $this->pdo->prepare(
                'INSERT INTO pending_orders (user_id, package_id, payment_id, amount, payment_method, created_at, status)
                 VALUES (:user_id, :package_id, :payment_id, :amount, :payment_method, :created_at, :status)'
            );
            $pendingStmt->execute([
                'user_id' => $userId,
                'package_id' => $packageId,
                'payment_id' => $paymentId,
                'amount' => $price,
                'payment_method' => 'wallet',
                'created_at' => gmdate('Y-m-d H:i:s'),
                'status' => 'paid_waiting_delivery',
            ]);

            $pendingId = (int) $this->pdo->lastInsertId();
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
            } elseif ($kind === 'purchase') {
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
            'INSERT INTO pending_orders (user_id, package_id, payment_id, amount, payment_method, created_at, status)
             VALUES (:user_id, :package_id, :payment_id, :amount, :payment_method, :created_at, :status)'
        );
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function listPendingDeliveries(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, user_id, package_id, payment_id, amount, created_at
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
                "SELECT id, user_id, package_id, payment_id, amount, payment_method, status
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
            if (($order['status'] ?? '') !== 'paid_waiting_delivery') {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'not_actionable'];
            }

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
            $configStmt->execute(['package_id' => (int) $order['package_id']]);
            $config = $configStmt->fetch();
            if (!is_array($config)) {
                $this->pdo->rollBack();
                return ['ok' => false, 'error' => 'no_stock'];
            }

            $purchaseStmt = $this->pdo->prepare(
                'INSERT INTO purchases (user_id, package_id, config_id, amount, payment_method, created_at, is_test)
                 VALUES (:user_id, :package_id, :config_id, :amount, :payment_method, :created_at, 0)'
            );
            $purchaseStmt->execute([
                'user_id' => (int) $order['user_id'],
                'package_id' => (int) $order['package_id'],
                'config_id' => (int) $config['id'],
                'amount' => (int) $order['amount'],
                'payment_method' => (string) $order['payment_method'],
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);
            $purchaseId = (int) $this->pdo->lastInsertId();

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

            $ordUpdate = $this->pdo->prepare("UPDATE pending_orders SET status = 'delivered' WHERE id = :id");
            $ordUpdate->execute(['id' => $orderId]);

            if (!empty($order['payment_id'])) {
                $payUpdate = $this->pdo->prepare("UPDATE payments SET status = 'completed', approved_at = :approved_at WHERE id = :id");
                $payUpdate->execute([
                    'approved_at' => gmdate('Y-m-d H:i:s'),
                    'id' => (int) $order['payment_id'],
                ]);
            }

            $this->pdo->commit();
            return [
                'ok' => true,
                'user_id' => (int) $order['user_id'],
                'config_text' => (string) $config['config_text'],
                'service_name' => (string) ($config['service_name'] ?? ''),
                'inquiry_link' => (string) ($config['inquiry_link'] ?? ''),
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'error' => 'db_error'];
        }
    }

    public function getPaymentById(int $paymentId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, user_id, package_id, amount, payment_method, gateway_ref, status FROM payments WHERE id = :id LIMIT 1');
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

    public function setPaymentGatewayRef(int $paymentId, string $gatewayRef): void
    {
        $stmt = $this->pdo->prepare('UPDATE payments SET gateway_ref = :gateway_ref WHERE id = :id');
        $stmt->execute(['gateway_ref' => $gatewayRef, 'id' => $paymentId]);
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
}
