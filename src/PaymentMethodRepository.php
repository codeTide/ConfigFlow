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
                    visible_to_user, allow_wallet_topup, wallet_amount_input_mode, config_json
             FROM payment_methods
             ORDER BY sort_order ASC, id ASC'
        );
        return $stmt->fetchAll() ?: [];
    }

    /** @return array<int,array<string,mixed>> */
    public function getActiveVisibleMethods(): array
    {
        $stmt = $this->database->pdo()->query(
            'SELECT id, code, category, is_active, sort_order, bonus_enabled, bonus_type, bonus_value, bonus_cap_amount,
                    bonus_min_amount, min_amount, max_amount, fee_enabled, fee_type, fee_value, auto_verify, requires_receipt,
                    visible_to_user, allow_wallet_topup, wallet_amount_input_mode, config_json
             FROM payment_methods
             WHERE is_active = 1 AND visible_to_user = 1
             ORDER BY sort_order ASC, id ASC'
        );
        return $stmt->fetchAll() ?: [];
    }

    /** @return array<string,mixed>|null */
    public function findByCode(string $code): ?array
    {
        $stmt = $this->database->pdo()->prepare(
            'SELECT id, code, category, is_active, sort_order, bonus_enabled, bonus_type, bonus_value, bonus_cap_amount,
                    bonus_min_amount, min_amount, max_amount, fee_enabled, fee_type, fee_value, auto_verify, requires_receipt,
                    visible_to_user, allow_wallet_topup, wallet_amount_input_mode, config_json
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
                    visible_to_user, allow_wallet_topup, wallet_amount_input_mode, config_json
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
            'fee_enabled', 'fee_type', 'fee_value', 'visible_to_user', 'auto_verify', 'requires_receipt',
            'bonus_cap_amount', 'bonus_min_amount', 'allow_wallet_topup', 'wallet_amount_input_mode',
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

    public function updateSortOrderWithRebalance(int $id, int $newSortOrder): bool
    {
        $method = $this->findById($id);
        if ($method === null) {
            return false;
        }
        $currentSortOrder = (int) ($method['sort_order'] ?? 0);
        if ($newSortOrder < 0) {
            $newSortOrder = 0;
        }
        if ($newSortOrder === $currentSortOrder) {
            return true;
        }

        $pdo = $this->database->pdo();
        $pdo->beginTransaction();
        try {
            if ($newSortOrder < $currentSortOrder) {
                $shift = $pdo->prepare(
                    'UPDATE payment_methods
                     SET sort_order = sort_order + 1, updated_at = :updated_at
                     WHERE id <> :id AND sort_order >= :new_sort_order AND sort_order < :current_sort_order'
                );
                $shift->execute([
                    'updated_at' => gmdate('Y-m-d H:i:s'),
                    'id' => $id,
                    'new_sort_order' => $newSortOrder,
                    'current_sort_order' => $currentSortOrder,
                ]);
            } else {
                $shift = $pdo->prepare(
                    'UPDATE payment_methods
                     SET sort_order = sort_order - 1, updated_at = :updated_at
                     WHERE id <> :id AND sort_order <= :new_sort_order AND sort_order > :current_sort_order'
                );
                $shift->execute([
                    'updated_at' => gmdate('Y-m-d H:i:s'),
                    'id' => $id,
                    'new_sort_order' => $newSortOrder,
                    'current_sort_order' => $currentSortOrder,
                ]);
            }

            $update = $pdo->prepare('UPDATE payment_methods SET sort_order = :sort_order, updated_at = :updated_at WHERE id = :id');
            $update->execute([
                'sort_order' => $newSortOrder,
                'updated_at' => gmdate('Y-m-d H:i:s'),
                'id' => $id,
            ]);
            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @return array<string,mixed> */
    public function getMethodConfig(string $code): array
    {
        $method = $this->findByCode($code);
        if ($method === null) {
            return [];
        }
        $raw = $method['config_json'] ?? null;
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /** @return array<int,array<string,mixed>> */
    public function getActiveVisibleWalletTopupMethods(): array
    {
        $stmt = $this->database->pdo()->query(
            "SELECT id, code, category, is_active, sort_order, bonus_enabled, bonus_type, bonus_value, bonus_cap_amount,
                    bonus_min_amount, min_amount, max_amount, fee_enabled, fee_type, fee_value, auto_verify, requires_receipt,
                    visible_to_user, allow_wallet_topup, wallet_amount_input_mode, config_json
             FROM payment_methods
             WHERE is_active = 1 AND visible_to_user = 1 AND allow_wallet_topup = 1
             ORDER BY sort_order ASC, id ASC"
        );
        return $stmt->fetchAll() ?: [];
    }

    public function setMethodConfigValue(string $code, string $key, mixed $value): bool
    {
        $method = $this->findByCode($code);
        if ($method === null) {
            return false;
        }
        $config = $this->getMethodConfig($code);
        $config[$key] = $value;
        return $this->replaceMethodConfig($code, $config);
    }

    /** @param array<string,mixed> $config */
    public function replaceMethodConfig(string $code, array $config): bool
    {
        $method = $this->findByCode($code);
        if ($method === null) {
            return false;
        }
        $validated = $this->validateMethodConfig($code, $config);
        $stmt = $this->database->pdo()->prepare(
            'UPDATE payment_methods SET config_json = :config_json, updated_at = :updated_at WHERE code = :code'
        );
        $stmt->execute([
            'config_json' => json_encode($validated, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => gmdate('Y-m-d H:i:s'),
            'code' => $code,
        ]);
        return true;
    }

    /** @param array<string,mixed> $config
     *  @return array<string,mixed>
     */
    public function validateMethodConfig(string $code, array $config): array
    {
        $schema = $this->configSchema()[$code] ?? [];
        if ($schema === []) {
            return [];
        }

        $clean = [];
        foreach ($schema as $key => $rules) {
            if (!array_key_exists($key, $config)) {
                continue;
            }
            $value = $config[$key];
            $type = (string) ($rules['type'] ?? 'string');
            if ($type === 'int') {
                $clean[$key] = (int) $value;
                continue;
            }
            if ($type === 'decimal') {
                $clean[$key] = (float) $value;
                continue;
            }
            if ($type === 'enum') {
                $allowed = is_array($rules['values'] ?? null) ? $rules['values'] : [];
                $stringValue = (string) $value;
                if (in_array($stringValue, $allowed, true)) {
                    $clean[$key] = $stringValue;
                }
                continue;
            }
            $clean[$key] = trim((string) $value);
        }

        return $clean;
    }

    /** @return array<string,array<string,array<string,mixed>>> */
    public function configSchema(): array
    {
        return [
            'tetrapay' => [
                'api_key' => ['type' => 'string'],
                'callback_url' => ['type' => 'string'],
                'default_description' => ['type' => 'string'],
                'email_fallback' => ['type' => 'string'],
                'mobile_fallback' => ['type' => 'string'],
            ],
            'crypto_tron' => [
                'network' => ['type' => 'string'],
                'coin' => ['type' => 'string'],
                'wallet_address' => ['type' => 'string'],
                'confirm_blocks' => ['type' => 'int'],
                'tolerance_percent' => ['type' => 'decimal'],
                'pricing_mode' => ['type' => 'enum', 'values' => ['manual', 'market']],
                'timeout_seconds' => ['type' => 'int'],
            ],
            'swapwallet_crypto' => [
                'base_url' => ['type' => 'string'],
                'merchant_key' => ['type' => 'string'],
                'username' => ['type' => 'string'],
                'network' => ['type' => 'string'],
                'asset' => ['type' => 'string'],
                'callback_url' => ['type' => 'string'],
                'pricing_mode' => ['type' => 'enum', 'values' => ['manual', 'market']],
                'timeout_seconds' => ['type' => 'int'],
            ],
            'tronpays_rial' => [
                'base_url' => ['type' => 'string'],
                'merchant_key' => ['type' => 'string'],
                'callback_url' => ['type' => 'string'],
                'mode' => ['type' => 'enum', 'values' => ['sandbox', 'live']],
                'verify_mode' => ['type' => 'enum', 'values' => ['manual', 'auto']],
                'timeout_seconds' => ['type' => 'int'],
            ],
        ];
    }
}
