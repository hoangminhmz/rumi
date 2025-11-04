-- ============================================
-- Migration: Add Geolocation Support
-- Date: 2025-11-04
-- Description: Add latitude/longitude for rooms and districts
--              to enable distance-based filtering
-- ============================================

-- Add geolocation columns to rooms table
ALTER TABLE rooms
ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER address,
ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude,
ADD INDEX idx_location (latitude, longitude);

-- Add geolocation columns to districts table (center coordinates)
ALTER TABLE districts
ADD COLUMN latitude DECIMAL(10, 8) NULL,
ADD COLUMN longitude DECIMAL(11, 8) NULL;

-- Update districts with center coordinates for major Vietnamese cities
-- Hà Nội districts
UPDATE districts SET latitude = 21.0341, longitude = 105.8195 WHERE name = 'Ba Đình' AND city_name = 'Hà Nội';
UPDATE districts SET latitude = 21.0285, longitude = 105.8542 WHERE name = 'Hoàn Kiếm' AND city_name = 'Hà Nội';
UPDATE districts SET latitude = 21.0583, longitude = 105.8231 WHERE name = 'Tây Hồ' AND city_name = 'Hà Nội';
UPDATE districts SET latitude = 21.0453, longitude = 105.8860 WHERE name = 'Long Biên' AND city_name = 'Hà Nội';
UPDATE districts SET latitude = 21.0333, longitude = 105.7944 WHERE name = 'Cầu Giấy' AND city_name = 'Hà Nội';
UPDATE districts SET latitude = 21.0188, longitude = 105.8265 WHERE name = 'Đống Đa' AND city_name = 'Hà Nội';
UPDATE districts SET latitude = 21.0069, longitude = 105.8478 WHERE name = 'Hai Bà Trưng' AND city_name = 'Hà Nội';
UPDATE districts SET latitude = 20.9817, longitude = 105.8468 WHERE name = 'Hoàng Mai' AND city_name = 'Hà Nội';
UPDATE districts SET latitude = 20.9992, longitude = 105.8067 WHERE name = 'Thanh Xuân' AND city_name = 'Hà Nội';
UPDATE districts SET latitude = 21.0333, longitude = 105.7573 WHERE name = 'Nam Từ Liêm' AND city_name = 'Hà Nội';
UPDATE districts SET latitude = 21.0689, longitude = 105.7378 WHERE name = 'Bắc Từ Liêm' AND city_name = 'Hà Nội';
UPDATE districts SET latitude = 20.9719, longitude = 105.7742 WHERE name = 'Hà Đông' AND city_name = 'Hà Nội';

-- TP.HCM districts
UPDATE districts SET latitude = 10.7756, longitude = 106.7019 WHERE name = 'Quận 1' AND city_name = 'TP.HCM';
UPDATE districts SET latitude = 10.7789, longitude = 106.7378 WHERE name = 'Quận 2' AND city_name = 'TP.HCM';
UPDATE districts SET latitude = 10.7820, longitude = 106.6897 WHERE name = 'Quận 3' AND city_name = 'TP.HCM';
UPDATE districts SET latitude = 10.7575, longitude = 106.7037 WHERE name = 'Quận 4' AND city_name = 'TP.HCM';
UPDATE districts SET latitude = 10.7557, longitude = 106.6672 WHERE name = 'Quận 5' AND city_name = 'TP.HCM';
UPDATE districts SET latitude = 10.7486, longitude = 106.6350 WHERE name = 'Quận 6' AND city_name = 'TP.HCM';
UPDATE districts SET latitude = 10.7332, longitude = 106.7196 WHERE name = 'Quận 7' AND city_name = 'TP.HCM';
UPDATE districts SET latitude = 10.7382, longitude = 106.6767 WHERE name = 'Quận 8' AND city_name = 'TP.HCM';
UPDATE districts SET latitude = 10.8226, longitude = 106.7881 WHERE name = 'Quận 9' AND city_name = 'TP.HCM';
UPDATE districts SET latitude = 10.7727, longitude = 106.6670 WHERE name = 'Quận 10' AND city_name = 'TP.HCM';
UPDATE districts SET latitude = 10.7626, longitude = 106.6503 WHERE name = 'Quận 11' AND city_name = 'TP.HCM';
UPDATE districts SET latitude = 10.8632, longitude = 106.6777 WHERE name = 'Quận 12' AND city_name = 'TP.HCM';
UPDATE districts SET latitude = 10.8011, longitude = 106.7100 WHERE name = 'Bình Thạnh' AND city_name = 'TP.HCM';
UPDATE districts SET latitude = 10.8376, longitude = 106.6762 WHERE name = 'Gò Vấp' AND city_name = 'TP.HCM';
UPDATE districts SET latitude = 10.7980, longitude = 106.6825 WHERE name = 'Phú Nhuận' AND city_name = 'TP.HCM';
UPDATE districts SET latitude = 10.8008, longitude = 106.6530 WHERE name = 'Tân Bình' AND city_name = 'TP.HCM';
UPDATE districts SET latitude = 10.7780, longitude = 106.6291 WHERE name = 'Tân Phú' AND city_name = 'TP.HCM';
UPDATE districts SET latitude = 10.8507, longitude = 106.7717 WHERE name = 'Thủ Đức' AND city_name = 'TP.HCM';

-- Đà Nẵng districts
UPDATE districts SET latitude = 16.0544, longitude = 108.2022 WHERE name = 'Hải Châu' AND city_name = 'Đà Nẵng';
UPDATE districts SET latitude = 16.0735, longitude = 108.1903 WHERE name = 'Thanh Khê' AND city_name = 'Đà Nẵng';
UPDATE districts SET latitude = 16.0783, longitude = 108.2383 WHERE name = 'Sơn Trà' AND city_name = 'Đà Nẵng';
UPDATE districts SET latitude = 16.0011, longitude = 108.2644 WHERE name = 'Ngũ Hành Sơn' AND city_name = 'Đà Nẵng';
UPDATE districts SET latitude = 16.0755, longitude = 108.1519 WHERE name = 'Liên Chiểu' AND city_name = 'Đà Nẵng';
UPDATE districts SET latitude = 16.0244, longitude = 108.1864 WHERE name = 'Cẩm Lệ' AND city_name = 'Đà Nẵng';
