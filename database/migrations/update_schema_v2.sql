-- ============================================
-- RUMI Database Schema V2 Migration
-- Enhanced matching with lifestyle preferences
-- ============================================

-- Set charset
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ============================================
-- UPDATE: users table
-- Add lifestyle preferences and verification
-- ============================================

-- Check and add columns if they don't exist
ALTER TABLE users
  -- Keep search_mode but update values
  MODIFY COLUMN search_mode ENUM('find_roommate_first', 'find_room_first')
    DEFAULT 'find_roommate_first' COMMENT 'User prefers to find roommate or room first';

-- Add custom avatar upload column
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS avatar_custom VARCHAR(255) DEFAULT NULL
    COMMENT 'User uploaded custom avatar' AFTER avatar;

-- Add lifestyle preference columns
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS sleep_schedule ENUM('early_bird', 'night_owl', 'flexible') DEFAULT NULL
    COMMENT 'Sleep schedule preference',
  ADD COLUMN IF NOT EXISTS work_schedule ENUM('office', 'shift', 'wfh', 'student') DEFAULT NULL
    COMMENT 'Work schedule type',
  ADD COLUMN IF NOT EXISTS drinking ENUM('no', 'social', 'frequent') DEFAULT NULL
    COMMENT 'Drinking/partying preference',
  ADD COLUMN IF NOT EXISTS guests_policy ENUM('no_guests', 'occasional', 'frequent') DEFAULT NULL
    COMMENT 'Guest policy preference',
  ADD COLUMN IF NOT EXISTS move_in_date DATE DEFAULT NULL
    COMMENT 'Desired move-in date',
  ADD COLUMN IF NOT EXISTS stay_duration ENUM('1month', '3months', '6months', '1year_plus') DEFAULT NULL
    COMMENT 'How long planning to stay',
  ADD COLUMN IF NOT EXISTS occupation VARCHAR(100) DEFAULT NULL
    COMMENT 'User occupation/job title';

-- Add matching stage tracking
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS matching_stage ENUM('finding_initial', 'finding_secondary', 'completed')
    DEFAULT 'finding_initial'
    COMMENT 'Current stage in two-phase matching';

-- Add verification fields
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS verification_status ENUM('unverified', 'pending', 'verified')
    DEFAULT 'unverified'
    COMMENT 'Account verification status',
  ADD COLUMN IF NOT EXISTS facebook_url VARCHAR(255) DEFAULT NULL
    COMMENT 'Facebook profile URL for verification',
  ADD COLUMN IF NOT EXISTS linkedin_url VARCHAR(255) DEFAULT NULL
    COMMENT 'LinkedIn profile URL for verification',
  ADD COLUMN IF NOT EXISTS id_verified BOOLEAN DEFAULT FALSE
    COMMENT 'ID verification completed';

-- Add index for new columns
ALTER TABLE users
  ADD INDEX IF NOT EXISTS idx_sleep_schedule (sleep_schedule),
  ADD INDEX IF NOT EXISTS idx_work_schedule (work_schedule),
  ADD INDEX IF NOT EXISTS idx_matching_stage (matching_stage),
  ADD INDEX IF NOT EXISTS idx_verification (verification_status),
  ADD INDEX IF NOT EXISTS idx_move_in_date (move_in_date);

-- ============================================
-- UPDATE: rooms table
-- Add location coordinates and room type
-- ============================================

-- Add ward/location columns
ALTER TABLE rooms
  ADD COLUMN IF NOT EXISTS ward VARCHAR(100) DEFAULT NULL
    COMMENT 'Ward/Commune name' AFTER district_id;

-- Add coordinates for distance calculation
ALTER TABLE rooms
  ADD COLUMN IF NOT EXISTS latitude DECIMAL(10, 8) DEFAULT NULL
    COMMENT 'Latitude coordinate',
  ADD COLUMN IF NOT EXISTS longitude DECIMAL(11, 8) DEFAULT NULL
    COMMENT 'Longitude coordinate';

-- Add room type
ALTER TABLE rooms
  ADD COLUMN IF NOT EXISTS room_type ENUM('apartment', 'house', 'mini_apartment', 'villa') DEFAULT NULL
    COMMENT 'Type of accommodation';

-- Add geocoding status
ALTER TABLE rooms
  ADD COLUMN IF NOT EXISTS geocoded BOOLEAN DEFAULT FALSE
    COMMENT 'Whether address has been geocoded';

-- Add indexes for location queries
ALTER TABLE rooms
  ADD INDEX IF NOT EXISTS idx_coordinates (latitude, longitude),
  ADD INDEX IF NOT EXISTS idx_room_type (room_type),
  ADD INDEX IF NOT EXISTS idx_geocoded (geocoded);

-- ============================================
-- UPDATE: districts table (if needed)
-- Ensure lat/lng columns exist
-- ============================================

ALTER TABLE districts
  ADD COLUMN IF NOT EXISTS latitude DECIMAL(10, 8) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS longitude DECIMAL(11, 8) DEFAULT NULL;

-- ============================================
-- NEW: amenities reference table (optional)
-- For admin management of available amenities
-- ============================================

CREATE TABLE IF NOT EXISTS amenities_list (
  id INT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(50) UNIQUE NOT NULL COMMENT 'Amenity code (e.g., wifi, ac)',
  name_vi VARCHAR(100) NOT NULL COMMENT 'Vietnamese name',
  name_en VARCHAR(100) NOT NULL COMMENT 'English name',
  icon VARCHAR(50) DEFAULT NULL COMMENT 'Icon/emoji representation',
  category ENUM('essential', 'comfort', 'convenience', 'security') DEFAULT 'comfort',
  is_active BOOLEAN DEFAULT TRUE,
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_active (is_active),
  INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default amenities
INSERT INTO amenities_list (code, name_vi, name_en, icon, category, sort_order) VALUES
  ('wifi', 'Wifi', 'Wifi', '📶', 'essential', 1),
  ('ac', 'Điều hòa', 'Air Conditioning', '❄️', 'comfort', 2),
  ('kitchen', 'Bếp', 'Kitchen', '🍳', 'essential', 3),
  ('parking', 'Chỗ đỗ xe', 'Parking', '🅿️', 'convenience', 4),
  ('laundry', 'Máy giặt', 'Washing Machine', '🧺', 'convenience', 5),
  ('furniture', 'Nội thất', 'Furniture', '🛋️', 'essential', 6),
  ('balcony', 'Ban công', 'Balcony', '🪟', 'comfort', 7),
  ('elevator', 'Thang máy', 'Elevator', '🛗', 'convenience', 8),
  ('security', 'Bảo vệ 24/7', 'Security', '🔒', 'security', 9),
  ('gym', 'Phòng gym', 'Gym', '💪', 'comfort', 10),
  ('pool', 'Hồ bơi', 'Swimming Pool', '🏊', 'comfort', 11),
  ('pet_allowed', 'Cho phép thú cưng', 'Pet Allowed', '🐕', 'convenience', 12)
ON DUPLICATE KEY UPDATE name_vi=VALUES(name_vi);

-- ============================================
-- NEW: preferences reference table (optional)
-- For admin management of lifestyle preferences
-- ============================================

CREATE TABLE IF NOT EXISTS preferences_list (
  id INT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(50) UNIQUE NOT NULL COMMENT 'Preference code',
  name_vi VARCHAR(100) NOT NULL COMMENT 'Vietnamese name',
  name_en VARCHAR(100) NOT NULL COMMENT 'English name',
  type ENUM('scale', 'select', 'boolean', 'multiselect') DEFAULT 'select',
  options JSON DEFAULT NULL COMMENT 'Available options for this preference',
  category ENUM('lifestyle', 'schedule', 'social', 'budget') DEFAULT 'lifestyle',
  weight INT DEFAULT 10 COMMENT 'Weight in compatibility calculation',
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_active (is_active),
  INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default preferences
INSERT INTO preferences_list (code, name_vi, name_en, type, options, category, weight) VALUES
  ('cleanliness', 'Mức độ sạch sẽ', 'Cleanliness Level', 'scale',
   '{"min": 1, "max": 5, "labels": ["Thoải mái", "Trung bình", "Khá sạch", "Sạch", "Rất sạch"]}',
   'lifestyle', 25),
  ('noise_tolerance', 'Độ chịu đựng tiếng ồn', 'Noise Tolerance', 'scale',
   '{"min": 1, "max": 5, "labels": ["Yên tĩnh tuyệt đối", "Ít ồn", "Trung bình", "OK với ồn", "Thích tiệc tùng"]}',
   'lifestyle', 25),
  ('sleep_schedule', 'Lịch ngủ', 'Sleep Schedule', 'select',
   '["early_bird", "night_owl", "flexible"]',
   'schedule', 20),
  ('work_schedule', 'Lịch làm việc', 'Work Schedule', 'select',
   '["office", "shift", "wfh", "student"]',
   'schedule', 10),
  ('smoking', 'Hút thuốc', 'Smoking', 'boolean', '{}', 'lifestyle', 15),
  ('drinking', 'Uống rượu/Tiệc tùng', 'Drinking/Partying', 'select',
   '["no", "social", "frequent"]',
   'social', 10),
  ('pets', 'Thú cưng', 'Pets', 'boolean', '{}', 'lifestyle', 10),
  ('guests_policy', 'Chính sách khách', 'Guests Policy', 'select',
   '["no_guests", "occasional", "frequent"]',
   'social', 5)
ON DUPLICATE KEY UPDATE name_vi=VALUES(name_vi);

-- ============================================
-- COMPLETED
-- ============================================

SELECT 'Schema V2 migration completed successfully!' as Status;
