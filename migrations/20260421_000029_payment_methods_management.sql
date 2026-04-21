CREATE TABLE IF NOT EXISTS payment_methods (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(64) NOT NULL,
    title VARCHAR(191) NOT NULL,
    category VARCHAR(32) NOT NULL DEFAULT 'gateway',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 100,
    bonus_enabled TINYINT(1) NOT NULL DEFAULT 0,
    bonus_type ENUM('percent','fixed') NULL,
    bonus_value DECIMAL(18,4) NULL,
    bonus_cap_amount INT NULL,
    bonus_min_amount INT NULL,
    min_amount INT NOT NULL DEFAULT 0,
    max_amount INT NOT NULL DEFAULT 0,
    fee_enabled TINYINT(1) NOT NULL DEFAULT 0,
    fee_type ENUM('none','percent','fixed') NULL,
    fee_value DECIMAL(18,4) NULL,
    auto_verify TINYINT(1) NOT NULL DEFAULT 0,
    requires_receipt TINYINT(1) NOT NULL DEFAULT 0,
    supports_purchase TINYINT(1) NOT NULL DEFAULT 1,
    supports_renewal TINYINT(1) NOT NULL DEFAULT 1,
    visible_to_user TINYINT(1) NOT NULL DEFAULT 1,
    admin_note TEXT NULL,
    user_description TEXT NULL,
    config_json JSON NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_payment_methods_code (code),
    KEY idx_payment_methods_active (is_active, sort_order),
    KEY idx_payment_methods_supports (supports_purchase, supports_renewal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO payment_methods
    (code, title, category, is_active, sort_order, min_amount, max_amount, auto_verify, requires_receipt, supports_purchase, supports_renewal, visible_to_user, created_at, updated_at)
VALUES
    ('crypto_tron', 'پرداخت کریپتو', 'crypto', 1, 20, 10000, 0, 0, 1, 1, 1, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
    ('tetrapay', 'پرداخت تتراپی', 'gateway', 1, 30, 10000, 0, 1, 0, 1, 1, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
    ('swapwallet_crypto', 'پرداخت سواپ‌ولت', 'crypto', 0, 40, 10000, 0, 1, 0, 1, 1, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP()),
    ('tronpays_rial', 'پرداخت ترون‌پیز', 'rial', 0, 50, 10000, 0, 1, 0, 1, 1, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    category = VALUES(category),
    sort_order = VALUES(sort_order),
    updated_at = VALUES(updated_at);

UPDATE payment_methods pm
JOIN settings s ON s.`key` = 'gw_crypto_enabled'
SET pm.is_active = CASE WHEN s.`value` = '1' THEN 1 ELSE 0 END,
    pm.updated_at = UTC_TIMESTAMP()
WHERE pm.code = 'crypto_tron';

UPDATE payment_methods pm
JOIN settings s ON s.`key` = 'gw_tetrapay_enabled'
SET pm.is_active = CASE WHEN s.`value` = '1' THEN 1 ELSE 0 END,
    pm.updated_at = UTC_TIMESTAMP()
WHERE pm.code = 'tetrapay';

UPDATE payment_methods pm
JOIN settings s ON s.`key` = 'gw_swapwallet_crypto_enabled'
SET pm.is_active = CASE WHEN s.`value` = '1' THEN 1 ELSE 0 END,
    pm.updated_at = UTC_TIMESTAMP()
WHERE pm.code = 'swapwallet_crypto';

UPDATE payment_methods pm
JOIN settings s ON s.`key` = 'gw_tronpays_rial_enabled'
SET pm.is_active = CASE WHEN s.`value` = '1' THEN 1 ELSE 0 END,
    pm.updated_at = UTC_TIMESTAMP()
WHERE pm.code = 'tronpays_rial';

DELETE FROM settings WHERE `key` = 'gw_card_enabled';
DELETE FROM settings WHERE `key` = 'payment_card';
DELETE FROM settings WHERE `key` = 'payment_bank';
DELETE FROM settings WHERE `key` = 'payment_owner';

