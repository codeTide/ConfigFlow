CREATE TABLE IF NOT EXISTS exchange_rates (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(64) NOT NULL,
    symbol VARCHAR(64) NOT NULL,
    price DECIMAL(20,8) NOT NULL,
    fetched_at DATETIME NOT NULL,
    raw_payload JSON NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uq_exchange_rates_source_symbol (source, symbol),
    INDEX idx_exchange_rates_fetched_at (fetched_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
