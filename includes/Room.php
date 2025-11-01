<?php
/**
 * RUMI - Room Model
 * Xử lý tất cả operations liên quan đến rooms/listings
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

class Room {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * Create new room listing
     * @param int $ownerId
     * @param array $data
     * @return int|false Room ID or false
     */
    public function create($ownerId, $data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO rooms (
                    owner_id, title, description, price, area,
                    district_id, address, images, amenities,
                    status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_payment', NOW())
            ");

            $result = $stmt->execute([
                $ownerId,
                $data['title'],
                $data['description'] ?? null,
                $data['price'],
                $data['area'] ?? null,
                $data['district_id'],
                $data['address'],
                json_encode($data['images'] ?? []),
                json_encode($data['amenities'] ?? [])
            ]);

            return $result ? $this->db->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("Create room error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get room by ID
     * @param int $roomId
     * @return array|null
     */
    public function getById($roomId) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, d.name as district_name, d.city_name,
                       u.name as owner_name, u.phone as owner_phone, u.avatar as owner_avatar
                FROM rooms r
                LEFT JOIN districts d ON r.district_id = d.id
                LEFT JOIN users u ON r.owner_id = u.id
                WHERE r.id = ?
            ");
            $stmt->execute([$roomId]);
            $room = $stmt->fetch();

            if ($room) {
                // Decode JSON fields
                $room['images'] = json_decode($room['images'], true) ?? [];
                $room['amenities'] = json_decode($room['amenities'], true) ?? [];
            }

            return $room;
        } catch (PDOException $e) {
            error_log("Get room error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update room
     * @param int $roomId
     * @param array $data
     * @return bool
     */
    public function update($roomId, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE rooms
                SET title = ?, description = ?, price = ?, area = ?,
                    district_id = ?, address = ?, amenities = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            return $stmt->execute([
                $data['title'],
                $data['description'] ?? null,
                $data['price'],
                $data['area'] ?? null,
                $data['district_id'],
                $data['address'],
                json_encode($data['amenities'] ?? []),
                $roomId
            ]);
        } catch (PDOException $e) {
            error_log("Update room error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark room as paid and activate
     * @param int $roomId
     * @param string $paymentId
     * @return bool
     */
    public function markAsPaid($roomId, $paymentId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE rooms
                SET payment_status = 'paid',
                    payment_id = ?,
                    payment_date = NOW(),
                    status = 'active',
                    expired_at = DATE_ADD(NOW(), INTERVAL " . ROOM_LISTING_DURATION . " DAY)
                WHERE id = ?
            ");

            return $stmt->execute([$paymentId, $roomId]);
        } catch (PDOException $e) {
            error_log("Mark room as paid error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get active rooms for swipe
     * @param int $userId
     * @param int $userDistrictId
     * @param int $limit
     * @return array
     */
    public function getPotentialRooms($userId, $userDistrictId, $limit = CARDS_PER_SWIPE) {
        try {
            // Get IDs của rooms đã swipe rồi
            $stmt = $this->db->prepare("
                SELECT room_id FROM room_swipes WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $swipedIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($swipedIds)) {
                $swipedIds = [0]; // Dummy value để query không lỗi
            }

            $placeholders = str_repeat('?,', count($swipedIds) - 1) . '?';

            // Get active rooms, prioritize same district
            $stmt = $this->db->prepare("
                SELECT r.*, d.name as district_name, d.city_name,
                       u.name as owner_name, u.avatar as owner_avatar,
                       CASE WHEN r.district_id = ? THEN 1 ELSE 0 END as same_district
                FROM rooms r
                LEFT JOIN districts d ON r.district_id = d.id
                LEFT JOIN users u ON r.owner_id = u.id
                WHERE r.id NOT IN ($placeholders)
                    AND r.status = 'active'
                    AND r.expired_at > NOW()
                ORDER BY same_district DESC, r.created_at DESC
                LIMIT ?
            ");

            $params = array_merge([$userDistrictId], $swipedIds, [$limit]);
            $stmt->execute($params);
            $rooms = $stmt->fetchAll();

            // Decode JSON fields
            foreach ($rooms as &$room) {
                $room['images'] = json_decode($room['images'], true) ?? [];
                $room['amenities'] = json_decode($room['amenities'], true) ?? [];
            }

            return $rooms;
        } catch (PDOException $e) {
            error_log("Get potential rooms error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Record room swipe
     * @param int $userId
     * @param int $roomId
     * @param bool $isLike
     * @return bool
     */
    public function swipe($userId, $roomId, $isLike) {
        try {
            $this->db->beginTransaction();

            // Insert swipe
            $stmt = $this->db->prepare("
                INSERT INTO room_swipes (user_id, room_id, is_like, created_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE is_like = ?, created_at = NOW()
            ");
            $stmt->execute([$userId, $roomId, $isLike, $isLike]);

            // Update likes count if like
            if ($isLike) {
                $stmt = $this->db->prepare("
                    UPDATE rooms SET likes_count = likes_count + 1 WHERE id = ?
                ");
                $stmt->execute([$roomId]);
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Room swipe error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get rooms owned by user
     * @param int $userId
     * @return array
     */
    public function getByOwner($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, d.name as district_name, d.city_name
                FROM rooms r
                LEFT JOIN districts d ON r.district_id = d.id
                WHERE r.owner_id = ?
                ORDER BY r.created_at DESC
            ");
            $stmt->execute([$userId]);
            $rooms = $stmt->fetchAll();

            // Decode JSON fields
            foreach ($rooms as &$room) {
                $room['images'] = json_decode($room['images'], true) ?? [];
                $room['amenities'] = json_decode($room['amenities'], true) ?? [];
            }

            return $rooms;
        } catch (PDOException $e) {
            error_log("Get rooms by owner error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Upload room images
     * @param int $roomId
     * @param array $files Array of $_FILES
     * @return array Uploaded filenames
     */
    public function uploadImages($roomId, $files) {
        $uploaded = [];

        try {
            foreach ($files as $file) {
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    continue;
                }

                if ($file['size'] > UPLOAD_MAX_SIZE) {
                    continue;
                }

                if (!in_array($file['type'], UPLOAD_ALLOWED_TYPES)) {
                    continue;
                }

                // Generate unique filename
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'room_' . $roomId . '_' . time() . '_' . uniqid() . '.' . $ext;
                $uploadPath = getUploadPath() . $filename;

                // Move file
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $uploaded[] = $filename;
                }

                // Limit số lượng images
                if (count($uploaded) >= UPLOAD_MAX_FILES) {
                    break;
                }
            }

            // Update room images in database
            if (!empty($uploaded)) {
                $room = $this->getById($roomId);
                $existingImages = $room['images'] ?? [];
                $allImages = array_merge($existingImages, $uploaded);

                $stmt = $this->db->prepare("UPDATE rooms SET images = ? WHERE id = ?");
                $stmt->execute([json_encode($allImages), $roomId]);
            }

            return $uploaded;
        } catch (Exception $e) {
            error_log("Upload room images error: " . $e->getMessage());
            return $uploaded;
        }
    }

    /**
     * Increment room views
     * @param int $roomId
     */
    public function incrementViews($roomId) {
        try {
            $stmt = $this->db->prepare("UPDATE rooms SET views_count = views_count + 1 WHERE id = ?");
            $stmt->execute([$roomId]);
        } catch (PDOException $e) {
            error_log("Increment views error: " . $e->getMessage());
        }
    }

    /**
     * Search rooms with filters
     * @param array $filters
     * @param int $page
     * @return array
     */
    public function search($filters = [], $page = 1) {
        try {
            $where = ["r.status = 'active'", "r.expired_at > NOW()"];
            $params = [];

            // City filter
            if (!empty($filters['city'])) {
                $where[] = "d.city_name = ?";
                $params[] = $filters['city'];
            }

            // District filter
            if (!empty($filters['district_id'])) {
                $where[] = "r.district_id = ?";
                $params[] = $filters['district_id'];
            }

            // Price range
            if (!empty($filters['price_min'])) {
                $where[] = "r.price >= ?";
                $params[] = $filters['price_min'];
            }
            if (!empty($filters['price_max'])) {
                $where[] = "r.price <= ?";
                $params[] = $filters['price_max'];
            }

            $whereClause = implode(' AND ', $where);
            $offset = ($page - 1) * ITEMS_PER_PAGE;

            $stmt = $this->db->prepare("
                SELECT r.*, d.name as district_name, d.city_name
                FROM rooms r
                LEFT JOIN districts d ON r.district_id = d.id
                WHERE $whereClause
                ORDER BY r.created_at DESC
                LIMIT ? OFFSET ?
            ");

            $params[] = ITEMS_PER_PAGE;
            $params[] = $offset;

            $stmt->execute($params);
            $rooms = $stmt->fetchAll();

            // Decode JSON fields
            foreach ($rooms as &$room) {
                $room['images'] = json_decode($room['images'], true) ?? [];
                $room['amenities'] = json_decode($room['amenities'], true) ?? [];
            }

            return $rooms;
        } catch (PDOException $e) {
            error_log("Search rooms error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete room
     * @param int $roomId
     * @param int $ownerId Verify ownership
     * @return bool
     */
    public function delete($roomId, $ownerId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM rooms WHERE id = ? AND owner_id = ?");
            return $stmt->execute([$roomId, $ownerId]);
        } catch (PDOException $e) {
            error_log("Delete room error: " . $e->getMessage());
            return false;
        }
    }
}
