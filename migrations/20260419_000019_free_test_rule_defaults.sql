ALTER TABLE free_test_service_rules
    ADD COLUMN IF NOT EXISTS default_volume_gb DECIMAL(10,2) NULL AFTER max_claims,
    ADD COLUMN IF NOT EXISTS default_duration_days INT NULL AFTER default_volume_gb;
