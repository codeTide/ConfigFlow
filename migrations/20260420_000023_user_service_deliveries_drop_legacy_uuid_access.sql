ALTER TABLE user_service_deliveries
    DROP COLUMN IF EXISTS access_url,
    DROP COLUMN IF EXISTS stock_item_uuid,
    DROP COLUMN IF EXISTS config_uuid;
