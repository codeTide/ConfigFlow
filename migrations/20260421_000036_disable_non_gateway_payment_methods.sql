DELETE FROM payment_methods
WHERE code <> 'tetrapay';

DELETE FROM settings
WHERE `key` = 'gw_crypto_enabled';
