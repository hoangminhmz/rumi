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
}
