-- Initial Preferences Data
-- Purpose: Insert basic preferences if not exist
-- This runs BEFORE the options_config migration

-- Insert preferences if they don't exist yet
INSERT IGNORE INTO preferences_list (code, name_vi, name_en, icon, weight, category, is_active, sort_order) VALUES
-- Lifestyle Preferences
('sleep_schedule', 'Lịch ngủ', 'Sleep Schedule', '😴', 20, 'lifestyle', 1, 10),
('work_schedule', 'Lịch làm việc', 'Work Schedule', '💼', 15, 'lifestyle', 1, 20),
('drinking', 'Uống rượu', 'Drinking', '🍺', 10, 'lifestyle', 1, 30),
('guests_policy', 'Chính sách khách', 'Guests Policy', '👥', 10, 'lifestyle', 1, 40),

-- Cleanliness & Noise
('cleanliness', 'Sạch sẽ', 'Cleanliness', '✨', 30, 'lifestyle', 1, 50),
('noise_tolerance', 'Chịu tiếng ồn', 'Noise Tolerance', '🔊', 25, 'lifestyle', 1, 60),

-- Habits
('smoking', 'Hút thuốc', 'Smoking', '🚬', 20, 'lifestyle', 1, 70),
('pets', 'Thú cưng', 'Pets', '🐕', 15, 'lifestyle', 1, 80),

-- Financial
('budget', 'Ngân sách', 'Budget', '💰', 30, 'financial', 1, 10),

-- Location
('location', 'Vị trí', 'Location', '📍', 25, 'location', 1, 10);

-- Verify
SELECT * FROM preferences_list ORDER BY category, sort_order;
