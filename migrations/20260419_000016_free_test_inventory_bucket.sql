ALTER TABLE configs ADD COLUMN IF NOT EXISTS inventory_bucket VARCHAR(32) NOT NULL DEFAULT 'sale' AFTER tariff_id;
ALTER TABLE configs ADD INDEX IF NOT EXISTS idx_configs_inventory_bucket (inventory_bucket);
UPDATE configs SET inventory_bucket = 'sale' WHERE inventory_bucket IS NULL OR TRIM(inventory_bucket) = '';

DROP TABLE IF EXISTS free_test_requests;
DROP TABLE IF EXISTS free_test_package_rules;
DROP TABLE IF EXISTS free_test_claims;
