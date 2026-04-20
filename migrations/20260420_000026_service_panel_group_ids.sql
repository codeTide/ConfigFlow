ALTER TABLE service
    ADD COLUMN IF NOT EXISTS panel_group_ids JSON NULL AFTER sub_link_base_url;
