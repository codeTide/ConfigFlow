ALTER TABLE purchases
    MODIFY package_id BIGINT NULL;

DROP TABLE IF EXISTS xui_jobs;
DROP TABLE IF EXISTS panel_packages;
DROP TABLE IF EXISTS panels;
