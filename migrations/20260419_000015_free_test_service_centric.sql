CREATE TABLE IF NOT EXISTS free_test_service_rules (
  service_id BIGINT PRIMARY KEY,
  is_enabled TINYINT(1) NOT NULL DEFAULT 0,
  claim_mode ENUM('cooldown','once_until_reset') NOT NULL DEFAULT 'once_until_reset',
  cooldown_days INT NULL,
  max_claims INT NOT NULL DEFAULT 1,
  priority INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_free_test_service_enabled (is_enabled),
  INDEX idx_free_test_service_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS free_test_service_claims (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT NOT NULL,
  service_id BIGINT NOT NULL,
  purchase_id BIGINT NOT NULL,
  claimed_at DATETIME NOT NULL,
  INDEX idx_free_test_service_claims_user_service (user_id, service_id),
  INDEX idx_free_test_service_claims_service (service_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS free_test_claims;
DROP TABLE IF EXISTS free_test_package_rules;
