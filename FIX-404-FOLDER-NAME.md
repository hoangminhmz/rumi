# 🔧 RUMI - Fix 404 với Tên Folder Khác

## Vấn Đề
Folder tên `rumi` bị block bởi server config/ModSecurity.
Folder `rumi-test` hoặc tên khác hoạt động bình thường.

## Giải Pháp

### Option 1: Dùng `rumi-test`

Nếu folder của bạn là `/www/rumi-test/`, update 2 files:

#### File 1: `config/constants.php`

Tìm dòng:
```php
define('BASE_URL', 'http://localhost/rumi');
```

Sửa thành:
```php
define('BASE_URL', 'https://hoangminhmz.com/rumi-test');
```

#### File 2: `config/zalo.php`

Tìm dòng:
```php
define('ZALO_CALLBACK_URL', 'http://yourdomain.com/pages/zalo-callback.php');
```

Sửa thành:
```php
define('ZALO_CALLBACK_URL', 'https://hoangminhmz.com/rumi-test/pages/zalo-callback.php');
```

### Option 2: Dùng Tên Khác (VD: `app`)

Nếu rename folder thành `app`:

**File 1: `config/constants.php`**
```php
define('BASE_URL', 'https://hoangminhmz.com/app');
```

**File 2: `config/zalo.php`**
```php
define('ZALO_CALLBACK_URL', 'https://hoangminhmz.com/app/pages/zalo-callback.php');
```

## Test URLs

Sau khi update config, test:

- Landing: `https://hoangminhmz.com/[folder-name]/`
- Debug: `https://hoangminhmz.com/[folder-name]/debug.php`
- Test: `https://hoangminhmz.com/[folder-name]/test.php`

## Zalo Callback

Nhớ update trong Zalo Developers Console:
- OAuth Callback URL: `https://hoangminhmz.com/[folder-name]/pages/zalo-callback.php`

## Các Tên Folder Gợi Ý

Nếu muốn đổi tên khác, thử:
- `app` ← Ngắn gọn
- `roommate` ← Mô tả rõ
- `matching` ← Professional
- `rumi-app` ← Giữ brand
- `home` ← Đơn giản

Test từng tên xem cái nào không bị block!
