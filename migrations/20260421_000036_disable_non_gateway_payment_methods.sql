DELETE FROM payment_methods
WHERE code IN ('crypto_tron', 'tronpays_rial');

DELETE FROM settings
WHERE `key` IN (
    'gw_crypto_enabled',
    'gw_tronpays_rial_enabled',
    'tronpays_rial_api_key',
    'tronpays_rial_callback_url'
);
