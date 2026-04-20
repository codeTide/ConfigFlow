ALTER TABLE service_stock_items
    ADD INDEX IF NOT EXISTS idx_stock_available_service (service_id, inventory_bucket, sold_to, reserved_payment_id, is_expired);

ALTER TABLE user_service_deliveries
    MODIFY COLUMN tariff_id BIGINT NULL;
