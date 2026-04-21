ALTER TABLE payments
    DROP COLUMN IF EXISTS tx_hash,
    DROP COLUMN IF EXISTS crypto_amount_claimed,
    DROP COLUMN IF EXISTS receipt_file_id,
    DROP COLUMN IF EXISTS receipt_text;
