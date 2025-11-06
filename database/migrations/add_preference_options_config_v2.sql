-- Migration: Add options configuration to preferences_list (SAFE VERSION)
-- Purpose: Enable dynamic preference options management (JSON Config approach)
-- Date: 2025-01-06
-- Note: Each ALTER TABLE is separate for better error handling

-- Step 1: Add field_type column
ALTER TABLE preferences_list
ADD COLUMN field_type VARCHAR(20) NULL DEFAULT NULL
AFTER category;

-- Step 2: Add options_config column
ALTER TABLE preferences_list
ADD COLUMN options_config TEXT NULL
AFTER field_type;

-- Step 3: Add description columns (split into separate files for safer execution)
ALTER TABLE preferences_list
ADD COLUMN description_vi TEXT NULL
AFTER options_config;

-- Step 4: Add description_en column (runs after description_vi exists)
ALTER TABLE preferences_list
ADD COLUMN description_en TEXT NULL
AFTER description_vi;

-- Step 4: Set default values for existing rows
UPDATE preferences_list SET field_type = 'enum' WHERE field_type IS NULL;

-- Verify structure
DESCRIBE preferences_list;
