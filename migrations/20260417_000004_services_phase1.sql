CREATE TABLE IF NOT EXISTS service (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    type_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    mode VARCHAR(32) NOT NULL DEFAULT 'stock',
    panel_provider VARCHAR(64) NULL,
    panel_base_url VARCHAR(255) NULL,
    panel_username VARCHAR(191) NULL,
    panel_password TEXT NULL,
    panel_ref VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_service_type (type_id),
    INDEX idx_service_mode (mode),
    INDEX idx_service_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
