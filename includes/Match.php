<?php
/**
 * RUMI - Match Model
 * Xử lý matching logic giữa users
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

class Match {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * Tạo match mới khi có mutual like
     * @param int $user1Id
     * @param int $user2Id
     * @param int|null $roomId Optional room context
     * @return int|false Match ID or false
     */
    public function create($user1Id, $user2Id, $roomId = null) {
        try {
            // Đảm bảo user1_id < user2_id để tránh duplicate
            if ($user1Id > $user2Id) {
                list($user1Id, $user2Id) = [$user2Id, $user1Id];
            }

            // Check if match đã tồn tại
            $stmt = $this->db->prepare("
                SELECT id FROM matches
                WHERE user1_id = ? AND user2_id = ? AND (room_id = ? OR (room_id IS NULL AND ? IS NULL))
            ");
            $stmt->execute([$user1Id, $user2Id, $roomId, $roomId]);
            $existing = $stmt->fetch();

            if ($existing) {
                return $existing['id'];
            }

            // Create new match
            $stmt = $this->db->prepare("
                INSERT INTO matches (user1_id, user2_id, room_id, status, matched_at)
                VALUES (?, ?, ?, 'pending', NOW())
            ");

            $result = $stmt->execute([$user1Id, $user2Id, $roomId]);
            return $result ? $this->db->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("Create match error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get match by ID
     * @param int $matchId
     * @return array|null
     */
    public function getById($matchId) {
        try {
            $stmt = $this->db->prepare("
                SELECT m.*,
                    u1.name as user1_name, u1.avatar as user1_avatar, u1.phone as user1_phone,
                    u1.age as user1_age, u1.gender as user1_gender, u1.bio as user1_bio,
                    d1.name as user1_district, d1.city_name as user1_city,
                    u2.name as user2_name, u2.avatar as user2_avatar, u2.phone as user2_phone,
                    u2.age as user2_age, u2.gender as user2_gender, u2.bio as user2_bio,
                    d2.name as user2_district, d2.city_name as user2_city,
                    r.title as room_title, r.price as room_price, r.address as room_address
                FROM matches m
                JOIN users u1 ON m.user1_id = u1.id
                JOIN users u2 ON m.user2_id = u2.id
                LEFT JOIN districts d1 ON u1.district_id = d1.id
                LEFT JOIN districts d2 ON u2.district_id = d2.id
                LEFT JOIN rooms r ON m.room_id = r.id
                WHERE m.id = ?
            ");
            $stmt->execute([$matchId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get match error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all matches for user
     * @param int $userId
     * @param string $status Filter by status (optional)
     * @return array
     */
    public function getByUser($userId, $status = null) {
        try {
            $where = "(m.user1_id = ? OR m.user2_id = ?)";
            $params = [$userId, $userId];

            if ($status) {
                $where .= " AND m.status = ?";
                $params[] = $status;
            }

            $stmt = $this->db->prepare("
                SELECT m.*,
                    u1.name as user1_name, u1.avatar as user1_avatar,
                    u1.age as user1_age, u1.gender as user1_gender, u1.phone as user1_phone,
                    d1.name as user1_district,
                    u2.name as user2_name, u2.avatar as user2_avatar,
                    u2.age as user2_age, u2.gender as user2_gender, u2.phone as user2_phone,
                    d2.name as user2_district,
                    r.title as room_title, r.price as room_price
                FROM matches m
                JOIN users u1 ON m.user1_id = u1.id
                JOIN users u2 ON m.user2_id = u2.id
                LEFT JOIN districts d1 ON u1.district_id = d1.id
                LEFT JOIN districts d2 ON u2.district_id = d2.id
                LEFT JOIN rooms r ON m.room_id = r.id
                WHERE $where
                ORDER BY m.matched_at DESC
            ");

            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get user matches error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mark match as connected (user clicked to reveal contact)
     * @param int $matchId
     * @return bool
     */
    public function markAsConnected($matchId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE matches
                SET status = 'connected', connected_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$matchId]);
        } catch (PDOException $e) {
            error_log("Mark match connected error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unmatch (disconnect)
     * @param int $matchId
     * @param int $userId Verify user is part of match
     * @return bool
     */
    public function unmatch($matchId, $userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE matches
                SET status = 'disconnected'
                WHERE id = ? AND (user1_id = ? OR user2_id = ?)
            ");
            return $stmt->execute([$matchId, $userId, $userId]);
        } catch (PDOException $e) {
            error_log("Unmatch error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate compatibility score giữa 2 users
     * @param array $user1
     * @param array $user2
     * @return int Score 0-100
     */
    public function calculateCompatibility($user1, $user2) {
        $score = 0;

        // Same district: +30 points
        if ($user1['district_id'] == $user2['district_id']) {
            $score += MATCH_DISTRICT_WEIGHT;
        }

        // Age compatibility (within 5 years): +20 points
        $ageDiff = abs($user1['age'] - $user2['age']);
        if ($ageDiff <= 5) {
            $score += MATCH_AGE_WEIGHT;
        }

        // Preferences match: +50 points
        $pref1 = is_string($user1['preferences']) ? json_decode($user1['preferences'], true) : $user1['preferences'];
        $pref2 = is_string($user2['preferences']) ? json_decode($user2['preferences'], true) : $user2['preferences'];

        if ($pref1 && $pref2) {
            $prefScore = $this->comparePreferences($pref1, $pref2);
            $score += ($prefScore / 100) * MATCH_PREFERENCE_WEIGHT;
        }

        return min(100, $score);
    }

    /**
     * Compare preferences giữa 2 users
     * @param array $pref1
     * @param array $pref2
     * @return int Score 0-100
     */
    private function comparePreferences($pref1, $pref2) {
        $score = 0;
        $maxScore = 0;

        // Budget compatibility
        if (isset($pref1['budget_min'], $pref1['budget_max'], $pref2['budget_min'], $pref2['budget_max'])) {
            $maxScore += 30;
            // Check if budgets overlap
            if ($pref1['budget_max'] >= $pref2['budget_min'] && $pref2['budget_max'] >= $pref1['budget_min']) {
                $score += 30;
            }
        }

        // Cleanliness level (1-5 scale, difference <= 1 is good)
        if (isset($pref1['cleanliness'], $pref2['cleanliness'])) {
            $maxScore += 20;
            $diff = abs($pref1['cleanliness'] - $pref2['cleanliness']);
            if ($diff <= 1) {
                $score += 20;
            } elseif ($diff == 2) {
                $score += 10;
            }
        }

        // Noise tolerance (1-5 scale)
        if (isset($pref1['noise_tolerance'], $pref2['noise_tolerance'])) {
            $maxScore += 20;
            $diff = abs($pref1['noise_tolerance'] - $pref2['noise_tolerance']);
            if ($diff <= 1) {
                $score += 20;
            } elseif ($diff == 2) {
                $score += 10;
            }
        }

        // Smoking (boolean)
        if (isset($pref1['smoking'], $pref2['smoking'])) {
            $maxScore += 15;
            if ($pref1['smoking'] == $pref2['smoking']) {
                $score += 15;
            }
        }

        // Pets (boolean)
        if (isset($pref1['pets'], $pref2['pets'])) {
            $maxScore += 15;
            if ($pref1['pets'] == $pref2['pets']) {
                $score += 15;
            }
        }

        return $maxScore > 0 ? round(($score / $maxScore) * 100) : 0;
    }

    /**
     * Get match statistics for user
     * @param int $userId
     * @return array
     */
    public function getStats($userId) {
        try {
            $stats = [];

            // Total matches
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total
                FROM matches
                WHERE (user1_id = ? OR user2_id = ?) AND status != 'disconnected'
            ");
            $stmt->execute([$userId, $userId]);
            $stats['total_matches'] = $stmt->fetch()['total'];

            // Pending matches
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as pending
                FROM matches
                WHERE (user1_id = ? OR user2_id = ?) AND status = 'pending'
            ");
            $stmt->execute([$userId, $userId]);
            $stats['pending_matches'] = $stmt->fetch()['pending'];

            // Connected matches
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as connected
                FROM matches
                WHERE (user1_id = ? OR user2_id = ?) AND status = 'connected'
            ");
            $stmt->execute([$userId, $userId]);
            $stats['connected_matches'] = $stmt->fetch()['connected'];

            return $stats;
        } catch (PDOException $e) {
            error_log("Get match stats error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if 2 users are already matched
     * @param int $user1Id
     * @param int $user2Id
     * @return bool
     */
    public function areMatched($user1Id, $user2Id) {
        try {
            if ($user1Id > $user2Id) {
                list($user1Id, $user2Id) = [$user2Id, $user1Id];
            }

            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM matches
                WHERE user1_id = ? AND user2_id = ? AND status != 'disconnected'
            ");
            $stmt->execute([$user1Id, $user2Id]);
            $result = $stmt->fetch();

            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Check if matched error: " . $e->getMessage());
            return false;
        }
    }

    // ============================================
    // NEW METHODS FOR TWO-STAGE MATCHING
    // ============================================

    /**
     * Get users who liked the same rooms as the given user
     * This is for "find_room_first" mode
     *
     * @param int $userId User ID
     * @param array $roomIds Array of room IDs that user liked
     * @param array $filters Optional lifestyle filters
     * @return array Array of compatible users
     */
    public function getUsersWhoLikedSameRooms($userId, $roomIds, $filters = []) {
        try {
            if (empty($roomIds)) {
                return [];
            }

            $placeholders = str_repeat('?,', count($roomIds) - 1) . '?';

            // Get all users who liked any of these rooms
            $stmt = $this->db->prepare("
                SELECT DISTINCT
                    u.id,
                    u.name,
                    u.age,
                    u.gender,
                    u.bio,
                    u.avatar,
                    u.preferences,
                    u.sleep_schedule,
                    u.work_schedule,
                    u.drinking,
                    u.guests_policy,
                    u.occupation,
                    d.name as district_name,
                    d.city_name,
                    GROUP_CONCAT(DISTINCT rs.room_id) as shared_room_ids
                FROM room_swipes rs
                INNER JOIN users u ON rs.user_id = u.id
                INNER JOIN districts d ON u.district_id = d.id
                WHERE rs.room_id IN ($placeholders)
                  AND rs.is_like = 1
                  AND rs.user_id != ?
                  AND u.is_active = 1
                  AND u.id NOT IN (
                      SELECT CASE
                          WHEN user1_id = ? THEN user2_id
                          ELSE user1_id
                      END
                      FROM matches
                      WHERE (user1_id = ? OR user2_id = ?)
                  )
                GROUP BY u.id
                ORDER BY rs.created_at DESC
                LIMIT 100
            ");

            $params = array_merge($roomIds, [$userId, $userId, $userId, $userId]);
            $stmt->execute($params);
            $users = $stmt->fetchAll();

            // Get current user for compatibility calculation
            require_once __DIR__ . '/User.php';
            $userModel = new User();
            $currentUser = $userModel->getById($userId);

            // Calculate compatibility score for each user
            $scoredUsers = [];
            foreach ($users as $user) {
                // Decode JSON fields
                $user['preferences'] = json_decode($user['preferences'], true);
                $user['shared_room_ids'] = explode(',', $user['shared_room_ids']);

                // Calculate lifestyle compatibility
                $compatibilityScore = $this->calculateCompatibilityScore($currentUser, $user);

                // Apply minimum threshold
                $minThreshold = defined('MIN_COMPATIBILITY_THRESHOLD')
                    ? MIN_COMPATIBILITY_THRESHOLD
                    : 30;

                if ($compatibilityScore >= $minThreshold) {
                    $user['compatibility_score'] = $compatibilityScore;
                    $user['shared_rooms_count'] = count($user['shared_room_ids']);
                    $scoredUsers[] = $user;
                }
            }

            // Sort by compatibility score (highest first)
            usort($scoredUsers, function($a, $b) {
                // Primary: compatibility score
                $scoreDiff = $b['compatibility_score'] <=> $a['compatibility_score'];
                if ($scoreDiff !== 0) return $scoreDiff;

                // Secondary: number of shared rooms
                return $b['shared_rooms_count'] <=> $a['shared_rooms_count'];
            });

            return $scoredUsers;

        } catch (PDOException $e) {
            error_log("Get users who liked same rooms error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculate lifestyle compatibility score between two users (ENHANCED)
     * Returns score from 0-100
     *
     * @param array $user1 First user data
     * @param array $user2 Second user data
     * @return float Compatibility score
     */
    public function calculateCompatibilityScore($user1, $user2) {
        $score = 0;
        $maxScore = 0;

        // Ensure preferences are arrays
        $prefs1 = is_array($user1['preferences']) ? $user1['preferences'] : [];
        $prefs2 = is_array($user2['preferences']) ? $user2['preferences'] : [];

        // 1. Sleep Schedule Compatibility (20 points)
        if (!empty($user1['sleep_schedule']) && !empty($user2['sleep_schedule'])) {
            $maxScore += 20;
            if ($user1['sleep_schedule'] === $user2['sleep_schedule']) {
                $score += 20; // Perfect match
            } elseif ($user1['sleep_schedule'] === 'flexible' || $user2['sleep_schedule'] === 'flexible') {
                $score += 15; // One is flexible
            } else {
                $score += 5; // Opposite schedules (early_bird vs night_owl)
            }
        }

        // 2. Cleanliness Level (25 points) - Most important
        if (isset($prefs1['cleanliness']) && isset($prefs2['cleanliness'])) {
            $maxScore += 25;
            $diff = abs($prefs1['cleanliness'] - $prefs2['cleanliness']);
            $score += max(0, 25 - ($diff * 6)); // Each level difference = -6 points
        }

        // 3. Noise Tolerance (25 points) - Also very important
        if (isset($prefs1['noise_tolerance']) && isset($prefs2['noise_tolerance'])) {
            $maxScore += 25;
            $diff = abs($prefs1['noise_tolerance'] - $prefs2['noise_tolerance']);
            $score += max(0, 25 - ($diff * 6));
        }

        // 4. Smoking (15 points)
        if (isset($prefs1['smoking']) && isset($prefs2['smoking'])) {
            $maxScore += 15;
            if ($prefs1['smoking'] === $prefs2['smoking']) {
                $score += 15; // Same preference
            } else {
                $score += 3; // Mismatch is problematic
            }
        }

        // 5. Drinking/Partying (10 points)
        if (!empty($user1['drinking']) && !empty($user2['drinking'])) {
            $maxScore += 10;
            if ($user1['drinking'] === $user2['drinking']) {
                $score += 10; // Exact match
            } elseif (
                in_array('social', [$user1['drinking'], $user2['drinking']]) &&
                !in_array('frequent', [$user1['drinking'], $user2['drinking']])
            ) {
                $score += 7; // Social drinker is compatible with non-drinker
            } elseif (in_array('social', [$user1['drinking'], $user2['drinking']])) {
                $score += 5; // Social compatible with frequent
            }
        }

        // 6. Guests Policy (5 points)
        if (!empty($user1['guests_policy']) && !empty($user2['guests_policy'])) {
            $maxScore += 5;
            if ($user1['guests_policy'] === $user2['guests_policy']) {
                $score += 5; // Exact match
            } elseif (in_array('occasional', [$user1['guests_policy'], $user2['guests_policy']])) {
                $score += 3; // Occasional is somewhat flexible
            }
        }

        // Normalize to 0-100
        if ($maxScore > 0) {
            return round(($score / $maxScore) * 100, 2);
        }

        // If no new criteria available, fall back to old method
        return $this->calculateCompatibility($user1, $user2);
    }

    /**
     * Get match status between two users
     * @param int $user1Id
     * @param int $user2Id
     * @return array|null Match record or null
     */
    public function getMatchStatus($user1Id, $user2Id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM matches
                WHERE (user1_id = ? AND user2_id = ?)
                   OR (user1_id = ? AND user2_id = ?)
                LIMIT 1
            ");
            $stmt->execute([$user1Id, $user2Id, $user2Id, $user1Id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get match status error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create triple match (user + user + room)
     * @param int $user1Id
     * @param int $user2Id
     * @param int $roomId
     * @return bool
     */
    public function createTripleMatch($user1Id, $user2Id, $roomId) {
        try {
            // Check if match already exists
            $existing = $this->getMatchStatus($user1Id, $user2Id);

            if ($existing) {
                // Update with room_id
                $stmt = $this->db->prepare("
                    UPDATE matches
                    SET room_id = ?, status = 'connected', connected_at = NOW()
                    WHERE id = ?
                ");
                return $stmt->execute([$roomId, $existing['id']]);
            } else {
                // Create new match (use parent create method)
                return $this->create($user1Id, $user2Id, $roomId) !== false;
            }
        } catch (PDOException $e) {
            error_log("Create triple match error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get suggested rooms for a matched pair
     * @param int $matchId Match ID
     * @return array
     */
    public function getSuggestedRoomsForMatch($matchId) {
        try {
            // Get match details
            $stmt = $this->db->prepare("SELECT * FROM matches WHERE id = ?");
            $stmt->execute([$matchId]);
            $match = $stmt->fetch();

            if (!$match) {
                return [];
            }

            // Get rooms suitable for both users
            require_once __DIR__ . '/Room.php';
            $roomModel = new Room();
            return $roomModel->getRoomsForMatchedPair(
                $match['user1_id'],
                $match['user2_id']
            );

        } catch (PDOException $e) {
            error_log("Get suggested rooms error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get count of matches for a user
     * @param int $userId
     * @return int
     */
    public function getMatchCount($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count FROM matches
                WHERE (user1_id = ? OR user2_id = ?)
                  AND status != 'disconnected'
            ");
            $stmt->execute([$userId, $userId]);
            $result = $stmt->fetch();
            return (int) $result['count'];
        } catch (PDOException $e) {
            error_log("Get match count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get array of matched user IDs for a user
     * @param int $userId
     * @return array Array of user IDs
     */
    public function getMatchedUserIds($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    CASE WHEN user1_id = ? THEN user2_id ELSE user1_id END as matched_user_id
                FROM matches
                WHERE (user1_id = ? OR user2_id = ?)
                  AND status != 'disconnected'
            ");
            $stmt->execute([$userId, $userId, $userId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Get matched user IDs error: " . $e->getMessage());
            return [];
        }
    }
}
