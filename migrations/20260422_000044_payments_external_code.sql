ALTER TABLE payments
    ADD COLUMN IF NOT EXISTS external_code VARCHAR(191) NULL AFTER gateway_ref,
    ADD INDEX idx_payments_external_code (external_code);
