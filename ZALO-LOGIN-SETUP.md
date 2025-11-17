# Hướng dẫn cấu hình Zalo Login cho RUMI

## Bước 1: Đăng ký Zalo App

1. Truy cập [Zalo Developer Portal](https://developers.zalo.me/)
2. Đăng nhập bằng tài khoản Zalo của bạn
3. Nhấp vào **"Tạo ứng dụng"** hoặc **"My Apps"** > **"Create App"**

## Bước 2: Tạo App mới

1. Chọn loại app: **"Web App"** hoặc **"Mobile App"**
2. Điền thông tin app:
   - **Tên ứng dụng**: RUMI - Tìm bạn cùng phòng
   - **Mô tả**: Ứng dụng tìm kiếm bạn cùng phòng và phòng trọ
   - **Category**: Social Networking
3. Upload logo cho app (tùy chọn)
4. Nhấn **"Tạo"** hoặc **"Create"**

## Bước 3: Cấu hình OAuth Settings

Sau khi tạo app, vào **Settings** > **OAuth Settings**:

1. **Redirect URIs** (Callback URLs):
   ```
   https://hoangminhmz.com/rummi/pages/zalo-callback.php
   ```

2. **Permissions** cần thiết:
   - ✅ `id` - Lấy Zalo ID
   - ✅ `name` - Lấy tên người dùng
   - ✅ `picture` - Lấy ảnh đại diện

3. Nhấn **"Save"** để lưu cấu hình

## Bước 4: Lấy App Credentials

Vào tab **"App Information"** hoặc **"Dashboard"**:

1. **App ID**: Copy giá trị này (dạng số, ví dụ: `1234567890123456789`)
2. **App Secret**: Click **"Show"** hoặc **"Generate"** để lấy secret key

⚠️ **LƯU Ý**: Giữ **App Secret** bí mật, không chia sẻ công khai!

## Bước 5: Cập nhật vào Code

Mở file `config/zalo.php` và thay đổi:

```php
// Thay YOUR_ZALO_APP_ID bằng App ID thật
define('ZALO_APP_ID', '1234567890123456789');

// Thay YOUR_ZALO_APP_SECRET bằng App Secret thật
define('ZALO_APP_SECRET', 'your_actual_app_secret_here');

// Kiểm tra callback URL đúng với domain của bạn
define('ZALO_CALLBACK_URL', 'https://hoangminhmz.com/rummi/pages/zalo-callback.php');
```

### Ví dụ cấu hình:

```php
<?php
// ĐÚNG ✅
define('ZALO_APP_ID', '3849502749285739485');
define('ZALO_APP_SECRET', 'Xkd8fj3KDf93kFjD9kfJ');
define('ZALO_CALLBACK_URL', 'https://hoangminhmz.com/rummi/pages/zalo-callback.php');

// SAI ❌ - Chưa thay đổi giá trị mặc định
define('ZALO_APP_ID', 'YOUR_ZALO_APP_ID');
define('ZALO_APP_SECRET', 'YOUR_ZALO_APP_SECRET');
```

## Bước 6: Test Zalo Login

1. Truy cập trang login: https://hoangminhmz.com/rummi/pages/login.php
2. Nhấn nút **"Đăng nhập với Zalo"**
3. Nếu cấu hình đúng, bạn sẽ được redirect sang trang đăng nhập Zalo
4. Đăng nhập và cấp quyền cho app
5. Sau đó sẽ được redirect về RUMI với thông tin đăng nhập thành công

## Troubleshooting

### Vấn đề: Nút đăng nhập không hoạt động
- ✅ Kiểm tra đã thay `YOUR_ZALO_APP_ID` và `YOUR_ZALO_APP_SECRET` chưa
- ✅ Kiểm tra App ID và Secret có đúng không (không có khoảng trắng thừa)

### Vấn đề: "Invalid redirect_uri"
- ✅ Kiểm tra Callback URL trong Zalo Developer phải khớp 100% với `ZALO_CALLBACK_URL` trong code
- ✅ Bao gồm cả protocol (https://) và path đầy đủ

### Vấn đề: "Invalid app_id"
- ✅ App ID phải là số, không có ký tự đặc biệt
- ✅ Kiểm tra đã copy đúng App ID từ Zalo Developer Portal

### Vấn đề: "Unauthorized"
- ✅ App Secret có thể đã thay đổi, generate lại trong Zalo Developer
- ✅ Kiểm tra app status phải là **"Active"** không phải **"Draft"**

## Bảo mật

1. **KHÔNG commit** file `config/zalo.php` với credentials thật vào Git
2. Nên tạo file `.env` để lưu credentials:
   ```
   ZALO_APP_ID=your_app_id
   ZALO_APP_SECRET=your_app_secret
   ```
3. Load từ `.env` trong `config/zalo.php`:
   ```php
   define('ZALO_APP_ID', getenv('ZALO_APP_ID') ?: 'YOUR_ZALO_APP_ID');
   define('ZALO_APP_SECRET', getenv('ZALO_APP_SECRET') ?: 'YOUR_ZALO_APP_SECRET');
   ```

## Tài liệu tham khảo

- [Zalo Login SDK Documentation](https://developers.zalo.me/docs/sdk/login-sdk/)
- [Zalo OAuth 2.0 Guide](https://developers.zalo.me/docs/api/social-api/tai-lieu/xac-thuc-va-uy-quyen-oauth-post-28)
- [Zalo API Reference](https://developers.zalo.me/docs/api/)

---

**Cần hỗ trợ thêm?** Tạo issue tại repository hoặc liên hệ team dev.
