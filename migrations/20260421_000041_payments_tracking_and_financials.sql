ALTER TABLE payments
    ADD COLUMN IF NOT EXISTS tracking_code VARCHAR(32) NULL AFTER id,
    ADD COLUMN IF NOT EXISTS fee_amount INT NOT NULL DEFAULT 0 AFTER amount,
    ADD COLUMN IF NOT EXISTS bonus_amount INT NOT NULL DEFAULT 0 AFTER fee_amount,
    ADD COLUMN IF NOT EXISTS paid_amount INT NULL AFTER bonus_amount,
    ADD COLUMN IF NOT EXISTS status_reason VARCHAR(128) NULL AFTER status,
    ADD COLUMN IF NOT EXISTS bonus_applied_at DATETIME NULL AFTER verified_at;

UPDATE payments
SET paid_amount = amount
WHERE paid_amount IS NULL;

UPDATE payments
SET tracking_code = CONCAT(
    LPAD(FLOOR(100 + (RAND() * 900)), 3, '0'),
    DATE_FORMAT(COALESCE(created_at, UTC_TIMESTAMP()), '%d%m%y'),
    LPAD(FLOOR(100 + (RAND() * 900)), 3, '0')
)
WHERE tracking_code IS NULL OR tracking_code = '';

UPDATE payments p
JOIN (
    SELECT tracking_code, MIN(id) AS keep_id
    FROM payments
    GROUP BY tracking_code
    HAVING COUNT(*) > 1
) d ON d.tracking_code = p.tracking_code AND p.id <> d.keep_id
SET p.tracking_code = CONCAT('8', LPAD(p.id, 11, '0'));

UPDATE payments p
JOIN (
    SELECT id, CONCAT('9', LPAD(id, 11, '0')) AS fallback_tracking_code
    FROM payments
    WHERE tracking_code IS NULL OR tracking_code = ''
) t ON t.id = p.id
SET p.tracking_code = t.fallback_tracking_code;

ALTER TABLE payments
    MODIFY COLUMN tracking_code VARCHAR(32) NOT NULL,
    ADD UNIQUE INDEX uq_payments_tracking_code (tracking_code);
