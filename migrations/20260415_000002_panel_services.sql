CREATE TABLE IF NOT EXISTS provisioning_services (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    min_gb DECIMAL(10,2) NOT NULL,
    max_gb DECIMAL(10,2) NOT NULL,
    step_gb DECIMAL(10,2) NOT NULL DEFAULT 1,
    price_per_gb INT NOT NULL,
    duration_policy VARCHAR(32) NOT NULL DEFAULT 'fixed_days',
    duration_days INT NULL,
    provider VARCHAR(64) NOT NULL DEFAULT 'pasarguard',
    provider_group_ids TEXT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_provisioning_services_active (is_active),
    INDEX idx_provisioning_services_provider (provider)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE pending_orders
    MODIFY package_id BIGINT NULL,
    ADD COLUMN IF NOT EXISTS order_mode VARCHAR(32) NOT NULL DEFAULT 'stock_only' AFTER package_id,
    ADD COLUMN IF NOT EXISTS service_id BIGINT NULL AFTER order_mode,
    ADD COLUMN IF NOT EXISTS selected_volume_gb DECIMAL(10,2) NULL AFTER service_id,
    ADD COLUMN IF NOT EXISTS computed_amount INT NULL AFTER selected_volume_gb,
    ADD INDEX IF NOT EXISTS idx_pending_mode (order_mode),
    ADD INDEX IF NOT EXISTS idx_pending_service (service_id);
