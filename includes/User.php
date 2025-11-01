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
                    search_mode = ?, updated_at = NOW()
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
                $data['search_mode'] ?? 'find_roommate',
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
}
