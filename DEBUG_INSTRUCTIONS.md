# 🐛 Hướng Dẫn Debug Lỗi 500 - RUMI

## 📍 HIỆN TRẠNG

Code debug đã được thêm vào `pages/swipe.php` với **32 checkpoints** để track từng bước thực thi.

---

## 🔍 CÁCH XEM LỖI

### Bước 1: Tạo thư mục logs (nếu chưa có)
```bash
cd /home/user/rumi
mkdir -p logs
chmod 755 logs
```

### Bước 2: Truy cập trang swipe.php
```
http://your-domain.com/pages/swipe.php
```

### Bước 3: Xem log file
```bash
# Xem debug log (chi tiết từng step)
cat logs/swipe_debug.log

# Hoặc xem real-time
tail -f logs/swipe_debug.log

# Xem PHP errors
cat logs/swipe_errors.log
```

---

## 📊 HIỂU LOG DEBUG

### Log sẽ có format:
```
[2024-01-15 10:30:45] ========== SWIPE.PHP START ==========
[2024-01-15 10:30:45] Step 1: Loading dependencies
[2024-01-15 10:30:45] Step 2: Dependencies loaded successfully
[2024-01-15 10:30:45] Step 3: empty-state-locked.php loaded
[2024-01-15 10:30:45] Step 4: Starting session
[2024-01-15 10:30:45] Step 5: User ID | Data: 123
[2024-01-15 10:30:45] Step 6: Initializing models
[2024-01-15 10:30:45] Step 7: Getting current user
[2024-01-15 10:30:45] Step 8: Current user loaded | Data: Array (...)
...
[2024-01-15 10:30:46] Step 32: Logic completed successfully
```

### Nếu có lỗi:
```
[2024-01-15 10:30:45] Step 18: Liked room IDs
[2024-01-15 10:30:45] ERROR in find_room_first logic: Column 'search_mode' not found
[2024-01-15 10:30:45] ========== FATAL ERROR ==========
[2024-01-15 10:30:45] Error message: Column 'search_mode' not found
[2024-01-15 10:30:45] Error file: /home/user/rumi/includes/User.php
[2024-01-15 10:30:45] Error line: 358
[2024-01-15 10:30:45] Stack trace: ...
```

---

## 🎯 PHÂN TÍCH LỖI THEO STEP

### Steps 1-6: Lỗi Loading Dependencies
**Nguyên nhân phổ biến:**
- File không tồn tại
- Lỗi syntax trong file require
- Quyền đọc file

**Cách fix:**
```bash
# Kiểm tra file tồn tại
ls -la config/database.php
ls -la includes/User.php
ls -la includes/Room.php
ls -la includes/Match.php

# Kiểm tra quyền
chmod 644 config/*.php
chmod 644 includes/*.php
```

### Steps 7-11: Lỗi User Data
**Nguyên nhân phổ biến:**
- User không tồn tại trong DB
- Session bị lỗi
- search_mode column chưa có

**Cách fix:**
```sql
-- Kiểm tra user
SELECT id, name, search_mode FROM users WHERE id = YOUR_USER_ID;

-- Kiểm tra column search_mode
DESCRIBE users;

-- Nếu thiếu, chạy migration
ALTER TABLE users
  MODIFY COLUMN search_mode ENUM('find_roommate_first', 'find_room_first')
  DEFAULT 'find_roommate_first';
```

### Steps 12-21: Lỗi Find Roommate Logic
**Các method cần kiểm tra:**
- `canAccessRoommateTab()` (User.php)
- `getLikedRoomIds()` (User.php)
- `getUsersWhoLikedSameRooms()` (Match.php)
- `getPotentialMatches()` (User.php)

**Cách fix:**
```bash
# Kiểm tra method tồn tại
grep -n "canAccessRoommateTab" includes/User.php
grep -n "getLikedRoomIds" includes/User.php
grep -n "getUsersWhoLikedSameRooms" includes/Match.php

# Nếu không có, pull code mới hoặc copy từ backup
```

### Steps 22-31: Lỗi Find Room Logic
**Các method cần kiểm tra:**
- `canAccessRoomTab()` (User.php)
- `getMatchedUserIds()` (Match.php)
- `getRoomsForAllMatches()` (Room.php)
- `getPotentialRooms()` (Room.php)

**Cách fix:**
```bash
grep -n "canAccessRoomTab" includes/User.php
grep -n "getMatchedUserIds" includes/Match.php
grep -n "getRoomsForAllMatches" includes/Room.php
```

---

## 🔧 FIX NHANH CÁC LỖI PHỔ BIẾN

### Lỗi 1: "Column 'search_mode' not found"
```sql
-- Kiểm tra
SHOW COLUMNS FROM users LIKE 'search_mode';

-- Fix
ALTER TABLE users
  MODIFY COLUMN search_mode ENUM('find_roommate_first', 'find_room_first')
  DEFAULT 'find_roommate_first';
```

### Lỗi 2: "Call to undefined method canAccessRoomTab"
```bash
# Kiểm tra file User.php có method này chưa
grep -A 20 "canAccessRoomTab" includes/User.php

# Nếu không có, cần update User.php với code mới
# Tham khảo: includes/User.php từ commit 5ca0726
```

### Lỗi 3: "Table 'amenities_list' doesn't exist"
```sql
-- Check table
SHOW TABLES LIKE 'amenities_list';

-- Nếu không có, tạo table
CREATE TABLE amenities_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name_vi VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NOT NULL,
    icon VARCHAR(10),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Lỗi 4: "empty-state-locked.php not found"
```bash
# Check file
ls -la components/empty-state-locked.php

# Nếu không có, tạo file hoặc comment out dòng require
# File này optional, có thể bỏ qua nếu không dùng locked tabs
```

### Lỗi 5: Không ghi được logs
```bash
# Tạo thư mục và set quyền
mkdir -p logs
chmod 755 logs
chown www-data:www-data logs  # hoặc user PHP của bạn

# Test ghi
echo "test" > logs/test.txt
cat logs/test.txt
```

---

## 📞 CÁCH BÁO CÁO LỖI

Khi gặp lỗi 500, hãy cung cấp:

1. **Nội dung log:**
```bash
tail -50 logs/swipe_debug.log
```

2. **Step cuối cùng thành công:**
```
Ví dụ: "Step 18: Liked room IDs"
=> Lỗi xảy ra ở Step 19
```

3. **Error message:**
```
Ví dụ: "Column 'search_mode' not found"
```

4. **Database info:**
```sql
-- Kiểm tra columns
DESCRIBE users;
DESCRIBE rooms;

-- Kiểm tra tables
SHOW TABLES;
```

---

## ✅ CHECKLIST XỬ LÝ LỖI

Làm theo thứ tự:

1. **[ ] Tạo thư mục logs**
   ```bash
   mkdir -p logs && chmod 755 logs
   ```

2. **[ ] Truy cập swipe.php và tạo log**
   ```
   http://your-domain.com/pages/swipe.php
   ```

3. **[ ] Xem log debug**
   ```bash
   cat logs/swipe_debug.log
   ```

4. **[ ] Tìm step bị lỗi**
   ```bash
   grep "ERROR" logs/swipe_debug.log
   grep "FATAL" logs/swipe_debug.log
   ```

5. **[ ] Kiểm tra database**
   ```sql
   DESCRIBE users;
   DESCRIBE rooms;
   SHOW TABLES;
   ```

6. **[ ] Chạy migration nếu cần**
   ```
   http://your-domain.com/database/migrations/run_v2_migration.php
   ```

7. **[ ] Kiểm tra code files**
   ```bash
   ls -la includes/*.php
   ls -la components/*.php
   ```

8. **[ ] Test lại**
   ```
   Refresh swipe.php và xem log mới
   ```

---

## 🚀 SAU KHI FIX XONG

1. **Disable debug mode** (khi production):
```php
// Comment out trong swipe.php:
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
```

2. **Xóa logs cũ**:
```bash
rm logs/swipe_debug.log
rm logs/swipe_errors.log
```

3. **Monitor logs mới**:
```bash
tail -f logs/swipe_debug.log &
```

---

## 📚 TÀI LIỆU THAM KHẢO

- **MIGRATION_GUIDE.md** - Hướng dẫn migrate database đầy đủ
- **logs/swipe_debug.log** - Log chi tiết từng step
- **logs/swipe_errors.log** - PHP errors

---

Chúc bạn debug thành công! 🎉
