ALTER TABLE user_service_deliveries
    ADD COLUMN IF NOT EXISTS cleanup_due_at DATETIME NULL AFTER last_status_sync_at,
    ADD COLUMN IF NOT EXISTS cleaned_up_at DATETIME NULL AFTER cleanup_due_at,
    ADD COLUMN IF NOT EXISTS cleanup_reason VARCHAR(100) NULL AFTER cleaned_up_at;
