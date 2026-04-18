-- Phase 3 (hard): remove legacy type linkage from configs table
ALTER TABLE configs DROP COLUMN IF EXISTS type_id;
