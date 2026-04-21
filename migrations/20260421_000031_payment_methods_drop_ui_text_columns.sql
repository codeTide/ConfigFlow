ALTER TABLE payment_methods
    DROP COLUMN IF EXISTS user_description,
    DROP COLUMN IF EXISTS admin_note;

