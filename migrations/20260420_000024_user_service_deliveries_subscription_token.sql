ALTER TABLE user_service_deliveries
    ADD COLUMN IF NOT EXISTS subscription_token VARCHAR(64) NULL AFTER stock_item_id;

UPDATE user_service_deliveries
SET subscription_token = REPLACE(LOWER(UUID()), '-', '')
WHERE subscription_token IS NULL
   OR subscription_token = '';

ALTER TABLE user_service_deliveries
    MODIFY COLUMN subscription_token VARCHAR(64) NOT NULL;

ALTER TABLE user_service_deliveries
    ADD UNIQUE INDEX IF NOT EXISTS uq_deliveries_subscription_token (subscription_token);
