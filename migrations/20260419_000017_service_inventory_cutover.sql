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
    INDEX idx_stock_available (service_id, tariff_id, inventory_bucket, sold_to, reserved_payment_id, is_expired)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_service_deliveries (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    purchase_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    service_id BIGINT NOT NULL,
    tariff_id BIGINT NOT NULL,
    source_type ENUM('stock','panel') NOT NULL,
    stock_item_id BIGINT NULL,
    sub_link TEXT NOT NULL,
    access_url TEXT NULL,
    stock_item_uuid VARCHAR(191) NULL,
    volume_gb DECIMAL(10,2) NULL,
    duration_days INT NULL,
    delivered_at DATETIME NOT NULL,
    meta_json LONGTEXT NULL,
    INDEX idx_deliveries_purchase (purchase_id),
    INDEX idx_deliveries_user (user_id),
    INDEX idx_deliveries_service (service_id, tariff_id),
    INDEX idx_deliveries_source (source_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO service_stock_items (
    service_id, tariff_id, inventory_bucket, sub_link, config_link,
    volume_gb, duration_days, created_at, reserved_payment_id, sold_to, purchase_id, sold_at, is_expired
)
SELECT
    c.service_id,
    c.tariff_id,
    COALESCE(NULLIF(TRIM(c.inventory_bucket), ''), 'sale') AS inventory_bucket,
    COALESCE(NULLIF(c.inquiry_link, ''), c.config_text) AS sub_link,
    NULL,
    NULL,
    NULL,
    c.created_at,
    c.reserved_payment_id,
    c.sold_to,
    c.purchase_id,
    c.sold_at,
    c.is_expired
FROM configs c
LEFT JOIN service_stock_items s
    ON s.service_id <=> c.service_id
   AND s.tariff_id <=> c.tariff_id
   AND s.created_at = c.created_at
   AND s.sub_link = COALESCE(NULLIF(c.inquiry_link, ''), c.config_text)
WHERE c.service_id IS NOT NULL
  AND c.service_id > 0
  AND s.id IS NULL;

INSERT INTO user_service_deliveries (
    purchase_id, user_id, service_id, tariff_id, source_type, stock_item_id,
    sub_link, access_url, stock_item_uuid, volume_gb, duration_days, delivered_at, meta_json
)
SELECT
    p.id,
    p.user_id,
    p.service_id,
    p.tariff_id,
    CASE WHEN s.id IS NULL THEN 'panel' ELSE 'stock' END,
    s.id,
    COALESCE(NULLIF(s.sub_link, ''), ''),
    s.config_link,
    NULL,
    s.volume_gb,
    s.duration_days,
    COALESCE(s.sold_at, p.created_at),
    NULL
FROM purchases p
LEFT JOIN service_stock_items s ON s.purchase_id = p.id
LEFT JOIN user_service_deliveries d ON d.purchase_id = p.id
WHERE p.service_id IS NOT NULL
  AND p.service_id > 0
  AND p.tariff_id IS NOT NULL
  AND p.tariff_id > 0
  AND d.id IS NULL;

ALTER TABLE purchases DROP COLUMN IF EXISTS package_id;
ALTER TABLE purchases DROP COLUMN IF EXISTS config_id;
ALTER TABLE payments DROP COLUMN IF EXISTS package_id;
ALTER TABLE pending_orders DROP COLUMN IF EXISTS package_id;

DROP TABLE IF EXISTS provisioning_services;
DROP TABLE IF EXISTS configs;
DROP TABLE IF EXISTS packages;
