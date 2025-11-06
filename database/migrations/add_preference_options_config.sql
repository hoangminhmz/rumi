-- Migration: Add options configuration to preferences_list
-- Purpose: Enable dynamic preference options management (JSON Config approach)
-- Date: 2025-01-06

-- Step 1: Add field_type column to define what type of input this preference needs
ALTER TABLE preferences_list
ADD COLUMN field_type ENUM('enum', 'scale', 'boolean', 'range') NOT NULL DEFAULT 'enum'
AFTER category;

-- Step 2: Add options_config column to store JSON configuration for options
ALTER TABLE preferences_list
ADD COLUMN options_config JSON NULL
AFTER field_type;

-- Step 3: Add description for admin UI help text
ALTER TABLE preferences_list
ADD COLUMN description_vi TEXT NULL AFTER options_config,
ADD COLUMN description_en TEXT NULL AFTER description_vi;

-- Verify structure
DESCRIBE preferences_list;
