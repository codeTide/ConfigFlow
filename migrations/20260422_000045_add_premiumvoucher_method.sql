INSERT INTO payment_methods
    (code, category, is_active, sort_order, min_amount, max_amount, visible_to_user, allow_wallet_topup, wallet_amount_input_mode, config_json, created_at, updated_at)
SELECT
    'premiumvoucher',
    'gateway',
    0,
    COALESCE(MAX(sort_order), 0) + 10,
    0,
    0,
    1,
    1,
    'none',
    JSON_OBJECT(
        'api_key', '',
        'api_base_url', 'https://api.premiummoney.com',
        'api_version', 'v2',
        'redeem_endpoint', 'RedeemCode',
        'voucher_regex', '^PSVouchers-\\d+(?:_\\d+)?-(?:USD|PSV|PM)-\\d+-[A-Za-z0-9]{20,}$',
        'rate_source', 'wallex',
        'rate_symbol', 'USDTTMN',
        'min_usd_amount', 0,
        'max_usd_amount', 0,
        'allowed_code_prefix', 'PSVouchers-',
        'is_enabled_precheck_regex', 1
    ),
    UTC_TIMESTAMP(),
    UTC_TIMESTAMP()
FROM payment_methods
WHERE NOT EXISTS (SELECT 1 FROM payment_methods WHERE code = 'premiumvoucher');
