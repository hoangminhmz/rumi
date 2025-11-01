# 🎯 RUMI - Hướng Dẫn Setup qua cPanel

## Bước 1️⃣: Upload Code lên Server

### Cách 1: Upload qua cPanel File Manager (Khuyến nghị)

1. **Login vào cPanel** (https://hoangminhmz.com:2083 hoặc URL cPanel của bạn)

2. **Mở File Manager**
   - Tìm icon "File Manager" trong cPanel
   - Click vào để mở

3. **Navigate đến thư mục web root**
   - Thường là: `/public_html/` hoặc `/home/[username]/public_html/`
   - Nếu đã có folder `rumi`, xóa đi và tạo lại

4. **Tạo folder `rumi`**
   - Click "New Folder" ở toolbar
   - Tên folder: `rumi`
   - Click "Create New Folder"

5. **Upload files**

   **Option A - Upload từ máy tính:**
   - Vào folder `rumi` vừa tạo
   - Click "Upload" trong toolbar
   - Chọn tất cả files/folders từ project RUMI
   - Upload (có thể mất vài phút)

   **Option B - Upload file ZIP (Nhanh hơn):**
   - Zip toàn bộ project RUMI trên máy tính
   - Upload file .zip vào folder `rumi`
   - Click chuột phải vào file .zip → "Extract"
   - Sau khi extract xong, xóa file .zip

6. **Kiểm tra cấu trúc**
   - Trong folder `rumi` phải có:
     - ✅ index.php
     - ✅ test.php
     - ✅ phpinfo.php
     - ✅ .htaccess
     - ✅ Folders: api, assets, components, config, includes, pages

### Cách 2: Upload qua Git (Nếu cPanel có Git Version Control)

1. Trong cPanel File Manager, vào `/public_html/`
2. Tìm "Git Version Control" trong cPanel
3. Click "Create"
4. Repository URL: `https://github.com/hoangminhmz/rumi.git`
5. Branch: `claude/rumi-roommate-matching-app-011CUgQZtSZAaGAhB3MRrRiN`
6. Repository Path: `/public_html/rumi`
7. Click "Create"

---

## Bước 2️⃣: Fix File Permissions

### Trong cPanel File Manager:

1. **Vào folder `rumi`**

2. **Select All files/folders** (Ctrl+A hoặc checkbox "Select All")

3. **Click "Permissions" trong toolbar** (icon cái ổ khóa)

4. **Set Permissions như sau:**

   **Cho FOLDERS:**
   - ✅ Read (4)
   - ✅ Write (2)
   - ✅ Execute (1)
   - **= 755** (rwxr-xr-x)

   **Cho FILES:**
   - ✅ Read (4)
   - ✅ Write (2)
   - ❌ Execute (0)
   - **= 644** (rw-r--r--)

5. **Tick checkbox "Apply to subdirectories"**

6. **Click "Save"**

### Permissions Quan Trọng:

| Path | Permission |
|------|------------|
| `/rumi/` | 755 |
| `/rumi/index.php` | 644 |
| `/rumi/.htaccess` | 644 |
| `/rumi/config/` | 755 |
| `/rumi/assets/images/uploads/` | 755 (để PHP có thể ghi file) |

---

## Bước 3️⃣: Setup Database

### Trong cPanel MySQL Databases:

1. **Tạo Database:**
   - Vào "MySQL Databases" trong cPanel
   - Phần "Create New Database"
   - Database Name: `rumi_db` (hoặc tên bạn muốn)
   - Click "Create Database"
   - **GHI LẠI tên database đầy đủ** (VD: `username_rumi_db`)

2. **Tạo User:**
   - Phần "MySQL Users"
   - Username: `rumi_user`
   - Password: Tạo password mạnh (dùng Password Generator)
   - Click "Create User"
   - **GHI LẠI username và password**

3. **Add User to Database:**
   - Phần "Add User To Database"
   - Chọn User vừa tạo
   - Chọn Database vừa tạo
   - Click "Add"
   - Tick "ALL PRIVILEGES"
   - Click "Make Changes"

4. **Import Database Schema:**
   - Vào "phpMyAdmin" trong cPanel
   - Chọn database `username_rumi_db`
   - Click tab "Import"
   - Choose file: `database/schema.sql` (từ project)
   - Click "Go"
   - ✅ Phải thấy message "Import has been successfully finished"

---

## Bước 4️⃣: Update Config Files

### Trong cPanel File Manager:

#### A. Update `config/database.php`:

1. Navigate: `/public_html/rumi/config/`
2. Click chuột phải vào `database.php` → "Edit"
3. Click "Edit" trong popup
4. Sửa các dòng sau:

```php
define('DB_HOST', 'localhost');  // Giữ nguyên
define('DB_NAME', 'username_rumi_db');  // ← Thay bằng tên database của bạn
define('DB_USER', 'username_rumi_user'); // ← Thay bằng username của bạn
define('DB_PASS', 'YOUR_PASSWORD_HERE'); // ← Thay bằng password của bạn
```

5. Click "Save Changes" (Ctrl+S)
6. Click "Close"

#### B. Update `config/constants.php`:

1. Edit file `config/constants.php`
2. Sửa dòng:

```php
define('BASE_URL', 'https://hoangminhmz.com/rumi');
// ↑ Đổi http thành https nếu domain có SSL
```

3. Save

#### C. Update `config/zalo.php`:

1. Edit file `config/zalo.php`
2. Điền thông tin từ [Zalo Developers](https://developers.zalo.me):

```php
define('ZALO_APP_ID', 'YOUR_ZALO_APP_ID');
define('ZALO_APP_SECRET', 'YOUR_ZALO_APP_SECRET');
define('ZALO_CALLBACK_URL', 'https://hoangminhmz.com/rumi/pages/zalo-callback.php');
```

3. Save

---

## Bước 5️⃣: Test Installation

### Test theo thứ tự:

1. **Test PHP:**
   - Truy cập: `https://hoangminhmz.com/rumi/test.php`
   - ✅ Phải thấy trang "RUMI Test - PHP Hoạt động OK!"
   - Check xem tất cả files đều "✅ Readable"

2. **Test PHP Info:**
   - Truy cập: `https://hoangminhmz.com/rumi/phpinfo.php`
   - ✅ Phải thấy trang PHP Info đầy đủ
   - Check PHP version >= 8.1

3. **Test Index:**
   - Truy cập: `https://hoangminhmz.com/rumi/`
   - ✅ Phải thấy RUMI landing page với logo mint
   - Nếu vẫn 403 → Đọc phần Troubleshooting bên dưới

4. **Test Index trực tiếp:**
   - Truy cập: `https://hoangminhmz.com/rumi/index.php`
   - Nếu works → Vấn đề với `.htaccess` DirectoryIndex

---

## 🔧 Troubleshooting - Fix Lỗi 403

### Nếu vẫn bị lỗi 403:

#### Fix 1: Tạm disable .htaccess

1. Trong File Manager, rename `.htaccess` → `.htaccess.disabled`
2. Refresh browser
3. Nếu works → Vấn đề nằm ở `.htaccess`

#### Fix 2: Tạo .htaccess đơn giản

1. Xóa `.htaccess` cũ
2. Tạo file mới `.htaccess` với nội dung:

```apache
DirectoryIndex index.php index.html
Options -Indexes

<FilesMatch "\.(php|html|css|js|png|jpg)$">
    Require all granted
</FilesMatch>
```

3. Save và test lại

#### Fix 3: Check PHP Version

1. Trong cPanel, tìm "Select PHP Version" hoặc "MultiPHP Manager"
2. Đảm bảo chọn **PHP 8.1** hoặc cao hơn
3. Enable extensions: `pdo`, `pdo_mysql`, `curl`, `gd`

#### Fix 4: Check Error Logs

1. Trong cPanel, vào "Errors"
2. Xem error log gần nhất
3. Copy error message và Google hoặc hỏi tôi

---

## Bước 6️⃣: Setup Zalo Login

1. **Vào Zalo Developers:**
   - Truy cập: https://developers.zalo.me
   - Login với Zalo account

2. **Tạo hoặc chọn App:**
   - Tạo app mới hoặc chọn app có sẵn
   - Lấy **App ID** và **App Secret**

3. **Thêm Callback URL:**
   - Vào phần "OAuth Settings"
   - Thêm: `https://hoangminhmz.com/rumi/pages/zalo-callback.php`
   - Save

4. **Update config/zalo.php** (như đã làm ở Bước 4)

---

## Bước 7️⃣: Security - XÓA Test Files

**SAU KHI SETUP XONG, PHẢI XÓA:**

1. `/rumi/test.php` ← Xóa
2. `/rumi/phpinfo.php` ← Xóa (QUAN TRỌNG - lộ thông tin server)

Trong File Manager:
- Click chuột phải vào file → "Delete"

---

## ✅ Checklist Hoàn Thành

- [ ] Upload code lên `/public_html/rumi/`
- [ ] Set file permissions (755 cho folders, 644 cho files)
- [ ] Tạo database và import `schema.sql`
- [ ] Update `config/database.php` với DB credentials
- [ ] Update `config/constants.php` với BASE_URL
- [ ] Update `config/zalo.php` với Zalo credentials
- [ ] Test: `https://hoangminhmz.com/rumi/test.php` works
- [ ] Test: `https://hoangminhmz.com/rumi/` shows landing page
- [ ] Setup Zalo callback URL
- [ ] Test login flow
- [ ] **XÓA test.php và phpinfo.php**

---

## 📞 Cần Help?

Nếu gặp lỗi:

1. Screenshot error
2. Copy error message từ cPanel Error Log
3. Cho tôi biết:
   - Link bạn đang truy cập
   - Lỗi hiển thị
   - PHP version đang dùng
   - Đã làm đến bước nào

---

**🏠 RUMI - Roommate Matching App**
