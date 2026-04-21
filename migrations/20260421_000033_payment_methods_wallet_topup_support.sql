ALTER TABLE payment_methods
    ADD COLUMN IF NOT EXISTS allow_wallet_topup TINYINT(1) NOT NULL DEFAULT 0 AFTER visible_to_user,
    ADD COLUMN IF NOT EXISTS wallet_amount_input_mode ENUM('none','user_input') NOT NULL DEFAULT 'user_input' AFTER allow_wallet_topup;

UPDATE payment_methods
SET allow_wallet_topup = CASE WHEN code = 'tetrapay' THEN 1 ELSE 0 END,
    wallet_amount_input_mode = CASE WHEN code = 'tetrapay' THEN 'user_input' ELSE 'none' END,
    updated_at = UTC_TIMESTAMP()
WHERE code IN ('tetrapay');
