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

CREATE TABLE IF NOT EXISTS service (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    service_code VARCHAR(32) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    mode VARCHAR(32) NOT NULL DEFAULT 'stock',
    panel_provider VARCHAR(64) NULL,
    panel_base_url VARCHAR(255) NULL,
    panel_username VARCHAR(191) NULL,
    panel_password TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_service_mode (mode),
    INDEX idx_service_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_tariff (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    service_id BIGINT NOT NULL,
    pricing_mode VARCHAR(32) NOT NULL DEFAULT 'fixed',
    volume_gb DECIMAL(10,2) NULL,
    duration_days INT NULL,
    price INT NULL,
    min_volume_gb DECIMAL(10,2) NULL,
    max_volume_gb DECIMAL(10,2) NULL,
    price_per_gb INT NULL,
    duration_policy VARCHAR(32) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_service_tariff_service (service_id),
    INDEX idx_service_tariff_mode (pricing_mode),
    INDEX idx_service_tariff_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS service_stock_items (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    service_id BIGINT NOT NULL,
    tariff_id BIGINT NULL,
    inventory_bucket VARCHAR(32) NOT NULL DEFAULT 'sale',
    sub_link TEXT NOT NULL,
    config_link TEXT NULL,
    volume_gb DECIMAL(10,2) NULL,
    duration_days INT NULL,
    created_at DATETIME NOT NULL,
    reserved_payment_id BIGINT NULL,
    sold_to BIGINT NULL,
    purchase_id BIGINT NULL,
    sold_at DATETIME NULL,
    is_expired TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_stock_service (service_id),
    INDEX idx_stock_tariff (tariff_id),
    INDEX idx_stock_bucket (inventory_bucket),
    INDEX idx_stock_available (service_id, tariff_id, inventory_bucket, sold_to, reserved_payment_id, is_expired),
    INDEX idx_stock_available_service (service_id, inventory_bucket, sold_to, reserved_payment_id, is_expired)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS purchases (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    service_id BIGINT NULL,
    tariff_id BIGINT NULL,
    amount INT NOT NULL,
    payment_method VARCHAR(64) NOT NULL,
    created_at DATETIME NOT NULL,
    is_test TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_purchases_user (user_id),
    INDEX idx_purchases_service (service_id),
    INDEX idx_purchases_tariff (tariff_id)
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

CREATE TABLE IF NOT EXISTS free_test_service_rules (
    service_id BIGINT PRIMARY KEY,
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    claim_mode ENUM('cooldown','once_until_reset') NOT NULL DEFAULT 'once_until_reset',
    cooldown_days INT NULL,
    max_claims INT NOT NULL DEFAULT 1,
    volume_gb DECIMAL(10,2) NULL,
    duration_days INT NULL,
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

CREATE TABLE IF NOT EXISTS payments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    kind VARCHAR(32) NOT NULL,
    user_id BIGINT NOT NULL,
    service_id BIGINT NULL,
    tariff_id BIGINT NULL,
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
    INDEX idx_payments_status (status),
    INDEX idx_payments_service (service_id),
    INDEX idx_payments_tariff (tariff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pending_orders (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    order_mode VARCHAR(32) NOT NULL DEFAULT 'stock_only',
    service_id BIGINT NULL,
    tariff_id BIGINT NULL,
    selected_volume_gb DECIMAL(10,2) NULL,
    computed_amount INT NULL,
    payment_id BIGINT NULL,
    amount INT NOT NULL,
    payment_method VARCHAR(64) NOT NULL,
    created_at DATETIME NOT NULL,
    status VARCHAR(64) NOT NULL DEFAULT 'waiting',
    INDEX idx_pending_user (user_id),
    INDEX idx_pending_status (status),
    INDEX idx_pending_mode (order_mode),
    INDEX idx_pending_service (service_id),
    INDEX idx_pending_tariff (tariff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_states (
    user_id BIGINT PRIMARY KEY,
    state_name VARCHAR(128) NOT NULL,
    state_payload JSON NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_users (
    user_id BIGINT PRIMARY KEY,
    added_by BIGINT NOT NULL,
    added_at DATETIME NOT NULL,
    permissions TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agency_service_prices (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    service_id BIGINT NOT NULL,
    tariff_id BIGINT NULL,
    price INT NOT NULL,
    UNIQUE KEY uniq_agency_service_price (user_id, service_id, tariff_id),
    INDEX idx_agency_service_price_user (user_id),
    INDEX idx_agency_service_price_service (service_id, tariff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agency_price_config (
    user_id BIGINT PRIMARY KEY,
    price_mode VARCHAR(32) NOT NULL DEFAULT 'service',
    global_type VARCHAR(16) NOT NULL DEFAULT 'pct',
    global_val INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agency_service_discount (
    user_id BIGINT NOT NULL,
    service_id BIGINT NOT NULL,
    discount_type VARCHAR(16) NOT NULL DEFAULT 'pct',
    discount_value INT NOT NULL DEFAULT 0,
    PRIMARY KEY (user_id, service_id),
    INDEX idx_agency_service_discount_service (service_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pinned_messages (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    text TEXT NOT NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pinned_message_sends (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    pin_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    message_id BIGINT NOT NULL,
    INDEX idx_pinned_send_pin (pin_id),
    INDEX idx_pinned_send_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS purchase_rule_acceptances (
    user_id BIGINT PRIMARY KEY,
    accepted_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_service_deliveries (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    purchase_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    service_id BIGINT NOT NULL,
    tariff_id BIGINT NULL,
    source_type ENUM('stock','panel') NOT NULL,
    is_test TINYINT(1) NOT NULL DEFAULT 0,
    stock_item_id BIGINT NULL,
    sub_link TEXT NOT NULL,
    volume_gb DECIMAL(10,2) NULL,
    duration_days INT NULL,
    delivered_at DATETIME NOT NULL,
    meta_json LONGTEXT NULL,
    INDEX idx_deliveries_purchase (purchase_id),
    INDEX idx_deliveries_user (user_id),
    INDEX idx_deliveries_service (service_id, tariff_id),
    INDEX idx_deliveries_source (source_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
