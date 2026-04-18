CREATE TABLE IF NOT EXISTS panel (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    provider VARCHAR(64) NOT NULL DEFAULT 'pasarguard',
    base_url VARCHAR(255) NOT NULL,
    username VARCHAR(191) NOT NULL,
    password TEXT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_panel_active (is_active),
    INDEX idx_panel_provider (provider)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    type_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    mode VARCHAR(32) NOT NULL DEFAULT 'stock',
    panel_id BIGINT NULL,
    panel_ref VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_service_type (type_id),
    INDEX idx_service_mode (mode),
    INDEX idx_service_panel (panel_id),
    INDEX idx_service_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
