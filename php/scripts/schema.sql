CREATE TABLE IF NOT EXISTS users (
    user_id BIGINT PRIMARY KEY,
    full_name VARCHAR(255) NULL,
    username VARCHAR(255) NULL,
    balance INT NOT NULL DEFAULT 0,
    joined_at DATETIME NOT NULL,
    last_seen_at DATETIME NOT NULL,
    first_start_notified TINYINT(1) NOT NULL DEFAULT 0,
    status VARCHAR(32) NOT NULL DEFAULT 'unsafe',
    is_agent TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(191) PRIMARY KEY,
    `value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS referrals (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    referrer_id BIGINT NOT NULL,
    referee_id BIGINT NOT NULL UNIQUE,
    created_at DATETIME NOT NULL,
    start_reward_given TINYINT(1) NOT NULL DEFAULT 0,
    purchase_reward_given TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_referrer_id (referrer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS config_types (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS packages (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    type_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    volume_gb DECIMAL(10,2) NOT NULL,
    duration_days INT NOT NULL,
    price INT NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_packages_type FOREIGN KEY (type_id) REFERENCES config_types(id) ON DELETE CASCADE,
    INDEX idx_packages_type (type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    type_id BIGINT NOT NULL,
    package_id BIGINT NOT NULL,
    service_name VARCHAR(255) NOT NULL,
    config_text TEXT NOT NULL,
    inquiry_link TEXT NULL,
    created_at DATETIME NOT NULL,
    reserved_payment_id BIGINT NULL,
    sold_to BIGINT NULL,
    purchase_id BIGINT NULL,
    sold_at DATETIME NULL,
    is_expired TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_configs_package (package_id),
    INDEX idx_configs_sold (sold_to),
    INDEX idx_configs_available (package_id, sold_to, reserved_payment_id, is_expired)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS purchases (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    package_id BIGINT NOT NULL,
    config_id BIGINT NOT NULL,
    amount INT NOT NULL,
    payment_method VARCHAR(64) NOT NULL,
    created_at DATETIME NOT NULL,
    is_test TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_purchases_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS free_test_requests (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    note TEXT NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    admin_note TEXT NULL,
    created_at DATETIME NOT NULL,
    reviewed_at DATETIME NULL,
    INDEX idx_free_test_user (user_id),
    INDEX idx_free_test_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agency_requests (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    note TEXT NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    admin_note TEXT NULL,
    created_at DATETIME NOT NULL,
    reviewed_at DATETIME NULL,
    INDEX idx_agency_user (user_id),
    INDEX idx_agency_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    kind VARCHAR(32) NOT NULL,
    user_id BIGINT NOT NULL,
    package_id BIGINT NULL,
    amount INT NOT NULL,
    payment_method VARCHAR(64) NOT NULL,
    gateway_ref VARCHAR(191) NULL,
    tx_hash VARCHAR(255) NULL,
    crypto_amount_claimed DECIMAL(24,8) NULL,
    provider_payload TEXT NULL,
    status VARCHAR(64) NOT NULL,
    receipt_file_id VARCHAR(255) NULL,
    receipt_text TEXT NULL,
    admin_note TEXT NULL,
    created_at DATETIME NOT NULL,
    approved_at DATETIME NULL,
    verified_at DATETIME NULL,
    verify_attempts INT NOT NULL DEFAULT 0,
    last_verify_at DATETIME NULL,
    INDEX idx_payments_user (user_id),
    INDEX idx_payments_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pending_orders (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    package_id BIGINT NOT NULL,
    payment_id BIGINT NULL,
    amount INT NOT NULL,
    payment_method VARCHAR(64) NOT NULL,
    created_at DATETIME NOT NULL,
    status VARCHAR(64) NOT NULL DEFAULT 'waiting',
    INDEX idx_pending_user (user_id),
    INDEX idx_pending_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_states (
    user_id BIGINT PRIMARY KEY,
    state_name VARCHAR(128) NOT NULL,
    state_payload JSON NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
