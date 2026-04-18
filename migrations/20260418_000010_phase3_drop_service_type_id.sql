-- Phase 3 (hard): remove legacy type linkage from service table
ALTER TABLE service DROP INDEX IF EXISTS idx_service_type;
ALTER TABLE service DROP COLUMN IF EXISTS type_id;
