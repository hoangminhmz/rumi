-- Seed Data: Populate preferences with options_config
-- Purpose: Add existing hardcoded options into database as JSON
-- Date: 2025-01-06

-- ============================================
-- LIFESTYLE PREFERENCES (ENUM TYPE)
-- ============================================

-- 1. Sleep Schedule
UPDATE preferences_list SET
  field_type = 'enum',
  options_config = JSON_OBJECT(
    'options', JSON_ARRAY(
      JSON_OBJECT('code', 'early_bird', 'name_vi', 'Dậy sớm', 'name_en', 'Early Bird', 'icon', '🌅'),
      JSON_OBJECT('code', 'night_owl', 'name_vi', 'Thức khuya', 'name_en', 'Night Owl', 'icon', '🦉'),
      JSON_OBJECT('code', 'flexible', 'name_vi', 'Linh hoạt', 'name_en', 'Flexible', 'icon', '⏰')
    )
  ),
  description_vi = 'Lịch ngủ và thời gian hoạt động của bạn',
  description_en = 'Your sleep schedule and active hours'
WHERE code = 'sleep_schedule';

-- 2. Work Schedule
UPDATE preferences_list SET
  field_type = 'enum',
  options_config = JSON_OBJECT(
    'options', JSON_ARRAY(
      JSON_OBJECT('code', 'office', 'name_vi', 'Văn phòng (9-5)', 'name_en', 'Office (9-5)', 'icon', '🏢'),
      JSON_OBJECT('code', 'shift', 'name_vi', 'Ca xoay', 'name_en', 'Shift Work', 'icon', '🔄'),
      JSON_OBJECT('code', 'wfh', 'name_vi', 'Làm từ xa', 'name_en', 'Work from Home', 'icon', '🏡'),
      JSON_OBJECT('code', 'student', 'name_vi', 'Sinh viên', 'name_en', 'Student', 'icon', '📚')
    )
  ),
  description_vi = 'Lịch làm việc hoặc học tập',
  description_en = 'Your work or study schedule'
WHERE code = 'work_schedule';

-- 3. Drinking
UPDATE preferences_list SET
  field_type = 'enum',
  options_config = JSON_OBJECT(
    'options', JSON_ARRAY(
      JSON_OBJECT('code', 'no', 'name_vi', 'Không uống', 'name_en', 'No Drinking', 'icon', '🚫'),
      JSON_OBJECT('code', 'social', 'name_vi', 'Uống xã giao', 'name_en', 'Social Drinker', 'icon', '🍺'),
      JSON_OBJECT('code', 'frequent', 'name_vi', 'Thường xuyên', 'name_en', 'Frequent', 'icon', '🍻')
    )
  ),
  description_vi = 'Thói quen uống rượu bia',
  description_en = 'Your drinking habits'
WHERE code = 'drinking';

-- 4. Guests Policy
UPDATE preferences_list SET
  field_type = 'enum',
  options_config = JSON_OBJECT(
    'options', JSON_ARRAY(
      JSON_OBJECT('code', 'no_guests', 'name_vi', 'Không khách', 'name_en', 'No Guests', 'icon', '🚫'),
      JSON_OBJECT('code', 'occasional', 'name_vi', 'Thỉnh thoảng', 'name_en', 'Occasional OK', 'icon', '👥'),
      JSON_OBJECT('code', 'frequent', 'name_vi', 'Chào đón khách', 'name_en', 'Guests Welcome', 'icon', '🎉')
    )
  ),
  description_vi = 'Chính sách đón khách ở nhà',
  description_en = 'Policy for having guests over'
WHERE code = 'guests_policy';

-- ============================================
-- SCALE PREFERENCES (1-5 RATING)
-- ============================================

-- 5. Cleanliness
UPDATE preferences_list SET
  field_type = 'scale',
  options_config = JSON_OBJECT(
    'min', 1,
    'max', 5,
    'labels', JSON_OBJECT(
      '1', JSON_OBJECT('vi', 'Thoải mái', 'en', 'Casual'),
      '3', JSON_OBJECT('vi', 'Trung bình', 'en', 'Moderate'),
      '5', JSON_OBJECT('vi', 'Rất sạch', 'en', 'Very Clean')
    )
  ),
  description_vi = 'Mức độ sạch sẽ mong muốn (1 = thoải mái, 5 = rất sạch)',
  description_en = 'Desired cleanliness level (1 = casual, 5 = very clean)'
WHERE code = 'cleanliness';

-- 6. Noise Tolerance
UPDATE preferences_list SET
  field_type = 'scale',
  options_config = JSON_OBJECT(
    'min', 1,
    'max', 5,
    'labels', JSON_OBJECT(
      '1', JSON_OBJECT('vi', 'Yên tĩnh', 'en', 'Quiet'),
      '3', JSON_OBJECT('vi', 'Trung bình', 'en', 'Moderate'),
      '5', JSON_OBJECT('vi', 'OK với ồn', 'en', 'Tolerant')
    )
  ),
  description_vi = 'Mức độ chịu đựng tiếng ồn (1 = cần yên tĩnh, 5 = chấp nhận ồn)',
  description_en = 'Noise tolerance level (1 = need quiet, 5 = tolerant)'
WHERE code = 'noise_tolerance';

-- ============================================
-- BOOLEAN PREFERENCES (YES/NO)
-- ============================================

-- 7. Smoking
UPDATE preferences_list SET
  field_type = 'boolean',
  options_config = JSON_OBJECT(
    'true_label', JSON_OBJECT('vi', 'Cho phép hút thuốc', 'en', 'Smoking OK', 'icon', '🚬'),
    'false_label', JSON_OBJECT('vi', 'Không hút thuốc', 'en', 'No Smoking', 'icon', '🚭')
  ),
  description_vi = 'Chấp nhận hút thuốc trong nhà',
  description_en = 'Allow smoking indoors'
WHERE code = 'smoking';

-- 8. Pets
UPDATE preferences_list SET
  field_type = 'boolean',
  options_config = JSON_OBJECT(
    'true_label', JSON_OBJECT('vi', 'Cho phép thú cưng', 'en', 'Pet Friendly', 'icon', '🐕'),
    'false_label', JSON_OBJECT('vi', 'Không thú cưng', 'en', 'No Pets', 'icon', '🚫')
  ),
  description_vi = 'Chấp nhận nuôi thú cưng',
  description_en = 'Allow pets'
WHERE code = 'pets';

-- ============================================
-- RANGE PREFERENCES (MIN-MAX)
-- ============================================

-- 9. Budget
UPDATE preferences_list SET
  field_type = 'range',
  options_config = JSON_OBJECT(
    'min', 0,
    'max', 20000000,
    'step', 500000,
    'unit', 'VND',
    'format', 'currency'
  ),
  description_vi = 'Khoảng ngân sách thuê phòng mỗi tháng',
  description_en = 'Monthly rent budget range'
WHERE code = 'budget';

-- ============================================
-- VERIFY DATA
-- ============================================

SELECT
  code,
  name_vi,
  field_type,
  options_config,
  weight,
  is_active
FROM preferences_list
ORDER BY category, sort_order;
