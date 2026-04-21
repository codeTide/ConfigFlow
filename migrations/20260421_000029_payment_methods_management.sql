CREATE TABLE IF NOT EXISTS payment_methods (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(64) NOT NULL,
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
    visible_to_user TINYINT(1) NOT NULL DEFAULT 1,
    config_json JSON NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_payment_methods_code (code),
    KEY idx_payment_methods_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO payment_methods
    (code, category, is_active, sort_order, min_amount, max_amount, visible_to_user, created_at, updated_at)
VALUES
    ('tetrapay', 'gateway', 1, 30, 10000, 0, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP())
ON DUPLICATE KEY UPDATE
    category = VALUES(category),
    sort_order = VALUES(sort_order),
    updated_at = VALUES(updated_at);


UPDATE payment_methods pm
JOIN settings s ON s.`key` = 'gw_tetrapay_enabled'
SET pm.is_active = CASE WHEN s.`value` = '1' THEN 1 ELSE 0 END,
    pm.updated_at = UTC_TIMESTAMP()
WHERE pm.code = 'tetrapay';


DELETE FROM settings WHERE `key` = 'gw_card_enabled';
DELETE FROM settings WHERE `key` = 'payment_card';
DELETE FROM settings WHERE `key` = 'payment_bank';
DELETE FROM settings WHERE `key` = 'payment_owner';
