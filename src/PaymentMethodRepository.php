<?php

declare(strict_types=1);

namespace ConfigFlow\Bot;

final class PaymentMethodRepository
{
    public function __construct(private Database $database)
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function getAll(): array
    {
        $stmt = $this->database->pdo()->query(
            'SELECT id, code, category, is_active, sort_order, bonus_enabled, bonus_type, bonus_value, bonus_cap_amount,
                    bonus_min_amount, min_amount, max_amount, fee_enabled, fee_type, fee_value, auto_verify, requires_receipt,
                    supports_purchase, supports_renewal, visible_to_user, admin_note, user_description, config_json
             FROM payment_methods
             ORDER BY sort_order ASC, id ASC'
        );
        return $stmt->fetchAll() ?: [];
    }

    /** @return array<int,array<string,mixed>> */
    public function getActiveForPurchase(): array
    {
        return $this->getActiveByFlow('supports_purchase');
    }

    /** @return array<int,array<string,mixed>> */
    public function getActiveForRenewal(): array
    {
        return $this->getActiveByFlow('supports_renewal');
    }

    /** @return array<string,mixed>|null */
    public function findByCode(string $code): ?array
    {
        $stmt = $this->database->pdo()->prepare(
            'SELECT id, code, category, is_active, sort_order, bonus_enabled, bonus_type, bonus_value, bonus_cap_amount,
                    bonus_min_amount, min_amount, max_amount, fee_enabled, fee_type, fee_value, auto_verify, requires_receipt,
                    supports_purchase, supports_renewal, visible_to_user, admin_note, user_description, config_json
             FROM payment_methods
             WHERE code = :code
             LIMIT 1'
        );
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->database->pdo()->prepare(
            'SELECT id, code, category, is_active, sort_order, bonus_enabled, bonus_type, bonus_value, bonus_cap_amount,
                    bonus_min_amount, min_amount, max_amount, fee_enabled, fee_type, fee_value, auto_verify, requires_receipt,
                    supports_purchase, supports_renewal, visible_to_user, admin_note, user_description, config_json
             FROM payment_methods
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function updateMethodSettings(int $id, array $fields): bool
    {
        if ($fields === []) {
            return false;
        }
        $allowed = [
            'is_active', 'sort_order', 'min_amount', 'max_amount', 'bonus_enabled', 'bonus_type', 'bonus_value',
            'fee_enabled', 'fee_type', 'fee_value', 'supports_purchase', 'supports_renewal', 'visible_to_user',
            'user_description', 'admin_note', 'auto_verify', 'requires_receipt', 'bonus_cap_amount', 'bonus_min_amount',
        ];
        $sets = [];
        $params = ['id' => $id];
        foreach ($fields as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                continue;
            }
            $sets[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }
        if ($sets === []) {
            return false;
        }
        $sets[] = 'updated_at = :updated_at';
        $params['updated_at'] = gmdate('Y-m-d H:i:s');
        $sql = 'UPDATE payment_methods SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->database->pdo()->prepare($sql);
        $stmt->execute($params);
        return true;
    }

    private function getActiveByFlow(string $flowColumn): array
    {
        $stmt = $this->database->pdo()->query(
            "SELECT id, code, category, is_active, sort_order, bonus_enabled, bonus_type, bonus_value, bonus_cap_amount,
                    bonus_min_amount, min_amount, max_amount, fee_enabled, fee_type, fee_value, auto_verify, requires_receipt,
                    supports_purchase, supports_renewal, visible_to_user, admin_note, user_description, config_json
             FROM payment_methods
             WHERE is_active = 1 AND visible_to_user = 1 AND {$flowColumn} = 1
             ORDER BY sort_order ASC, id ASC"
        );
        return $stmt->fetchAll() ?: [];
    }
}
