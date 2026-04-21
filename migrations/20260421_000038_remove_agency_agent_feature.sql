DROP TABLE IF EXISTS agency_requests;
DROP TABLE IF EXISTS agency_service_prices;
DROP TABLE IF EXISTS agency_price_config;
DROP TABLE IF EXISTS agency_service_discount;

ALTER TABLE users DROP COLUMN IF EXISTS is_agent;

DELETE FROM settings WHERE `key` = 'agency_request_enabled';
