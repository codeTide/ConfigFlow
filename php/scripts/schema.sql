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
