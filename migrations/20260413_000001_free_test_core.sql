CREATE TABLE IF NOT EXISTS free_test_package_rules (
    package_id BIGINT PRIMARY KEY,
    max_claims INT NOT NULL DEFAULT 1,
    cooldown_days INT NOT NULL DEFAULT 0,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_free_test_enabled (is_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS free_test_claims (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    package_id BIGINT NOT NULL,
    purchase_id BIGINT NOT NULL,
    claimed_at DATETIME NOT NULL,
    INDEX idx_free_test_claims_user_pkg (user_id, package_id),
    INDEX idx_free_test_claims_pkg (package_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
