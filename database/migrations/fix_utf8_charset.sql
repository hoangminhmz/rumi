-- Fix UTF-8 Charset for RUMI Database
-- This script converts all tables to utf8mb4 with utf8mb4_unicode_ci collation

-- Convert preferences_list table
ALTER TABLE preferences_list
CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Convert amenities_list table
ALTER TABLE amenities_list
CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Convert users table
ALTER TABLE users
CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Convert rooms table
ALTER TABLE rooms
CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Convert districts table
ALTER TABLE districts
CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Convert cities table
ALTER TABLE cities
CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Convert swipes table
ALTER TABLE swipes
CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Convert matches table
ALTER TABLE matches
CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Convert payments table
ALTER TABLE payments
CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Set database default charset
ALTER DATABASE rumi_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
