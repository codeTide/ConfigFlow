DROP TABLE IF EXISTS free_test_service_claims;

ALTER TABLE purchases
    DROP COLUMN IF EXISTS is_test;

ALTER TABLE user_service_deliveries
    MODIFY COLUMN purchase_id BIGINT NULL;

ALTER TABLE service
    ADD COLUMN IF NOT EXISTS sub_link_mode VARCHAR(16) NOT NULL DEFAULT 'proxy' AFTER panel_password,
    ADD COLUMN IF NOT EXISTS sub_link_base_url VARCHAR(255) NULL AFTER sub_link_mode;
