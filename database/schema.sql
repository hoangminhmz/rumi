-- ============================================
-- RUMI Database Schema
-- Roommate Matching Web App
-- ============================================

-- Set charset
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ============================================
-- Table: districts
-- Lưu danh sách quận/huyện cho các thành phố
-- ============================================
CREATE TABLE IF NOT EXISTS districts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    city_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_city (city_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: users
-- Lưu thông tin user đăng nhập qua Zalo
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    zalo_id VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    gender ENUM('male', 'female', 'other') NOT NULL,
    age INT NOT NULL,
    district_id INT NOT NULL,
    bio TEXT,
    avatar VARCHAR(255),
    preferences JSON,
    -- JSON structure: {"budget_min": 1000000, "budget_max": 5000000, "cleanliness": 5, "noise_tolerance": 3, "smoking": false, "pets": true}
    search_mode ENUM('find_roommate', 'find_room') DEFAULT 'find_roommate',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_district (district_id),
    INDEX idx_gender_age (gender, age),
    INDEX idx_active (is_active),
    INDEX idx_search_mode (search_mode),
    FOREIGN KEY (district_id) REFERENCES districts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: rooms
-- Lưu thông tin phòng trọ do chủ nhà đăng
-- ============================================
CREATE TABLE IF NOT EXISTS rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    owner_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    price INT NOT NULL,
    area FLOAT,
    district_id INT NOT NULL,
    address TEXT NOT NULL,
    images JSON,
    -- JSON structure: ["image1.jpg", "image2.jpg", "image3.jpg"]
    amenities JSON,
    -- JSON structure: {"wifi": true, "ac": true, "kitchen": true, "parking": false, "laundry": true}
    status ENUM('pending_payment', 'active', 'inactive', 'rented') DEFAULT 'pending_payment',
    payment_status ENUM('unpaid', 'paid') DEFAULT 'unpaid',
    payment_id VARCHAR(100),
    payment_date TIMESTAMP NULL,
    views_count INT DEFAULT 0,
    likes_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expired_at TIMESTAMP NULL,

    INDEX idx_owner (owner_id),
    INDEX idx_district_price (district_id, price),
    INDEX idx_status (status),
    INDEX idx_created (created_at),
    FOREIGN KEY (owner_id) REFERENCES users(id),
    FOREIGN KEY (district_id) REFERENCES districts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: user_swipes
-- Lưu lịch sử swipe giữa user với user
-- ============================================
CREATE TABLE IF NOT EXISTS user_swipes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    target_user_id INT NOT NULL,
    is_like BOOLEAN NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_swipe (user_id, target_user_id),
    INDEX idx_user (user_id),
    INDEX idx_target (target_user_id),
    INDEX idx_like (is_like),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (target_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: room_swipes
-- Lưu lịch sử swipe giữa user với room
-- ============================================
CREATE TABLE IF NOT EXISTS room_swipes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    is_like BOOLEAN NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_swipe (user_id, room_id),
    INDEX idx_user (user_id),
    INDEX idx_room (room_id),
    INDEX idx_like (is_like),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table: matches
-- Lưu các cặp match thành công
-- ============================================
CREATE TABLE IF NOT EXISTS matches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    room_id INT NULL,
    status ENUM('pending', 'connected', 'disconnected') DEFAULT 'pending',
    matched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    connected_at TIMESTAMP NULL,

    UNIQUE KEY unique_match (user1_id, user2_id, room_id),
    INDEX idx_user1 (user1_id),
    INDEX idx_user2 (user2_id),
    INDEX idx_room (room_id),
    INDEX idx_status (status),
    INDEX idx_matched_date (matched_at),
    FOREIGN KEY (user1_id) REFERENCES users(id),
    FOREIGN KEY (user2_id) REFERENCES users(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Insert sample districts data
-- ============================================
INSERT INTO districts (name, city_name) VALUES
-- Hà Nội
('Ba Đình', 'Hà Nội'),
('Hoàn Kiếm', 'Hà Nội'),
('Tây Hồ', 'Hà Nội'),
('Long Biên', 'Hà Nội'),
('Cầu Giấy', 'Hà Nội'),
('Đống Đa', 'Hà Nội'),
('Hai Bà Trưng', 'Hà Nội'),
('Hoàng Mai', 'Hà Nội'),
('Thanh Xuân', 'Hà Nội'),
('Nam Từ Liêm', 'Hà Nội'),
('Bắc Từ Liêm', 'Hà Nội'),
('Hà Đông', 'Hà Nội'),

-- TP.HCM
('Quận 1', 'TP.HCM'),
('Quận 2', 'TP.HCM'),
('Quận 3', 'TP.HCM'),
('Quận 4', 'TP.HCM'),
('Quận 5', 'TP.HCM'),
('Quận 6', 'TP.HCM'),
('Quận 7', 'TP.HCM'),
('Quận 8', 'TP.HCM'),
('Quận 9', 'TP.HCM'),
('Quận 10', 'TP.HCM'),
('Quận 11', 'TP.HCM'),
('Quận 12', 'TP.HCM'),
('Bình Thạnh', 'TP.HCM'),
('Gò Vấp', 'TP.HCM'),
('Phú Nhuận', 'TP.HCM'),
('Tân Bình', 'TP.HCM'),
('Tân Phú', 'TP.HCM'),
('Thủ Đức', 'TP.HCM'),

-- Đà Nẵng
('Hải Châu', 'Đà Nẵng'),
('Thanh Khê', 'Đà Nẵng'),
('Sơn Trà', 'Đà Nẵng'),
('Ngũ Hành Sơn', 'Đà Nẵng'),
('Liên Chiểu', 'Đà Nẵng'),
('Cẩm Lệ', 'Đà Nẵng');
