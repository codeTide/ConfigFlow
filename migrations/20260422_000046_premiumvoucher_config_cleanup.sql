UPDATE payment_methods
SET
    min_amount = 0,
    max_amount = 0,
    fee_enabled = 0,
    fee_type = 'none',
    fee_value = 0,
    config_json = JSON_OBJECT('api_key', COALESCE(JSON_UNQUOTE(JSON_EXTRACT(config_json, '$.api_key')), '')),
    updated_at = UTC_TIMESTAMP()
WHERE code = 'premiumvoucher';
