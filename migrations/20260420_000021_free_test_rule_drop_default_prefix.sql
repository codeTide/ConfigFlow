ALTER TABLE free_test_service_rules
    ADD COLUMN IF NOT EXISTS volume_gb DECIMAL(10,2) NULL AFTER max_claims,
    ADD COLUMN IF NOT EXISTS duration_days INT NULL AFTER volume_gb;

UPDATE free_test_service_rules
SET volume_gb = default_volume_gb
WHERE volume_gb IS NULL
  AND default_volume_gb IS NOT NULL;

UPDATE free_test_service_rules
SET duration_days = default_duration_days
WHERE duration_days IS NULL
  AND default_duration_days IS NOT NULL;

ALTER TABLE free_test_service_rules
    DROP COLUMN IF EXISTS default_volume_gb,
    DROP COLUMN IF EXISTS default_duration_days;
