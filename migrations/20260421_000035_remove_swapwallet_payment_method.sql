DELETE FROM payment_methods WHERE code = 'swapwallet_crypto';

DELETE FROM settings WHERE `key` IN (
    'gw_swapwallet_crypto_enabled',
    'swapwallet_crypto_api_key',
    'swapwallet_crypto_username'
);
