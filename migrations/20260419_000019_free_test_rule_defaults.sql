ALTER TABLE free_test_service_rules
    ADD COLUMN IF NOT EXISTS volume_gb DECIMAL(10,2) NULL AFTER max_claims,
    ADD COLUMN IF NOT EXISTS duration_days INT NULL AFTER volume_gb;
