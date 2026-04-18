DROP TABLE IF EXISTS agency_type_discount;

CREATE TABLE IF NOT EXISTS agency_service_discount (
    user_id BIGINT NOT NULL,
    service_id BIGINT NOT NULL,
    discount_type VARCHAR(16) NOT NULL DEFAULT 'pct',
    discount_value INT NOT NULL DEFAULT 0,
    PRIMARY KEY (user_id, service_id),
    INDEX idx_agency_service_discount_service (service_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
