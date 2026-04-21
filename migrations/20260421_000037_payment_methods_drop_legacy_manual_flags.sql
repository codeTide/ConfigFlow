ALTER TABLE payment_methods
    DROP COLUMN IF EXISTS auto_verify,
    DROP COLUMN IF EXISTS requires_receipt;
