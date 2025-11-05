# 🧪 Hướng Dẫn Test Từng Bước

## ⚠️ CẢ 2 FILE DEBUG BỊ LỖI 500

Vì `simple_test.php` và `debug_test.php` đều bị lỗi 500, tôi đã tạo **5 file test đơn giản hơn** để test từng phần.

---

## 📋 TRÌNH TỰ TEST (Quan trọng!)

Làm theo thứ tự từ test 1 → 5:

### TEST 1: PHP Cơ Bản ⭐ BẮT ĐẦU TỪ ĐÂY
```
👉 http://your-domain.com/test1_basic.php
```

**Mục đích:** Kiểm tra PHP có hoạt động không

**Kết quả mong đợi:**
- Thấy text "TEST 1: PHP is working!"
- Thấy PHP Version
- Thấy phpinfo() table

**Nếu bị lỗi 500:**
```
→ Vấn đề: Web server hoặc PHP config
→ Kiểm tra:
  - Apache/Nginx có chạy không?
  - PHP có được cài không? (php -v)
  - Check error log: /var/log/apache2/error.log
  - Check error log: /var/log/nginx/error.log
```

---

### TEST 2: Error Display
```
👉 http://your-domain.com/test2_error.php
```

**Mục đích:** Kiểm tra PHP có hiển thị lỗi không

**Kết quả mong đợi:**
- Thấy "Error display enabled"
- Thấy "If you see this, error reporting is working!"

**Nếu bị lỗi 500:**
```
→ Vấn đề: PHP syntax error hoặc config
→ Check: error_log PHP
```

---

### TEST 3: Database Connection
```
👉 http://your-domain.com/test3_database.php
```

**Mục đích:** Kiểm tra kết nối database

**Kết quả mong đợi:**
```
Step 1: Loading database.php...
✓ database.php exists
✓ database.php loaded
✓ getDB() function exists
Step 2: Connecting to database...
✓ Database connected!
Step 3: Testing query...
✓ Query works! Result: 1
Step 4: Counting users...
✓ Total users: X
✓ ALL TESTS PASSED!
```

**Nếu bị lỗi:**
- "database.php not found" → Check file config/database.php có tồn tại
- "getDB() function not found" → Lỗi trong database.php
- "Database connection failed" → Sai username/password/database name
- "Table 'users' doesn't exist" → Database chưa có tables

**Fix:**
```bash
# Kiểm tra file
ls -la config/database.php

# Test MySQL connection
mysql -u username -p database_name

# Check tables
mysql -u username -p database_name -e "SHOW TABLES;"
```

---

### TEST 4: Check Tables
```
👉 http://your-domain.com/test4_tables.php
```

**Mục đích:** Kiểm tra cấu trúc database

**Kết quả mong đợi:**
- Danh sách tất cả columns trong users table
- New Columns Check:
  - ✓ search_mode (màu xanh)
  - ✓ sleep_schedule (màu xanh)
  - ✓ work_schedule (màu xanh)
  - ✓ drinking (màu xanh)
  - ✓ guests_policy (màu xanh)
- New Tables Check:
  - ✓ amenities_list (12 rows)
  - ✓ preferences_list (8 rows)

**Nếu thấy ✗ (đỏ):**
```
→ Column thiếu: Cần chạy migration
→ Table thiếu: Cần tạo table

Fix:
1. Truy cập: http://your-domain.com/database/migrations/run_v2_migration.php
2. Click "Run Migration"
3. Hoặc chạy manual:
   mysql -u username -p database_name < database/migrations/update_schema_v2.sql
```

---

### TEST 5: Load Models
```
👉 http://your-domain.com/test5_models.php
```

**Mục đích:** Kiểm tra các Model files và methods

**Kết quả mong đợi:**
```
Step 1: Database...
✓ Database config loaded

Step 2: Functions...
✓ functions.php loaded

Step 3: User model...
✓ User.php loaded
✓ User object created

User methods:
✓ canAccessRoomTab()
✓ canAccessRoommateTab()
✓ getLikedRoomIds()
✓ getMatchedUsers()

Step 4: Room model...
✓ Room.php loaded
✓ Room object created

Room methods:
✓ getRoomsForAllMatches()
✓ getPotentialRooms()

Step 5: Match model...
✓ Match.php loaded
✓ Match object created

Match methods:
✓ getUsersWhoLikedSameRooms()
✓ getMatchedUserIds()
✓ calculateCompatibilityScore()

✓ ALL MODELS LOADED SUCCESSFULLY!
```

**Nếu thấy ✗ (đỏ):**
```
→ Method thiếu: File Model chưa update

Fix:
1. Check file: includes/User.php
2. Check file: includes/Room.php
3. Check file: includes/Match.php
4. Cần update từ code mới (commit 5ca0726)
```

---

## 🎯 WORKFLOW DEBUG

```
START
  ↓
Test 1 (test1_basic.php)
  ↓ PASS?
  ├─ NO → Fix web server / PHP
  └─ YES
      ↓
Test 2 (test2_error.php)
  ↓ PASS?
  ├─ NO → Fix PHP config
  └─ YES
      ↓
Test 3 (test3_database.php)
  ↓ PASS?
  ├─ NO → Fix database connection
  └─ YES
      ↓
Test 4 (test4_tables.php)
  ↓ All ✓ green?
  ├─ NO → Run migration
  └─ YES
      ↓
Test 5 (test5_models.php)
  ↓ All ✓ green?
  ├─ NO → Update Model files
  └─ YES
      ↓
Test swipe.php
  ↓ Works?
  ├─ NO → Check logs/swipe_debug.log
  └─ YES → DONE! 🎉
```

---

## 📊 BẢNG TÓM TẮT

| Test | File | Kiểm tra gì | Lỗi thường gặp | Fix |
|------|------|-------------|----------------|-----|
| 1 | test1_basic.php | PHP hoạt động | 500 | Check web server |
| 2 | test2_error.php | Error display | 500 | Check PHP config |
| 3 | test3_database.php | DB connection | Connection failed | Check config/database.php |
| 4 | test4_tables.php | Tables/Columns | ✗ missing | Run migration |
| 5 | test5_models.php | Models/Methods | ✗ missing | Update files |

---

## 🆘 NẾU TẤT CẢ BỊ LỖI 500

**Nguyên nhân có thể:**

1. **PHP không hoạt động**
```bash
# Kiểm tra PHP
php -v

# Kiểm tra web server
systemctl status apache2
# hoặc
systemctl status nginx
```

2. **File .htaccess sai**
```bash
# Tạm thời rename để test
mv .htaccess .htaccess.bak
# Test lại
```

3. **PHP memory/timeout**
```bash
# Kiểm tra PHP settings
php -i | grep memory_limit
php -i | grep max_execution_time
```

4. **Check error logs**
```bash
# Apache
tail -f /var/log/apache2/error.log

# Nginx
tail -f /var/log/nginx/error.log

# PHP-FPM
tail -f /var/log/php-fpm/error.log
```

---

## ✅ KHI TẤT CẢ PASS

Nếu test 1-5 đều ✓ xanh:

1. Test swipe.php:
   ```
   http://your-domain.com/pages/swipe.php
   ```

2. Nếu vẫn lỗi, xem log:
   ```bash
   cat logs/swipe_debug.log
   ```

3. Tìm dòng cuối cùng có "Step XX"

4. Đọc hướng dẫn fix tại:
   - DEBUG_INSTRUCTIONS.md
   - MIGRATION_GUIDE.md

---

## 📞 BÁO CÁO LỖI

Khi báo cáo, cung cấp:

1. Test nào bị lỗi (1, 2, 3, 4, hay 5)
2. Screenshot error message
3. Nội dung error log (nếu có)

Ví dụ:
```
Test 3 bị lỗi:
ERROR: Access denied for user 'root'@'localhost'
```

---

## 🎯 MONG ĐỢI

Sau khi test xong:
- Test 1: ✓ PASS
- Test 2: ✓ PASS
- Test 3: ✓ PASS
- Test 4: Tất cả ✓ xanh
- Test 5: Tất cả ✓ xanh
- swipe.php: Không còn lỗi 500

Good luck! 🚀
