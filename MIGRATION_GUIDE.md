# 🚀 RUMI - Hướng Dẫn Migration và Cài Đặt

## 📋 Tổng Quan
Hướng dẫn này giúp bạn cài đặt và migrate database cho hệ thống RUMI với các tính năng mới:
- Two-stage matching (tìm bạn trước hoặc tìm phòng trước)
- Lifestyle preferences (lối sống)
- Enhanced filtering với Mapbox
- Room posting với image upload
- Admin panel

---

## ⚠️ YÊU CẦU TRƯỚC KHI BẮT ĐẦU

### 1. Kiểm tra PHP Version
```bash
php -v
# Cần PHP >= 7.4
```

### 2. Kiểm tra MySQL/MariaDB
```bash
mysql --version
# Cần MySQL >= 5.7 hoặc MariaDB >= 10.2
```

### 3. Kiểm tra PHP Extensions
```bash
php -m | grep -E "pdo|pdo_mysql|mbstring|json|gd"
# Phải có: pdo, pdo_mysql, mbstring, json, gd
```

### 4. Kiểm tra quyền ghi
```bash
cd /path/to/rumi
mkdir -p logs assets/uploads/rooms
chmod 755 logs assets/uploads/rooms
```

---

## 📦 BƯỚC 1: BACKUP DATABASE

**⚠️ QUAN TRỌNG: Backup trước khi migrate!**

```bash
# Backup toàn bộ database
mysqldump -u username -p rumi_database > backup_before_migration_$(date +%Y%m%d_%H%M%S).sql

# Hoặc backup riêng tables quan trọng
mysqldump -u username -p rumi_database users rooms matches > backup_critical_tables.sql
```

---

## 🗄️ BƯỚC 2: CHẠY MIGRATION DATABASE

### Option 1: Qua Web Interface (Đề nghị)

1. **Truy cập migration runner:**
   ```
   http://your-domain.com/database/migrations/run_v2_migration.php
   ```

2. **Nhấn nút "Run Migration"**

3. **Kiểm tra kết quả:**
   - ✅ Success: Tất cả ALTER TABLE thành công
   - ❌ Error: Xem log và chạy lại hoặc dùng Option 2

### Option 2: Qua Command Line

```bash
# Di chuyển vào thư mục migrations
cd /path/to/rumi/database/migrations

# Chạy migration SQL
mysql -u username -p rumi_database < update_schema_v2.sql

# Kiểm tra kết quả
mysql -u username -p rumi_database -e "DESCRIBE users;"
mysql -u username -p rumi_database -e "DESCRIBE rooms;"
mysql -u username -p rumi_database -e "SELECT * FROM amenities_list;"
mysql -u username -p rumi_database -e "SELECT * FROM preferences_list;"
```

### Option 3: Từng Bước (Nếu gặp lỗi)

```bash
# 1. Tạo file riêng cho từng phần
# Alter users table
mysql -u username -p rumi_database << 'EOF'
ALTER TABLE users
  MODIFY COLUMN search_mode ENUM('find_roommate_first', 'find_room_first') DEFAULT 'find_roommate_first',
  ADD COLUMN sleep_schedule ENUM('early_bird', 'night_owl', 'flexible') AFTER preferences,
  ADD COLUMN work_schedule ENUM('office', 'shift', 'wfh', 'student') AFTER sleep_schedule;
EOF

# 2. Kiểm tra
mysql -u username -p rumi_database -e "SHOW COLUMNS FROM users LIKE '%schedule%';"

# 3. Tiếp tục với rooms table...
```

---

## 📊 BƯỚC 3: POPULATE DATA DEFAULTS

### 1. Insert Amenities
```sql
INSERT INTO amenities_list (code, name_vi, name_en, icon, sort_order, is_active) VALUES
('wifi', 'Wifi', 'Wifi', '📶', 1, 1),
('ac', 'Điều hòa', 'Air Conditioning', '❄️', 2, 1),
('kitchen', 'Bếp', 'Kitchen', '🍳', 3, 1),
('parking', 'Chỗ đỗ xe', 'Parking', '🅿️', 4, 1),
('laundry', 'Máy giặt', 'Washing Machine', '🧺', 5, 1),
('furniture', 'Nội thất', 'Furniture', '🛋️', 6, 1),
('elevator', 'Thang máy', 'Elevator', '🛗', 7, 1),
('security', 'An ninh', 'Security', '🔒', 8, 1),
('balcony', 'Ban công', 'Balcony', '🌿', 9, 1),
('gym', 'Phòng gym', 'Gym', '💪', 10, 1),
('pool', 'Hồ bơi', 'Swimming Pool', '🏊', 11, 1),
('pet_friendly', 'Cho phép thú cưng', 'Pet Friendly', '🐕', 12, 1);
```

### 2. Insert Preferences
```sql
INSERT INTO preferences_list (code, name_vi, name_en, icon, weight, category, is_active) VALUES
('cleanliness', 'Sạch sẽ', 'Cleanliness', '✨', 25, 'lifestyle', 1),
('noise_tolerance', 'Độ ồn', 'Noise Tolerance', '🔊', 25, 'lifestyle', 1),
('sleep_schedule', 'Lịch ngủ', 'Sleep Schedule', '😴', 20, 'lifestyle', 1),
('smoking', 'Hút thuốc', 'Smoking', '🚬', 15, 'lifestyle', 1),
('drinking', 'Uống rượu', 'Drinking', '🍺', 10, 'lifestyle', 1),
('guests_policy', 'Chính sách khách', 'Guests Policy', '👥', 5, 'lifestyle', 1),
('budget', 'Ngân sách', 'Budget', '💰', 30, 'financial', 1),
('location', 'Vị trí', 'Location', '📍', 25, 'location', 1);
```

---

## 🔍 BƯỚC 4: KIỂM TRA MIGRATION

### 1. Kiểm tra cấu trúc tables
```sql
-- Check users table
DESCRIBE users;
-- Phải có: sleep_schedule, work_schedule, drinking, guests_policy,
--          move_in_date, stay_duration, occupation, facebook_url, linkedin_url

-- Check rooms table
DESCRIBE rooms;
-- Phải có: ward, latitude, longitude, room_type, geocoded

-- Check new tables
SELECT COUNT(*) FROM amenities_list;  -- Phải có 12 rows
SELECT COUNT(*) FROM preferences_list; -- Phải có 8 rows
```

### 2. Test query mới
```sql
-- Test user mode
SELECT id, name, search_mode FROM users LIMIT 5;

-- Test lifestyle fields
SELECT id, name, sleep_schedule, work_schedule FROM users WHERE sleep_schedule IS NOT NULL;

-- Test room coordinates
SELECT id, title, latitude, longitude FROM rooms WHERE geocoded = 1;
```

### 3. Kiểm tra qua web
```
1. Truy cập: http://your-domain.com/pages/swipe.php
2. Mở Console (F12) và xem logs/swipe_debug.log
3. Kiểm tra không có lỗi 500
```

---

## 🐛 BƯỚC 5: DEBUG VÀ XEM LOGS

### 1. Xem debug logs
```bash
# Xem log real-time
tail -f logs/swipe_debug.log

# Xem log errors
tail -f logs/swipe_errors.log

# Search lỗi cụ thể
grep "FATAL ERROR" logs/swipe_debug.log
grep "Step" logs/swipe_debug.log | tail -20
```

### 2. Các bước debug phổ biến

**Lỗi: "Column not found"**
```bash
# Kiểm tra column có tồn tại không
mysql -u username -p rumi_database -e "SHOW COLUMNS FROM users LIKE 'sleep_schedule';"

# Nếu không có, chạy lại ALTER TABLE cho column đó
```

**Lỗi: "Table doesn't exist"**
```bash
# Kiểm tra table
mysql -u username -p rumi_database -e "SHOW TABLES LIKE 'amenities_list';"

# Nếu không có, tạo lại table
mysql -u username -p rumi_database < database/migrations/create_amenities_table.sql
```

**Lỗi: "Call to undefined method"**
```bash
# Kiểm tra file Model có đầy đủ methods không
grep -n "canAccessRoomTab" includes/User.php
grep -n "getRoomsForAllMatches" includes/Room.php
grep -n "getUsersWhoLikedSameRooms" includes/Match.php
```

### 3. Kiểm tra từng step trong log
```bash
# Log sẽ cho biết step nào bị lỗi
# Ví dụ: "Step 18: Liked room IDs" => Check method getLikedRoomIds()
tail -50 logs/swipe_debug.log
```

---

## 📁 BƯỚC 6: CẬP NHẬT MAPBOX TOKEN

### 1. Lấy Mapbox Access Token
1. Đăng ký tại: https://account.mapbox.com/
2. Tạo token mới với scope: `styles:read`, `fonts:read`, `geocoding:read`
3. Copy token

### 2. Cập nhật token trong code
```bash
# Tìm và thay MAPBOX_TOKEN trong các files:
grep -r "MAPBOX_TOKEN" --include="*.php" .

# Cập nhật trong:
# - components/filter-modal-v2.php
# - pages/post-room-v2.php
```

Hoặc tạo constants:
```php
// config/constants.php
define('MAPBOX_ACCESS_TOKEN', 'pk.eyJ1IjoieW91ci11c2VybmFtZSIsImEiOiJ5b3VyLXRva2VuIn0...');
```

---

## 🔐 BƯỚC 7: CẤU HÌNH ADMIN PANEL

### 1. Đổi mật khẩu admin
```php
// File: admin/login.php (line ~11)
// Tìm và thay đổi:
define('ADMIN_PASSWORD', 'your_secure_password_here');
```

### 2. Truy cập admin panel
```
URL: http://your-domain.com/admin/login.php
Username: admin
Password: [password bạn vừa đổi]
```

### 3. Kiểm tra stats
- Xem số users, rooms, matches
- Kiểm tra recent users và rooms
- Test navigation

---

## 📋 CHECKLIST HOÀN THÀNH

Đánh dấu khi hoàn thành:

- [ ] ✅ Backup database thành công
- [ ] ✅ Chạy migration update_schema_v2.sql
- [ ] ✅ Insert amenities_list (12 rows)
- [ ] ✅ Insert preferences_list (8 rows)
- [ ] ✅ Kiểm tra cấu trúc tables mới
- [ ] ✅ Test query users với search_mode
- [ ] ✅ Tạo thư mục logs/ và set quyền
- [ ] ✅ Tạo thư mục assets/uploads/rooms/
- [ ] ✅ Cập nhật Mapbox token
- [ ] ✅ Đổi admin password
- [ ] ✅ Test swipe.php không có lỗi 500
- [ ] ✅ Xem logs/swipe_debug.log
- [ ] ✅ Test profile-setup-v2.php
- [ ] ✅ Test post-room-v2.php
- [ ] ✅ Test filter modal với Mapbox
- [ ] ✅ Test admin panel login

---

## 🆘 TROUBLESHOOTING

### Lỗi 500 tại swipe.php

**1. Xem log đầu tiên:**
```bash
tail -100 logs/swipe_debug.log
tail -100 logs/swipe_errors.log
```

**2. Check step nào bị lỗi:**
- Step 1-6: Lỗi loading dependencies
- Step 7-8: Lỗi get user data
- Step 12-21: Lỗi matching logic
- Step 22-31: Lỗi room logic

**3. Các lỗi phổ biến:**

```bash
# Lỗi: "Cannot find method canAccessRoomTab"
# Fix: Kiểm tra includes/User.php có method này chưa
grep -n "canAccessRoomTab" includes/User.php

# Lỗi: "Column 'search_mode' not found"
# Fix: Chạy lại migration
mysql -u username -p rumi_database -e "ALTER TABLE users MODIFY COLUMN search_mode ENUM('find_roommate_first', 'find_room_first');"

# Lỗi: "Call to undefined function renderLockedTabState"
# Fix: Kiểm tra file components/empty-state-locked.php có tồn tại
ls -la components/empty-state-locked.php
```

### Không thấy logs/swipe_debug.log

```bash
# Tạo thư mục logs
mkdir -p logs
chmod 755 logs

# Test ghi file
echo "test" > logs/test.txt

# Nếu không ghi được, check quyền
ls -la logs/
sudo chown -R www-data:www-data logs/
```

### Migration bị lỗi giữa chừng

```bash
# 1. Restore từ backup
mysql -u username -p rumi_database < backup_before_migration_*.sql

# 2. Chạy từng ALTER TABLE một
# Copy từng câu trong update_schema_v2.sql và chạy riêng

# 3. Kiểm tra lỗi cụ thể
mysql -u username -p rumi_database
> ALTER TABLE users ADD COLUMN sleep_schedule ENUM('early_bird', 'night_owl', 'flexible');
> -- Xem error message
```

---

## 📞 HỖ TRỢ

Nếu gặp vấn đề:

1. **Check logs:**
   - `logs/swipe_debug.log` - Chi tiết từng step
   - `logs/swipe_errors.log` - PHP errors
   - MySQL error log (thường tại `/var/log/mysql/error.log`)

2. **Test từng phần:**
   - Test database connection: `php -r "require 'config/database.php'; var_dump(getDB());"`
   - Test User model: `php -r "require 'includes/User.php'; $u = new User(); var_dump($u);"`

3. **Rollback nếu cần:**
   ```bash
   mysql -u username -p rumi_database < backup_before_migration_*.sql
   ```

---

## 🎉 HOÀN TẤT

Sau khi hoàn thành tất cả steps:

1. **Test toàn bộ flow:**
   - Đăng ký user mới
   - Complete profile với lifestyle preferences
   - Test swipe roommates
   - Test swipe rooms
   - Test filter với Mapbox
   - Test post room với images

2. **Monitor logs:**
   ```bash
   tail -f logs/swipe_debug.log &
   tail -f logs/swipe_errors.log &
   ```

3. **Remove debug mode khi production:**
   ```php
   // swipe.php - Comment out sau khi test xong:
   // error_reporting(E_ALL);
   // ini_set('display_errors', 1);
   ```

Chúc bạn migrate thành công! 🚀
