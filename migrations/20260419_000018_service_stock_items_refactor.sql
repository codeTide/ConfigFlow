ALTER TABLE service_stock_items
    ADD COLUMN IF NOT EXISTS config_link TEXT NULL AFTER sub_link;

SET @has_access_url := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'service_stock_items'
      AND column_name = 'access_url'
);
SET @copy_sql := IF(
    @has_access_url > 0,
    'UPDATE service_stock_items SET config_link = access_url WHERE config_link IS NULL AND access_url IS NOT NULL',
    'SELECT 1'
);
PREPARE stmt_copy FROM @copy_sql;
EXECUTE stmt_copy;
DEALLOCATE PREPARE stmt_copy;

ALTER TABLE service_stock_items
    DROP COLUMN IF EXISTS access_url,
    DROP COLUMN IF EXISTS config_uuid,
    DROP COLUMN IF EXISTS stock_item_uuid,
    DROP COLUMN IF EXISTS raw_payload;
