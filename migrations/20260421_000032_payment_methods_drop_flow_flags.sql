ALTER TABLE payment_methods
    DROP INDEX IF EXISTS idx_payment_methods_supports,
    DROP COLUMN IF EXISTS supports_purchase,
    DROP COLUMN IF EXISTS supports_renewal;
