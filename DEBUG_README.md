# 🔧 Hướng Dẫn Debug Lỗi 500

## 🚀 Bắt Đầu Nhanh

### Bước 1: Chạy File Test
Truy cập một trong hai file sau:

```
👉 http://your-domain.com/simple_test.php (Đề nghị - dễ đọc)
hoặc
👉 http://your-domain.com/debug_test.php (Chi tiết hơn)
```

### Bước 2: Xem Kết Quả
- **✅ Màu xanh** = OK
- **❌ Màu đỏ** = Có vấn đề, cần fix

### Bước 3: Fix Các Vấn đề

#### Nếu thấy "❌ Column missing"
→ Chạy migration:
```
http://your-domain.com/database/migrations/run_v2_migration.php
```

#### Nếu thấy "❌ Table missing"
→ Đọc file: `MIGRATION_GUIDE.md`

#### Nếu thấy "❌ Method missing"
→ Update file Model từ code mới nhất

---

## 📁 Các File Debug

| File | Mục đích | Khi nào dùng |
|------|----------|--------------|
| `simple_test.php` | Test cơ bản, UI đẹp | Lần đầu debug, dễ đọc |
| `debug_test.php` | Test chi tiết | Cần info kỹ thuật |
| `pages/swipe.php` | Có debug logs | Trang bị lỗi 500 |
| `logs/swipe_debug.log` | Log chi tiết | Xem flow thực thi |
| `logs/swipe_errors.log` | PHP errors | Xem lỗi PHP |

---

## 🔍 Các Lỗi Phổ Biến

### Lỗi 1: Không thấy file logs
```bash
cd /path/to/rumi
mkdir logs
chmod 755 logs
```

### Lỗi 2: Database columns thiếu
```
→ Truy cập: http://your-domain.com/database/migrations/run_v2_migration.php
→ Click "Run Migration"
```

### Lỗi 3: Method không tồn tại
```
→ Check file: includes/User.php
→ Check file: includes/Match.php
→ Check file: includes/Room.php
→ Có thể cần pull code mới hoặc update từ commit 5ca0726
```

### Lỗi 4: Không kết nối database
```
→ Check file: config/database.php
→ Kiểm tra username, password, database name
→ Test: mysql -u username -p database_name
```

---

## 📊 Flow Debug

```
1. Chạy simple_test.php
   ↓
2. Xem có ❌ không?
   ↓ CÓ
3. Đọc error message
   ↓
4. Follow hướng dẫn fix
   ↓
5. Chạy lại simple_test.php
   ↓
6. Tất cả ✅? → Test swipe.php
   ↓
7. Vẫn lỗi? → Xem logs/swipe_debug.log
```

---

## 🎯 Checklist

Debug theo thứ tự:

1. [ ] Chạy `simple_test.php` - xem overview
2. [ ] Fix các ❌ về database columns
3. [ ] Fix các ❌ về tables
4. [ ] Fix các ❌ về methods
5. [ ] Tạo thư mục `logs/` với quyền 755
6. [ ] Test `swipe.php`
7. [ ] Xem `logs/swipe_debug.log` nếu vẫn lỗi
8. [ ] Đọc `MIGRATION_GUIDE.md` nếu cần chi tiết

---

## 📞 Các File Hỗ Trợ

- **MIGRATION_GUIDE.md** - Hướng dẫn migrate đầy đủ (tiếng Việt)
- **DEBUG_INSTRUCTIONS.md** - Chi tiết debug từng step
- **DEBUG_README.md** - File này (tóm tắt)

---

## ⚡ TL;DR (Too Long Didn't Read)

```bash
# 1. Test nhanh
curl http://your-domain.com/simple_test.php

# 2. Nếu có lỗi database
curl http://your-domain.com/database/migrations/run_v2_migration.php

# 3. Tạo logs
mkdir logs && chmod 755 logs

# 4. Test lại
curl http://your-domain.com/pages/swipe.php

# 5. Xem log
tail -f logs/swipe_debug.log
```

Done! 🎉
