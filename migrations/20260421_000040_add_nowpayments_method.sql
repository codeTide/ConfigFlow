INSERT INTO payment_methods
    (code, category, is_active, sort_order, min_amount, max_amount, visible_to_user, allow_wallet_topup, wallet_amount_input_mode, created_at, updated_at)
SELECT
    'nowpayments',
    'gateway',
    0,
    COALESCE(MAX(sort_order), 0) + 10,
    10000,
    0,
    1,
    1,
    'user_input',
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
FROM payment_methods
WHERE NOT EXISTS (SELECT 1 FROM payment_methods WHERE code = 'nowpayments');
