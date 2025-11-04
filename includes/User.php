<?php
/**
 * RUMI - User Model
 * Xử lý tất cả operations liên quan đến users
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

class User {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * Tạo hoặc update user từ Zalo login
     * @param array $zaloData
     * @return int User ID
     */
    public function createOrUpdateFromZalo($zaloData) {
        try {
            // Check nếu user đã tồn tại
            $stmt = $this->db->prepare("SELECT id FROM users WHERE zalo_id = ?");
            $stmt->execute([$zaloData['id']]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Update user info
                $stmt = $this->db->prepare("
                    UPDATE users
                    SET name = ?, avatar = ?, updated_at = NOW()
                    WHERE zalo_id = ?
                ");
                $stmt->execute([
                    $zaloData['name'],
                    $zaloData['picture']['data']['url'] ?? null,
                    $zaloData['id']
                ]);

                return $existing['id'];
            } else {
                // Tạo user mới - sẽ cần complete profile sau
                $stmt = $this->db->prepare("
                    INSERT INTO users (zalo_id, name, avatar, gender, age, district_id, created_at)
                    VALUES (?, ?, ?, 'other', 25, 1, NOW())
                ");
                $stmt->execute([
                    $zaloData['id'],
                    $zaloData['name'],
                    $zaloData['picture']['data']['url'] ?? null
                ]);

                return $this->db->lastInsertId();
            }
        } catch (PDOException $e) {
            error_log("User creation error: " . $e->getMessage());
            throw new Exception("Không thể tạo user");
        }
    }

    /**
     * Get user by ID
     * @param int $userId
     * @return array|null
     */
    public function getById($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, d.name as district_name, d.city_name
                FROM users u
                LEFT JOIN districts d ON u.district_id = d.id
                WHERE u.id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if ($user) {
                // Decode JSON fields
                $user['preferences'] = json_decode($user['preferences'], true);
            }

            return $user;
        } catch (PDOException $e) {
            error_log("Get user error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update user profile
     * @param int $userId
     * @param array $data
     * @return bool
     */
    public function updateProfile($userId, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users
                SET name = ?, phone = ?, gender = ?, age = ?,
                    district_id = ?, bio = ?, preferences = ?,
                    search_mode = ?,
                    sleep_schedule = ?, work_schedule = ?, drinking = ?,
                    guests_policy = ?, occupation = ?, move_in_date = ?,
                    stay_duration = ?, facebook_url = ?, linkedin_url = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            return $stmt->execute([
                $data['name'],
                $data['phone'],
                $data['gender'],
                $data['age'],
                $data['district_id'],
                $data['bio'] ?? null,
                json_encode($data['preferences'] ?? []),
                $data['search_mode'] ?? 'find_roommate_first',
                $data['sleep_schedule'] ?? null,
                $data['work_schedule'] ?? null,
                $data['drinking'] ?? null,
                $data['guests_policy'] ?? null,
                $data['occupation'] ?? null,
                $data['move_in_date'] ?? null,
                $data['stay_duration'] ?? null,
                $data['facebook_url'] ?? null,
                $data['linkedin_url'] ?? null,
                $userId
            ]);
        } catch (PDOException $e) {
            error_log("Update profile error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check nếu user đã complete profile
     * @param int $userId
     * @return bool
     */
    public function hasCompleteProfile($userId) {
        $user = $this->getById($userId);

        if (!$user) {
            return false;
        }

        // Check các fields required
        return !empty($user['phone']) &&
               !empty($user['gender']) &&
               $user['age'] > 0 &&
               $user['district_id'] > 0;
    }

    /**
     * Get potential matches cho user
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getPotentialMatches($userId, $limit = CARDS_PER_SWIPE) {
        try {
            $user = $this->getById($userId);
            if (!$user) {
                return [];
            }

            // Get IDs của users đã swipe rồi
            $stmt = $this->db->prepare("
                SELECT target_user_id FROM user_swipes WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $swipedIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $swipedIds[] = $userId; // Exclude chính mình

            $placeholders = str_repeat('?,', count($swipedIds) - 1) . '?';

            // Get potential matches với scoring
            $stmt = $this->db->prepare("
                SELECT u.*, d.name as district_name, d.city_name,
                    CASE
                        WHEN u.district_id = ? THEN " . MATCH_DISTRICT_WEIGHT . "
                        ELSE 0
                    END +
                    CASE
                        WHEN ABS(u.age - ?) <= 5 THEN " . MATCH_AGE_WEIGHT . "
                        ELSE 0
                    END as compatibility_score
                FROM users u
                LEFT JOIN districts d ON u.district_id = d.id
                WHERE u.id NOT IN ($placeholders)
                    AND u.is_active = 1
                HAVING compatibility_score >= " . MATCH_MIN_SCORE . "
                ORDER BY compatibility_score DESC, RAND()
                LIMIT ?
            ");

            $params = array_merge(
                [$user['district_id'], $user['age']],
                $swipedIds,
                [$limit]
            );

            $stmt->execute($params);
            $matches = $stmt->fetchAll();

            // Decode JSON fields
            foreach ($matches as &$match) {
                $match['preferences'] = json_decode($match['preferences'], true);
            }

            return $matches;
        } catch (PDOException $e) {
            error_log("Get potential matches error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Record swipe action
     * @param int $userId
     * @param int $targetUserId
     * @param bool $isLike
     * @return bool
     */
    public function swipe($userId, $targetUserId, $isLike) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO user_swipes (user_id, target_user_id, is_like, created_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE is_like = ?, created_at = NOW()
            ");

            return $stmt->execute([$userId, $targetUserId, $isLike, $isLike]);
        } catch (PDOException $e) {
            error_log("Swipe error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if swipe tạo match (mutual like)
     * @param int $userId
     * @param int $targetUserId
     * @return bool
     */
    public function checkMutualLike($userId, $targetUserId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM user_swipes
                WHERE ((user_id = ? AND target_user_id = ?) OR (user_id = ? AND target_user_id = ?))
                    AND is_like = 1
            ");
            $stmt->execute([$userId, $targetUserId, $targetUserId, $userId]);
            $result = $stmt->fetch();

            return $result['count'] == 2;
        } catch (PDOException $e) {
            error_log("Check mutual like error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's matches
     * @param int $userId
     * @return array
     */
    public function getMatches($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT m.*,
                    u1.name as user1_name, u1.avatar as user1_avatar,
                    u2.name as user2_name, u2.avatar as user2_avatar, u2.phone as user2_phone
                FROM matches m
                JOIN users u1 ON m.user1_id = u1.id
                JOIN users u2 ON m.user2_id = u2.id
                WHERE m.user1_id = ? OR m.user2_id = ?
                ORDER BY m.matched_at DESC
            ");
            $stmt->execute([$userId, $userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get matches error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Upload user avatar
     * @param int $userId
     * @param array $file $_FILES array
     * @return string|false Filename or false
     */
    public function uploadAvatar($userId, $file) {
        try {
            // Validate file
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Upload error");
            }

            if ($file['size'] > UPLOAD_MAX_SIZE) {
                throw new Exception("File quá lớn");
            }

            if (!in_array($file['type'], UPLOAD_ALLOWED_TYPES)) {
                throw new Exception("File type không hợp lệ");
            }

            // Generate unique filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
            $uploadPath = getUploadPath() . $filename;

            // Move file
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                throw new Exception("Không thể upload file");
            }

            // Update database
            $stmt = $this->db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->execute([$filename, $userId]);

            return $filename;
        } catch (Exception $e) {
            error_log("Upload avatar error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get districts by city
     * @param string $cityName
     * @return array
     */
    public function getDistricts($cityName = null) {
        try {
            if ($cityName) {
                $stmt = $this->db->prepare("SELECT * FROM districts WHERE city_name = ? ORDER BY name");
                $stmt->execute([$cityName]);
            } else {
                $stmt = $this->db->query("SELECT * FROM districts ORDER BY city_name, name");
            }
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get districts error: " . $e->getMessage());
            return [];
        }
    }

    // ============================================
    // NEW METHODS FOR TWO-STAGE MATCHING
    // ============================================

    /**
     * Check if user can access roommate tab
     * For find_room_first mode: must have liked at least 1 room
     * For find_roommate_first mode: always accessible
     *
     * @param int $userId
     * @return bool
     */
    public function canAccessRoommateTab($userId) {
        try {
            $user = $this->getById($userId);
            if (!$user) return false;

            // If mode is find_roommate_first, always allow access
            if ($user['search_mode'] === 'find_roommate_first') {
                return true;
            }

            // If mode is find_room_first, check if user has liked any rooms
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM room_swipes
                WHERE user_id = ? AND is_like = 1
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();

            return $result['count'] > 0;

        } catch (PDOException $e) {
            error_log("Can access roommate tab error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user can access room tab
     * For find_roommate_first mode: must have at least 1 match
     * For find_room_first mode: always accessible
     *
     * @param int $userId
     * @return bool
     */
    public function canAccessRoomTab($userId) {
        try {
            $user = $this->getById($userId);
            if (!$user) return false;

            // If mode is find_room_first, always allow access
            if ($user['search_mode'] === 'find_room_first') {
                return true;
            }

            // If mode is find_roommate_first, check if user has any matches
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM matches
                WHERE (user1_id = ? OR user2_id = ?)
                  AND status != 'disconnected'
            ");
            $stmt->execute([$userId, $userId]);
            $result = $stmt->fetch();

            return $result['count'] > 0;

        } catch (PDOException $e) {
            error_log("Can access room tab error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get list of room IDs that user has liked
     * Used for find_room_first mode
     *
     * @param int $userId
     * @return array Array of room IDs
     */
    public function getLikedRoomIds($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT room_id
                FROM room_swipes
                WHERE user_id = ? AND is_like = 1
                ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Get liked room IDs error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get matched users for current user
     * @param int $userId
     * @return array
     */
    public function getMatchedUsers($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT
                    CASE
                        WHEN m.user1_id = ? THEN u2.id
                        ELSE u1.id
                    END as id,
                    CASE
                        WHEN m.user1_id = ? THEN u2.name
                        ELSE u1.name
                    END as name,
                    CASE
                        WHEN m.user1_id = ? THEN u2.age
                        ELSE u1.age
                    END as age,
                    CASE
                        WHEN m.user1_id = ? THEN u2.avatar
                        ELSE u1.avatar
                    END as avatar,
                    CASE
                        WHEN m.user1_id = ? THEN u2.bio
                        ELSE u1.bio
                    END as bio,
                    CASE
                        WHEN m.user1_id = ? THEN u2.preferences
                        ELSE u1.preferences
                    END as preferences,
                    CASE
                        WHEN m.user1_id = ? THEN d2.name
                        ELSE d1.name
                    END as district_name
                FROM matches m
                INNER JOIN users u1 ON m.user1_id = u1.id
                INNER JOIN users u2 ON m.user2_id = u2.id
                LEFT JOIN districts d1 ON u1.district_id = d1.id
                LEFT JOIN districts d2 ON u2.district_id = d2.id
                WHERE (m.user1_id = ? OR m.user2_id = ?)
                  AND m.status != 'disconnected'
                ORDER BY m.matched_at DESC
            ");
            $stmt->execute([
                $userId, $userId, $userId, $userId,
                $userId, $userId, $userId, $userId, $userId
            ]);
            $users = $stmt->fetchAll();

            // Decode preferences JSON
            foreach ($users as &$user) {
                $user['preferences'] = json_decode($user['preferences'], true);
            }

            return $users;
        } catch (PDOException $e) {
            error_log("Get matched users error: " . $e->getMessage());
            return [];
        }
    }
}
