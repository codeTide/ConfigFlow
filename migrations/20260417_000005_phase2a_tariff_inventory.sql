CREATE TABLE IF NOT EXISTS service_tariff (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE configs
    MODIFY package_id BIGINT NULL,
    ADD COLUMN IF NOT EXISTS service_id BIGINT NULL AFTER package_id,
    ADD COLUMN IF NOT EXISTS tariff_id BIGINT NULL AFTER service_id,
    ADD INDEX idx_configs_service (service_id),
    ADD INDEX idx_configs_tariff (tariff_id);
