-- Migration V3: Add options configuration to preferences_list (SAFEST VERSION)
-- Purpose: Enable dynamic preference options management (JSON Config approach)
-- Date: 2025-01-06
-- Note: No AFTER clause - just add columns at end of table

-- Add field_type column
ALTER TABLE preferences_list ADD COLUMN field_type VARCHAR(20) NULL;

-- Add options_config column
ALTER TABLE preferences_list ADD COLUMN options_config TEXT NULL;

-- Add description columns
ALTER TABLE preferences_list ADD COLUMN description_vi TEXT NULL;

ALTER TABLE preferences_list ADD COLUMN description_en TEXT NULL;

-- Set default values for existing rows
UPDATE preferences_list SET field_type = 'enum' WHERE field_type IS NULL;
