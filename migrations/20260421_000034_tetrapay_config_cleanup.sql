UPDATE payment_methods
SET config_json = JSON_REMOVE(
        COALESCE(config_json, JSON_OBJECT()),
        '$.default_description',
        '$.email_fallback',
        '$.mobile_fallback'
    ),
    updated_at = UTC_TIMESTAMP()
WHERE code = 'tetrapay'
  AND config_json IS NOT NULL;
