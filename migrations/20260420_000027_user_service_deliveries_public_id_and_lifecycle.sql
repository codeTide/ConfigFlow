ALTER TABLE user_service_deliveries
    ADD COLUMN IF NOT EXISTS service_public_id CHAR(12) NULL AFTER stock_item_id,
    ADD COLUMN IF NOT EXISTS lifecycle_status ENUM('active','expired','depleted','disabled','revoked','deleted') NOT NULL DEFAULT 'active' AFTER service_public_id,
    ADD COLUMN IF NOT EXISTS is_manageable TINYINT(1) NOT NULL DEFAULT 1 AFTER lifecycle_status,
    ADD COLUMN IF NOT EXISTS status_reason VARCHAR(191) NULL AFTER is_manageable,
    ADD COLUMN IF NOT EXISTS last_status_sync_at DATETIME NULL AFTER status_reason;

UPDATE user_service_deliveries
SET lifecycle_status = 'active'
WHERE lifecycle_status IS NULL OR lifecycle_status = '';

UPDATE user_service_deliveries
SET is_manageable = 1
WHERE is_manageable IS NULL;

ALTER TABLE user_service_deliveries
    ADD UNIQUE INDEX IF NOT EXISTS uq_deliveries_service_public_id (service_public_id),
    ADD INDEX IF NOT EXISTS idx_deliveries_manageable (user_id, lifecycle_status, is_manageable);
