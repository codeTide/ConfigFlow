ALTER TABLE user_service_deliveries
    ADD COLUMN IF NOT EXISTS is_test TINYINT(1) NOT NULL DEFAULT 0 AFTER source_type;

UPDATE user_service_deliveries d
JOIN purchases p ON p.id = d.purchase_id
SET d.is_test = p.is_test
WHERE d.is_test = 0
  AND p.is_test = 1;
