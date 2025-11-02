# 📊 HƯỚNG DẪN IMPORT DUMMY DATA

## Bước 1: Download file dummy-data.sql

1. Vào GitHub repo của bạn
2. Vào folder `database/`
3. Click vào file `dummy-data.sql`
4. Click nút **"Raw"** hoặc **"Download"**
5. Save file về máy

## Bước 2: Vào phpMyAdmin trong cPanel

1. Login vào **cPanel** tại https://hoangminhmz.com:2083
2. Tìm mục **"Databases"** (Cơ sở dữ liệu)
3. Click vào **"phpMyAdmin"**
4. Cửa sổ phpMyAdmin sẽ mở ra

## Bước 3: Chọn Database

1. Bên trái, click vào database của RUMI (tên database bạn đã tạo)
2. Ví dụ: `hoangmi5_rumi` hoặc tên database bạn đã đặt

## Bước 4: Import File SQL

1. Click tab **"Import"** (Nhập) ở menu trên
2. Click nút **"Choose File"** (Chọn tập tin)
3. Chọn file `dummy-data.sql` vừa download
4. Kéo xuống dưới cùng
5. Click nút **"Go"** (Thực hiện)

## Bước 5: Kiểm tra kết quả

Sau khi import thành công, bạn sẽ thấy thông báo màu xanh:
```
Import has been successfully finished
```

### Kiểm tra dữ liệu đã import:

1. Click vào bảng **`users`** → Xem → Sẽ thấy 20 users
2. Click vào bảng **`rooms`** → Xem → Sẽ thấy 15 rooms
3. Click vào bảng **`matches`** → Xem → Sẽ thấy 3 matches
4. Click vào bảng **`user_swipes`** → Xem → Sẽ thấy lịch sử swipe

## Bước 6: Test App với Dummy Data

### Cách 1: Dùng Bypass Login (Không cần Zalo)

1. Vào: https://hoangminhmz.com/rummi/pages/login-bypass.php
2. Click nút **"Create & Login Test User"**
3. Sẽ tự động tạo user và login
4. Click **"Go to Profile Setup"**
5. Điền thông tin profile
6. Bắt đầu test!

### Cách 2: Login bằng User có sẵn (Dùng Database Editing)

1. Vào phpMyAdmin → bảng `users`
2. Chọn 1 user bất kỳ (ví dụ user có id = 1)
3. Copy `zalo_id` của user đó
4. Sửa trong code login-bypass.php dòng 32:
   ```php
   // Thay 'test_user_123' bằng zalo_id của user bạn muốn login
   $stmt->execute(['dummy_user_1']);
   ```

## 🎯 Các tính năng có thể test:

### 1. Swipe Users (Tìm bạn cùng phòng)
- Vào: https://hoangminhmz.com/rummi/pages/swipe.php
- Swipe trái/phải để like/dislike users
- Xem chi tiết profile từng user
- Check compatibility score

### 2. Swipe Rooms (Tìm phòng)
- Trong profile → Chọn "Search Mode: Find Room"
- Vào swipe.php → Sẽ thấy rooms thay vì users
- Swipe các phòng trọ
- Xem thông tin phòng, giá, địa điểm

### 3. Matches (Người đã match)
- Vào: https://hoangminhmz.com/rummi/pages/matches.php
- Xem danh sách matches
- Xem thống kê (Total Likes, Matches, Match Rate)
- Click vào match để xem chi tiết
- Reveal contact (Zalo) để chat

### 4. Post Room (Đăng phòng cho thuê)
- Vào: https://hoangminhmz.com/rummi/pages/post-room.php
- Điền thông tin phòng
- Upload ảnh (nếu đã setup)
- Đăng phòng mới

### 5. Profile
- Vào: https://hoangminhmz.com/rummi/pages/profile.php
- Xem profile cá nhân
- Edit profile
- Xem "My Rooms" (phòng đã đăng)
- Switch search mode (Find Roommate ↔ Find Room)

## 📊 Dummy Data đã tạo:

### Users (20 người):
- **Dummy User 1-7**: Sinh viên/Freelancer ở Hà Nội
- **Dummy User 8-14**: Nhân viên văn phòng ở TP.HCM
- **Dummy User 15-20**: Các nghề khác ở Đà Nẵng
- Độ tuổi: 21-32
- Budget: 1.5M - 6M VND
- Preferences đa dạng (cleanliness, noise, pets, smoking)

### Rooms (15 phòng):
- **Hanoi**: 5 phòng ở quận Ba Đình, Cầu Giấy, Hai Bà Trưng
- **HCMC**: 7 phòng ở quận 1, Bình Thạnh, Phú Nhuận
- **Danang**: 3 phòng ở Hải Châu, Thanh Khê
- Giá: 1.8M - 6.5M VND
- Diện tích: 20-40 m²
- Tiện ích: Wifi, Điều hòa, Bếp, Máy giặt, Chỗ để xe...

### Matches (3 cặp):
- Dummy User 1 ↔ Dummy User 2
- Dummy User 1 ↔ Dummy User 4
- Dummy User 1 ↔ Dummy User 5

### Swipe History:
- Nhiều lượt swipe giữa các users
- Một số room swipes

## ⚠️ LƯU Ý:

1. **Login Bypass chỉ để test** → XÓA file này khi production!
2. Nếu muốn test với user khác, edit `login-bypass.php`
3. Để production thật, cần setup **Zalo OAuth** (xem README.md)
4. Photos chưa có → Có thể thêm sau bằng cách:
   - Upload ảnh vào `assets/images/uploads/`
   - Update field `photos` trong bảng `rooms`
   - Update field `avatar` trong bảng `users`

## 🐛 Gặp lỗi?

### "Import failed" - File quá lớn
→ Tăng upload limit trong php.ini hoặc import từng phần

### "Duplicate entry" error
→ Database đã có data → Xóa tables và import lại
```sql
TRUNCATE TABLE user_swipes;
TRUNCATE TABLE room_swipes;
TRUNCATE TABLE matches;
DELETE FROM rooms;
DELETE FROM users WHERE zalo_id LIKE 'dummy_%';
```

### Không thấy data sau khi import
→ Check đúng database chưa? Reload phpMyAdmin

### Login bypass không hoạt động
→ Check database connection trong `config/database.php`

## ✅ Checklist sau khi import:

- [ ] Vào phpMyAdmin → Thấy 20 users
- [ ] Vào phpMyAdmin → Thấy 15 rooms
- [ ] Login bypass → Tạo được test user
- [ ] Swipe page → Thấy cards hiển thị
- [ ] Matches page → Xem được matches
- [ ] Profile → Xem được thông tin user
- [ ] Post room → Form hiển thị đầy đủ

---

🎉 **Chúc bạn test app vui vẻ!**

Nếu có lỗi gì, hãy screenshot và báo lại để mình fix nhé!
