-- ============================================
-- RUMI - Dummy Data for Testing
-- Tạo users, rooms, swipes, matches để test
-- ============================================

-- Clear existing data (optional - comment out nếu muốn giữ data cũ)
-- DELETE FROM matches;
-- DELETE FROM room_swipes;
-- DELETE FROM user_swipes;
-- DELETE FROM rooms;
-- DELETE FROM users WHERE zalo_id LIKE 'dummy_%';

-- ============================================
-- INSERT DUMMY USERS (20 users)
-- ============================================

INSERT INTO users (zalo_id, name, phone, gender, age, district_id, bio, avatar, preferences, search_mode, is_active, created_at) VALUES

-- Users ở Hà Nội
('dummy_user_1', 'Nguyễn Văn An', '0901234001', 'male', 24, 5, 'Sinh viên IT, thích coding và cafe. Tìm bạn cùng phòng gần trường.', NULL, '{"budget_min": 2000000, "budget_max": 3000000, "cleanliness": 4, "noise_tolerance": 3, "smoking": false, "pets": false}', 'find_roommate', 1, NOW()),

('dummy_user_2', 'Trần Thị Bích', '0901234002', 'female', 22, 5, 'Làm việc tại startup, yêu thích yoga và nấu ăn. Cần người cùng phòng sạch sẽ.', NULL, '{"budget_min": 2500000, "budget_max": 4000000, "cleanliness": 5, "noise_tolerance": 2, "smoking": false, "pets": true}', 'find_roommate', 1, NOW()),

('dummy_user_3', 'Lê Minh Châu', '0901234003', 'female', 25, 6, 'Designer freelance, hay đi du lịch. Tìm bạn cùng phòng vui vẻ.', NULL, '{"budget_min": 3000000, "budget_max": 5000000, "cleanliness": 3, "noise_tolerance": 4, "smoking": false, "pets": true}', 'find_roommate', 1, NOW()),

('dummy_user_4', 'Phạm Quốc Dũng', '0901234004', 'male', 28, 6, 'Nhân viên văn phòng, thích gym và đọc sách. Tìm bạn ở ghép ổn định.', NULL, '{"budget_min": 2000000, "budget_max": 3500000, "cleanliness": 4, "noise_tolerance": 3, "smoking": false, "pets": false}', 'find_roommate', 1, NOW()),

('dummy_user_5', 'Hoàng Thu Hà', '0901234005', 'female', 23, 7, 'Giáo viên tiểu học, thích trồng cây và nhạc nhẹ. Tìm bạn nữ ở ghép.', NULL, '{"budget_min": 1500000, "budget_max": 2500000, "cleanliness": 5, "noise_tolerance": 2, "smoking": false, "pets": false}', 'find_roommate', 1, NOW()),

('dummy_user_6', 'Đỗ Văn Hùng', '0901234006', 'male', 26, 9, 'Developer, làm remote. Cần không gian yên tĩnh để code.', NULL, '{"budget_min": 3000000, "budget_max": 4500000, "cleanliness": 3, "noise_tolerance": 1, "smoking": false, "pets": false}', 'find_room', 1, NOW()),

('dummy_user_7', 'Vũ Thị Lan', '0901234007', 'female', 24, 9, 'Marketing, thích shopping và cafe. Tìm bạn cùng phòng hoà đồng.', NULL, '{"budget_min": 2500000, "budget_max": 4000000, "cleanliness": 4, "noise_tolerance": 4, "smoking": false, "pets": true}', 'find_roommate', 1, NOW()),

('dummy_user_8', 'Bùi Đức Mạnh', '0901234008', 'male', 27, 10, 'Kỹ sư xây dựng, hay đi công tác. Cần chỗ ở ổn định.', NULL, '{"budget_min": 2000000, "budget_max": 3000000, "cleanliness": 3, "noise_tolerance": 3, "smoking": false, "pets": false}', 'find_room', 1, NOW()),

('dummy_user_9', 'Ngô Thu Trang', '0901234009', 'female', 22, 11, 'Sinh viên y khoa, học tập nhiều. Cần không gian yên tĩnh.', NULL, '{"budget_min": 1500000, "budget_max": 2500000, "cleanliness": 5, "noise_tolerance": 1, "smoking": false, "pets": false}', 'find_roommate', 1, NOW()),

('dummy_user_10', 'Trịnh Văn Nam', '0901234010', 'male', 29, 12, 'Nhân viên ngân hàng, thích thể thao. Tìm bạn nam ở ghép.', NULL, '{"budget_min": 3000000, "budget_max": 5000000, "cleanliness": 4, "noise_tolerance": 3, "smoking": false, "pets": false}', 'find_roommate', 1, NOW()),

-- Users ở TP.HCM
('dummy_user_11', 'Phan Thị Mai', '0901234011', 'female', 25, 13, 'Content creator, hay quay video. Cần phòng đẹp có ánh sáng tốt.', NULL, '{"budget_min": 4000000, "budget_max": 6000000, "cleanliness": 4, "noise_tolerance": 4, "smoking": false, "pets": true}', 'find_room', 1, NOW()),

('dummy_user_12', 'Lý Quang Minh', '0901234012', 'male', 26, 15, 'Sales, hay đi gặp khách. Tìm bạn ở ghép gần Q1.', NULL, '{"budget_min": 3000000, "budget_max": 5000000, "cleanliness": 3, "noise_tolerance": 4, "smoking": false, "pets": false}', 'find_roommate', 1, NOW()),

('dummy_user_13', 'Đặng Thu Ngọc', '0901234013', 'female', 23, 17, 'Nhân viên spa, thích làm đẹp và yoga. Tìm bạn nữ dễ tính.', NULL, '{"budget_min": 2500000, "budget_max": 4000000, "cleanliness": 5, "noise_tolerance": 3, "smoking": false, "pets": false}', 'find_roommate', 1, NOW()),

('dummy_user_14', 'Võ Minh Phúc', '0901234014', 'male', 27, 18, 'Bartender, làm ca đêm. Cần bạn ở ghép thoải mái về thời gian.', NULL, '{"budget_min": 2000000, "budget_max": 3500000, "cleanliness": 3, "noise_tolerance": 4, "smoking": false, "pets": true}', 'find_roommate', 1, NOW()),

('dummy_user_15', 'Huỳnh Thị Quỳnh', '0901234015', 'female', 24, 25, 'Kế toán, thích nấu ăn. Tìm bạn cùng phòng yêu ẩm thực.', NULL, '{"budget_min": 2500000, "budget_max": 4000000, "cleanliness": 5, "noise_tolerance": 2, "smoking": false, "pets": false}', 'find_roommate', 1, NOW()),

-- Users ở Đà Nẵng
('dummy_user_16', 'Tô Văn Sơn', '0901234016', 'male', 25, 29, 'Hướng dẫn viên du lịch, thích khám phá. Tìm bạn cùng phòng năng động.', NULL, '{"budget_min": 1500000, "budget_max": 2500000, "cleanliness": 3, "noise_tolerance": 4, "smoking": false, "pets": false}', 'find_roommate', 1, NOW()),

('dummy_user_17', 'Cao Thị Thanh', '0901234017', 'female', 23, 30, 'Nhân viên khách sạn, thích biển và cafe. Tìm bạn nữ thân thiện.', NULL, '{"budget_min": 2000000, "budget_max": 3000000, "cleanliness": 4, "noise_tolerance": 3, "smoking": false, "pets": true}', 'find_roommate', 1, NOW()),

('dummy_user_18', 'Dương Minh Tuấn', '0901234018', 'male', 28, 31, 'Lập trình viên, thích chơi game. Tìm bạn nam có chung sở thích.', NULL, '{"budget_min": 2500000, "budget_max": 4000000, "cleanliness": 3, "noise_tolerance": 4, "smoking": false, "pets": false}', 'find_room', 1, NOW()),

('dummy_user_19', 'Lưu Thị Uyên', '0901234019', 'female', 22, 32, 'Sinh viên kiến trúc, thích vẽ. Cần không gian sáng tạo.', NULL, '{"budget_min": 1500000, "budget_max": 2500000, "cleanliness": 4, "noise_tolerance": 3, "smoking": false, "pets": false}', 'find_roommate', 1, NOW()),

('dummy_user_20', 'Đinh Văn Vũ', '0901234020', 'male', 26, 33, 'Photographer, hay chụp ảnh. Tìm phòng có view đẹp.', NULL, '{"budget_min": 2000000, "budget_max": 3500000, "cleanliness": 3, "noise_tolerance": 3, "smoking": false, "pets": true}', 'find_room', 1, NOW());

-- ============================================
-- INSERT DUMMY ROOMS (15 rooms)
-- ============================================

INSERT INTO rooms (owner_id, title, description, price, area, district_id, address, images, amenities, status, payment_status, payment_date, views_count, likes_count, created_at, expired_at) VALUES

-- Rooms ở Hà Nội
((SELECT id FROM users WHERE zalo_id = 'dummy_user_6'),
'Phòng đẹp gần Đại học Bách Khoa HN',
'Phòng 25m2, đầy đủ nội thất, gần trường, siêu thị. An ninh tốt, chủ nhà thân thiện.',
2800000, 25, 5, '123 Tạ Quang Bửu, Hai Bà Trưng',
'[]',
'{"wifi": true, "ac": true, "kitchen": true, "parking": true, "laundry": true, "furniture": true, "balcony": false, "security": true}',
'active', 'paid', NOW(), 45, 12, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_ADD(NOW(), INTERVAL 25 DAY)),

((SELECT id FROM users WHERE zalo_id = 'dummy_user_8'),
'Căn hộ mini Cầu Giấy',
'Căn hộ 30m2, có gác lửng, WC riêng. Giờ giấc tự do, điện nước giá dân.',
3500000, 30, 6, '456 Dương Quảng Hàm, Cầu Giấy',
'[]',
'{"wifi": true, "ac": true, "kitchen": true, "parking": false, "laundry": true, "furniture": true, "balcony": true, "security": true}',
'active', 'paid', NOW(), 67, 18, DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_ADD(NOW(), INTERVAL 20 DAY)),

((SELECT id FROM users WHERE zalo_id = 'dummy_user_10'),
'Phòng trọ Thanh Xuân giá rẻ',
'Phòng 20m2, toilet riêng, có cửa sổ thoáng mát. Gần Big C, bến xe.',
2200000, 20, 9, '789 Nguyễn Trãi, Thanh Xuân',
'[]',
'{"wifi": true, "ac": false, "kitchen": true, "parking": true, "laundry": false, "furniture": true, "balcony": false, "security": true}',
'active', 'paid', NOW(), 34, 8, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_ADD(NOW(), INTERVAL 27 DAY)),

((SELECT id FROM users WHERE zalo_id = 'dummy_user_4'),
'Chung cư mini Đống Đa',
'Studio 28m2, full nội thất, có thang máy. View đẹp, yên tĩnh.',
4200000, 28, 6, '12 Láng Hạ, Đống Đa',
'[]',
'{"wifi": true, "ac": true, "kitchen": true, "parking": true, "laundry": true, "furniture": true, "balcony": true, "security": true}',
'active', 'paid', NOW(), 89, 23, DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_ADD(NOW(), INTERVAL 15 DAY)),

((SELECT id FROM users WHERE zalo_id = 'dummy_user_2'),
'Phòng 2 người Hai Bà Trưng',
'Phòng 35m2 cho 2 người, có 2 giường. Khu vực an ninh, gần công viên.',
3000000, 35, 7, '234 Bà Triệu, Hai Bà Trưng',
'[]',
'{"wifi": true, "ac": true, "kitchen": true, "parking": true, "laundry": true, "furniture": true, "balcony": false, "security": true}',
'active', 'paid', NOW(), 56, 15, DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_ADD(NOW(), INTERVAL 23 DAY)),

-- Rooms ở TP.HCM
((SELECT id FROM users WHERE zalo_id = 'dummy_user_11'),
'Căn hộ cao cấp Quận 1',
'Studio 40m2, view thành phố, full nội thất cao cấp. Gần Vincom, tiện di chuyển.',
6500000, 40, 13, '567 Nguyễn Huệ, Quận 1',
'[]',
'{"wifi": true, "ac": true, "kitchen": true, "parking": true, "laundry": true, "furniture": true, "balcony": true, "security": true}',
'active', 'paid', NOW(), 123, 34, DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_ADD(NOW(), INTERVAL 18 DAY)),

((SELECT id FROM users WHERE zalo_id = 'dummy_user_14'),
'Phòng trọ Bình Thạnh',
'Phòng 22m2, gần chợ Bà Chiểu. Giá rẻ, phù hợp sinh viên.',
2500000, 22, 25, '890 Xô Viết Nghệ Tĩnh, Bình Thạnh',
'[]',
'{"wifi": true, "ac": true, "kitchen": true, "parking": false, "laundry": true, "furniture": true, "balcony": false, "security": false}',
'active', 'paid', NOW(), 42, 10, DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_ADD(NOW(), INTERVAL 26 DAY)),

((SELECT id FROM users WHERE zalo_id = 'dummy_user_12'),
'Căn hộ mini Phú Nhuận',
'28m2, WC riêng, có ban công. Khu vực yên tĩnh, an ninh tốt.',
3800000, 28, 27, '345 Phan Đăng Lưu, Phú Nhuận',
'[]',
'{"wifi": true, "ac": true, "kitchen": true, "parking": true, "laundry": true, "furniture": true, "balcony": true, "security": true}',
'active', 'paid', NOW(), 71, 19, DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_ADD(NOW(), INTERVAL 22 DAY)),

((SELECT id FROM users WHERE zalo_id = 'dummy_user_15'),
'Phòng 2 người Tân Bình',
'Phòng 32m2, gần sân bay. Phù hợp 2 người, có chỗ để xe.',
3200000, 32, 28, '678 Cộng Hòa, Tân Bình',
'[]',
'{"wifi": true, "ac": true, "kitchen": true, "parking": true, "laundry": false, "furniture": true, "balcony": false, "security": true}',
'active', 'paid', NOW(), 58, 14, DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_ADD(NOW(), INTERVAL 24 DAY)),

((SELECT id FROM users WHERE zalo_id = 'dummy_user_13'),
'Studio Quận 7 view sông',
'35m2, tầng cao, view sông Sài Gòn. Chung cư mới, đầy đủ tiện ích.',
5500000, 35, 19, '901 Nguyễn Hữu Thọ, Quận 7',
'[]',
'{"wifi": true, "ac": true, "kitchen": true, "parking": true, "laundry": true, "furniture": true, "balcony": true, "security": true}',
'active', 'paid', NOW(), 98, 27, DATE_SUB(NOW(), INTERVAL 14 DAY), DATE_ADD(NOW(), INTERVAL 16 DAY)),

-- Rooms ở Đà Nẵng
((SELECT id FROM users WHERE zalo_id = 'dummy_user_18'),
'Phòng view biển Sơn Trà',
'30m2, nhìn ra biển, thoáng mát. Gần bãi tắm Mỹ Khê.',
3500000, 30, 31, '123 Võ Nguyên Giáp, Sơn Trà',
'[]',
'{"wifi": true, "ac": true, "kitchen": true, "parking": true, "laundry": true, "furniture": true, "balcony": true, "security": true}',
'active', 'paid', NOW(), 76, 21, DATE_SUB(NOW(), INTERVAL 9 DAY), DATE_ADD(NOW(), INTERVAL 21 DAY)),

((SELECT id FROM users WHERE zalo_id = 'dummy_user_20'),
'Căn hộ Hải Châu trung tâm',
'25m2, gần cầu Rồng, chợ Hàn. Tiện nghi đầy đủ.',
2800000, 25, 29, '456 Trần Phú, Hải Châu',
'[]',
'{"wifi": true, "ac": true, "kitchen": true, "parking": false, "laundry": true, "furniture": true, "balcony": false, "security": true}',
'active', 'paid', NOW(), 52, 13, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_ADD(NOW(), INTERVAL 25 DAY)),

((SELECT id FROM users WHERE zalo_id = 'dummy_user_16'),
'Phòng trọ Thanh Khê',
'20m2, gần Đại học Duy Tân. Giá sinh viên.',
1800000, 20, 30, '789 Nguyễn Hữu Thọ, Thanh Khê',
'[]',
'{"wifi": true, "ac": false, "kitchen": true, "parking": true, "laundry": false, "furniture": true, "balcony": false, "security": false}',
'active', 'paid', NOW(), 38, 9, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 28 DAY)),

((SELECT id FROM users WHERE zalo_id = 'dummy_user_17'),
'Studio Ngũ Hành Sơn',
'28m2, gần Marble Mountains. Yên tĩnh, phù hợp làm việc.',
2500000, 28, 32, '234 Huyền Trân Công Chúa, Ngũ Hành Sơn',
'[]',
'{"wifi": true, "ac": true, "kitchen": true, "parking": true, "laundry": true, "furniture": true, "balcony": true, "security": true}',
'active', 'paid', NOW(), 44, 11, DATE_SUB(NOW(), INTERVAL 11 DAY), DATE_ADD(NOW(), INTERVAL 19 DAY)),

((SELECT id FROM users WHERE zalo_id = 'dummy_user_19'),
'Phòng 2 người Liên Chiểu',
'33m2, có 2 giường. Gần KCN Hòa Khánh.',
2200000, 33, 33, '567 Tôn Đức Thắng, Liên Chiểu',
'[]',
'{"wifi": true, "ac": true, "kitchen": true, "parking": true, "laundry": true, "furniture": true, "balcony": false, "security": true}',
'active', 'paid', NOW(), 36, 7, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 29 DAY));

-- ============================================
-- INSERT DUMMY USER SWIPES (tạo swipe history)
-- ============================================

-- User 1 swipes on others
INSERT INTO user_swipes (user_id, target_user_id, is_like, created_at) VALUES
((SELECT id FROM users WHERE zalo_id = 'dummy_user_1'), (SELECT id FROM users WHERE zalo_id = 'dummy_user_2'), 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
((SELECT id FROM users WHERE zalo_id = 'dummy_user_1'), (SELECT id FROM users WHERE zalo_id = 'dummy_user_3'), 0, DATE_SUB(NOW(), INTERVAL 2 DAY)),
((SELECT id FROM users WHERE zalo_id = 'dummy_user_1'), (SELECT id FROM users WHERE zalo_id = 'dummy_user_4'), 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
((SELECT id FROM users WHERE zalo_id = 'dummy_user_1'), (SELECT id FROM users WHERE zalo_id = 'dummy_user_5'), 1, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- User 2 swipes back (create matches)
INSERT INTO user_swipes (user_id, target_user_id, is_like, created_at) VALUES
((SELECT id FROM users WHERE zalo_id = 'dummy_user_2'), (SELECT id FROM users WHERE zalo_id = 'dummy_user_1'), 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
((SELECT id FROM users WHERE zalo_id = 'dummy_user_2'), (SELECT id FROM users WHERE zalo_id = 'dummy_user_4'), 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
((SELECT id FROM users WHERE zalo_id = 'dummy_user_2'), (SELECT id FROM users WHERE zalo_id = 'dummy_user_5'), 0, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- More swipes
INSERT INTO user_swipes (user_id, target_user_id, is_like, created_at) VALUES
((SELECT id FROM users WHERE zalo_id = 'dummy_user_4'), (SELECT id FROM users WHERE zalo_id = 'dummy_user_1'), 1, NOW()),
((SELECT id FROM users WHERE zalo_id = 'dummy_user_4'), (SELECT id FROM users WHERE zalo_id = 'dummy_user_2'), 0, NOW()),
((SELECT id FROM users WHERE zalo_id = 'dummy_user_5'), (SELECT id FROM users WHERE zalo_id = 'dummy_user_1'), 1, NOW());

-- ============================================
-- INSERT DUMMY ROOM SWIPES
-- ============================================

INSERT INTO room_swipes (user_id, room_id, is_like, created_at) VALUES
((SELECT id FROM users WHERE zalo_id = 'dummy_user_6'), (SELECT id FROM rooms WHERE title = 'Phòng đẹp gần Đại học Bách Khoa HN'), 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
((SELECT id FROM users WHERE zalo_id = 'dummy_user_6'), (SELECT id FROM rooms WHERE title = 'Căn hộ mini Cầu Giấy'), 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
((SELECT id FROM users WHERE zalo_id = 'dummy_user_8'), (SELECT id FROM rooms WHERE title = 'Phòng trọ Thanh Xuân giá rẻ'), 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
((SELECT id FROM users WHERE zalo_id = 'dummy_user_11'), (SELECT id FROM rooms WHERE title = 'Căn hộ cao cấp Quận 1'), 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
((SELECT id FROM users WHERE zalo_id = 'dummy_user_18'), (SELECT id FROM rooms WHERE title = 'Phòng view biển Sơn Trà'), 1, NOW());

-- ============================================
-- INSERT DUMMY MATCHES (mutual likes)
-- ============================================

INSERT INTO matches (user1_id, user2_id, room_id, status, matched_at) VALUES
-- Match giữa user 1 và user 2 (cùng like nhau)
((SELECT id FROM users WHERE zalo_id = 'dummy_user_1'),
 (SELECT id FROM users WHERE zalo_id = 'dummy_user_2'),
 NULL, 'pending', DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- Match giữa user 1 và user 4
((SELECT id FROM users WHERE zalo_id = 'dummy_user_1'),
 (SELECT id FROM users WHERE zalo_id = 'dummy_user_4'),
 NULL, 'pending', NOW()),

-- Match giữa user 1 và user 5
((SELECT id FROM users WHERE zalo_id = 'dummy_user_1'),
 (SELECT id FROM users WHERE zalo_id = 'dummy_user_5'),
 NULL, 'pending', NOW());

-- ============================================
-- UPDATE view và like counts cho rooms
-- ============================================

UPDATE rooms SET views_count = FLOOR(RAND() * 100) + 20 WHERE status = 'active';
UPDATE rooms SET likes_count = FLOOR(RAND() * 30) + 5 WHERE status = 'active';

-- ============================================
-- DONE!
-- ============================================
-- Bây giờ có:
-- - 20 dummy users với profiles đầy đủ
-- - 15 rooms ở Hà Nội, TP.HCM, Đà Nẵng
-- - User swipes history
-- - Room swipes
-- - 3 matches để test
-- ============================================
