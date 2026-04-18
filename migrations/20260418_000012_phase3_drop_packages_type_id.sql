ALTER TABLE packages DROP FOREIGN KEY fk_packages_type;
ALTER TABLE packages DROP INDEX idx_packages_type;
ALTER TABLE packages DROP COLUMN type_id;
