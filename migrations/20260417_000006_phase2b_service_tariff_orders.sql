ALTER TABLE payments
    ADD COLUMN IF NOT EXISTS service_id BIGINT NULL AFTER package_id,
    ADD COLUMN IF NOT EXISTS tariff_id BIGINT NULL AFTER service_id,
    ADD INDEX idx_payments_service (service_id),
    ADD INDEX idx_payments_tariff (tariff_id);

ALTER TABLE pending_orders
    ADD COLUMN IF NOT EXISTS tariff_id BIGINT NULL AFTER service_id,
    ADD INDEX idx_pending_tariff (tariff_id);

ALTER TABLE purchases
    ADD COLUMN IF NOT EXISTS service_id BIGINT NULL AFTER package_id,
    ADD COLUMN IF NOT EXISTS tariff_id BIGINT NULL AFTER service_id,
    ADD INDEX idx_purchases_service (service_id),
    ADD INDEX idx_purchases_tariff (tariff_id);
