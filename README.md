# 🏠 RUMI - Roommate Matching Web App

RUMI (Room + Me) là một web application giống Tinder để matching bạn cùng phòng tại Việt Nam. Người dùng có thể swipe để tìm người trước hoặc tìm phòng trước, khi match thành công sẽ chia sẻ thông tin liên hệ qua Zalo.

## ✨ Features

### MVP Phase 1 (Current)
- ✅ **Zalo Login Integration** - Đăng nhập nhanh với Zalo SDK
- ✅ **User Profile Management** - Tạo và quản lý profile với preferences
- ✅ **Dual Mode Matching** - Toggle giữa "Tìm người" và "Tìm phòng"
- ✅ **Swipe Interface** - Tinder-style card swipe với Hammer.js
- ✅ **Smart Matching Algorithm** - Scoring dựa trên location, age, preferences
- ✅ **Real-time Match Notifications** - Modal thông báo khi có match
- ✅ **Contact Reveal** - Chia sẻ số điện thoại khi match
- ✅ **Room Posting** - Chủ nhà đăng phòng (có listing fee)

### Upcoming Features
- 🔄 Advanced filters (price range, amenities)
- 🔄 Photo upload with compression
- 🔄 Owner dashboard with analytics
- 🔄 Email notifications
- 🔄 Admin panel
- 🔄 Payment gateway integration (Momo/VNPay)

## 🛠️ Tech Stack

- **Backend**: Core PHP 8.1+ với PDO (no framework)
- **Frontend**: Bootstrap 5 + Vanilla JavaScript + Hammer.js
- **Database**: MySQL 8.0
- **Authentication**: Zalo Login SDK
- **Design**: Airbnb-inspired, Mobile-first, Mint color palette

## 📋 Requirements

- PHP 8.1 hoặc cao hơn
- MySQL 8.0 hoặc cao hơn
- Apache/Nginx web server
- Composer (optional, hiện tại không dùng dependencies)
- Zalo App credentials (từ [Zalo Developers](https://developers.zalo.me/))

## 🚀 Installation

### 1. Clone Repository

```bash
git clone https://github.com/your-username/rumi.git
cd rumi
```

### 2. Database Setup

```bash
# Tạo database
mysql -u root -p
CREATE DATABASE rumi_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Import schema
mysql -u root -p rumi_db < database/schema.sql
```

### 3. Configuration

#### Database Config
Sửa file `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'rumi_db');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
```

#### Zalo SDK Config
1. Tạo app tại [Zalo Developers](https://developers.zalo.me/)
2. Lấy App ID và App Secret
3. Sửa file `config/zalo.php`:

```php
define('ZALO_APP_ID', 'your_zalo_app_id');
define('ZALO_APP_SECRET', 'your_zalo_app_secret');
define('ZALO_CALLBACK_URL', 'http://yourdomain.com/pages/zalo-callback.php');
```

#### Base URL
Sửa file `config/constants.php`:

```php
define('BASE_URL', 'http://yourdomain.com/rumi');
```

### 4. File Permissions

```bash
# Tạo upload directory và set permissions
mkdir -p assets/images/uploads
chmod 755 assets/images/uploads

# Tạo logs directory
mkdir -p logs
chmod 755 logs
```

### 5. Web Server Config

#### Apache (.htaccess)
File `.htaccess` đã được tạo sẵn ở root directory.

#### Nginx
Add vào nginx config:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
}
```

### 6. Test Installation

Truy cập `http://yourdomain.com/rumi` trong browser.

## 📁 Project Structure

```
/rumi/
├── api/                    # AJAX endpoints
│   ├── swipe-user.php
│   ├── swipe-room.php
│   └── get-cards.php
├── assets/
│   ├── css/
│   │   ├── style.css       # Main RUMI design system
│   │   └── components.css  # UI components
│   ├── js/
│   │   ├── app.js          # Main app logic
│   │   └── swipe.js        # Swipe functionality
│   ├── fonts/
│   └── images/
│       ├── logo/
│       ├── icons/
│       └── uploads/        # User/room uploads
├── components/             # Reusable PHP components
│   ├── header.php
│   ├── footer.php
│   ├── navigation.php
│   └── cards.php
├── config/
│   ├── database.php        # PDO connection
│   ├── zalo.php           # Zalo SDK config
│   └── constants.php      # App constants
├── database/
│   └── schema.sql         # Database schema
├── includes/
│   ├── User.php           # User model
│   ├── Room.php           # Room model
│   ├── Match.php          # Match model
│   └── functions.php      # Helper functions
├── pages/
│   ├── login.php
│   ├── profile-setup.php
│   ├── swipe.php
│   ├── matches.php
│   ├── post-room.php
│   └── profile.php
├── logs/                   # Application logs
├── .htaccess
├── .gitignore
├── index.php              # Landing page
└── README.md
```

## 🎨 Design System

RUMI sử dụng design system lấy cảm hứng từ Airbnb với mint color palette:

- **Primary**: #00D4AA (Mint Green)
- **Secondary**: #A7F3D0 (Soft Mint)
- **Accent**: #059669 (Deep Mint)
- **Typography**: Inter font family
- **Layout**: Mobile-first responsive design

## 🔐 Security Features

- ✅ CSRF protection trên tất cả forms
- ✅ Prepared statements (PDO) cho database queries
- ✅ Input validation và sanitization
- ✅ Secure session management
- ✅ File upload validation
- ✅ Rate limiting cho swipes (100/day)

## 💰 Business Model

- **FREE** cho người dùng thường (matching + swipe)
- **CHARGE** listing fee từ chủ nhà khi đăng phòng
  - Fee: 50,000 VND per listing
  - Duration: 30 days active
- **NO in-app chat** - dùng Zalo để communication

## 📊 Database Schema

### Main Tables
- `users` - User profiles và preferences
- `rooms` - Room listings
- `districts` - Quận/huyện (Hà Nội, TP.HCM, Đà Nẵng)
- `user_swipes` - User-to-user swipe history
- `room_swipes` - User-to-room swipe history
- `matches` - Successful matches

## 🧪 Testing

### Manual Testing Checklist
- [ ] Zalo login flow
- [ ] Profile creation/update
- [ ] Swipe left/right functionality
- [ ] Match creation on mutual like
- [ ] Contact reveal after match
- [ ] Room posting workflow
- [ ] Mobile responsive design

## 🚢 Deployment

### Production Checklist
- [ ] Update `config/database.php` với production credentials
- [ ] Update `config/constants.php` với production URL
- [ ] Set proper file permissions (755 directories, 644 files)
- [ ] Enable HTTPS
- [ ] Configure Zalo callback URL
- [ ] Setup payment gateway (Momo/VNPay)
- [ ] Configure email notifications
- [ ] Setup backup strategy
- [ ] Enable error logging

### Recommended Hosting
- DigitalOcean Droplet (Ubuntu 22.04)
- Vultr VPS
- AWS Lightsail

## 📝 Development Notes

### Code Style
- Comments tiếng Việt để dễ hiểu
- Simple, maintainable code over perfect code
- Mobile-first always
- Progressive enhancement

### Performance
- Database indexing trên user_id, district_id
- Image optimization on upload
- Lazy loading cho images
- Simple file-based caching

## 🤝 Contributing

1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License.

## 🙏 Credits

- Design inspiration: Airbnb
- Swipe library: Hammer.js
- Icons: Heroicons
- Font: Inter (Google Fonts)

## 📞 Support

For issues and questions:
- Open an issue on GitHub
- Email: support@rumi.vn (placeholder)

---

**RUMI** - Tìm bạn cùng phòng dễ dàng như swipe ❤️
