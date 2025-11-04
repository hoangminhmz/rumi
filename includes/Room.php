<?php
/**
 * RUMI - Room Model
 * Xử lý tất cả operations liên quan đến rooms/listings
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/GeoLocationService.php';

class Room {
    private $db;
    private $geoService;

    public function __construct() {
        $this->db = getDB();
        $this->geoService = new GeoLocationService();
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
     * Get active rooms for swipe with smart filtering and ranking
     * @param int $userId
     * @param int $userDistrictId
     * @param array $userPreferences User preferences including budget, max_distance, etc.
     * @param int $limit
     * @return array
     */
    public function getPotentialRooms($userId, $userDistrictId, $userPreferences = [], $limit = CARDS_PER_SWIPE) {
        try {
            // Get user location (district center)
            $userLocation = $this->geoService->getUserLocation($userId);

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

            // Build WHERE conditions based on preferences
            $whereConditions = [
                "r.id NOT IN ($placeholders)",
                "r.status = 'active'",
                "r.expired_at > NOW()"
            ];
            $params = $swipedIds;

            // Price filter
            if (!empty($userPreferences['budget_min'])) {
                $whereConditions[] = "r.price >= ?";
                $params[] = $userPreferences['budget_min'];
            }
            if (!empty($userPreferences['budget_max'])) {
                $whereConditions[] = "r.price <= ?";
                $params[] = $userPreferences['budget_max'];
            }

            // Area filter
            if (!empty($userPreferences['area_min'])) {
                $whereConditions[] = "r.area >= ?";
                $params[] = $userPreferences['area_min'];
            }
            if (!empty($userPreferences['area_max'])) {
                $whereConditions[] = "r.area <= ?";
                $params[] = $userPreferences['area_max'];
            }

            $whereClause = implode(' AND ', $whereConditions);

            // Get rooms with all data needed for ranking
            // Fetch more than needed so we can filter by distance and rank properly
            $fetchLimit = $limit * 3;

            $stmt = $this->db->prepare("
                SELECT r.*,
                       d.name as district_name,
                       d.city_name,
                       d.latitude as district_lat,
                       d.longitude as district_lng,
                       u.name as owner_name,
                       u.avatar as owner_avatar
                FROM rooms r
                LEFT JOIN districts d ON r.district_id = d.id
                LEFT JOIN users u ON r.owner_id = u.id
                WHERE $whereClause
                LIMIT ?
            ");

            $params[] = $fetchLimit;
            $stmt->execute($params);
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate distance and apply smart ranking
            $maxDistance = isset($userPreferences['max_distance'])
                ? min($userPreferences['max_distance'], MAX_DISTANCE_LIMIT)
                : DEFAULT_MAX_DISTANCE;

            $rankedRooms = [];

            foreach ($rooms as &$room) {
                // Decode JSON fields
                $room['images'] = json_decode($room['images'], true) ?? [];
                $room['amenities'] = json_decode($room['amenities'], true) ?? [];

                // Calculate distance
                $distance = null;
                if ($userLocation && $room['latitude'] && $room['longitude']) {
                    $distance = $this->geoService->calculateDistance(
                        $userLocation['latitude'],
                        $userLocation['longitude'],
                        $room['latitude'],
                        $room['longitude']
                    );
                } elseif ($userLocation && $room['district_lat'] && $room['district_lng']) {
                    // Fallback to district center if room coordinates not available
                    $distance = $this->geoService->calculateDistance(
                        $userLocation['latitude'],
                        $userLocation['longitude'],
                        $room['district_lat'],
                        $room['district_lng']
                    );
                }

                // Filter by max distance if distance is available
                if ($distance !== null && $distance > $maxDistance) {
                    continue; // Skip rooms beyond max distance
                }

                $room['distance_km'] = $distance;
                $room['distance_formatted'] = $this->geoService->formatDistance($distance);

                // Calculate smart ranking score (Phase 2)
                $room['ranking_score'] = $this->calculateRoomScore($room, $userPreferences, $distance);

                $rankedRooms[] = $room;
            }

            // Sort by ranking score (highest first)
            usort($rankedRooms, function($a, $b) {
                return $b['ranking_score'] <=> $a['ranking_score'];
            });

            // Return only the requested limit
            return array_slice($rankedRooms, 0, $limit);

        } catch (PDOException $e) {
            error_log("Get potential rooms error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculate smart ranking score for a room (Phase 2)
     * @param array $room Room data
     * @param array $userPreferences User preferences
     * @param float|null $distance Distance in km
     * @return float Score from 0 to 100
     */
    private function calculateRoomScore($room, $userPreferences, $distance) {
        $score = 0;

        // 1. Distance Score (40%) - Closer is better
        if ($distance !== null) {
            $maxDistance = isset($userPreferences['max_distance'])
                ? $userPreferences['max_distance']
                : DEFAULT_MAX_DISTANCE;

            // Score decreases linearly from 100 to 0 as distance approaches max
            $distanceScore = max(0, (1 - ($distance / $maxDistance)) * 100);
            $score += $distanceScore * RANKING_DISTANCE_WEIGHT;
        } else {
            // No distance data, give neutral score
            $score += 50 * RANKING_DISTANCE_WEIGHT;
        }

        // 2. Price Match Score (30%) - How well price matches budget
        if (!empty($userPreferences['budget_min']) && !empty($userPreferences['budget_max'])) {
            $budgetMid = ($userPreferences['budget_min'] + $userPreferences['budget_max']) / 2;
            $budgetRange = $userPreferences['budget_max'] - $userPreferences['budget_min'];

            if ($budgetRange > 0) {
                // Score is 100 if price is at budget midpoint, decreases as it moves away
                $priceDiff = abs($room['price'] - $budgetMid);
                $priceScore = max(0, (1 - ($priceDiff / $budgetRange)) * 100);
                $score += $priceScore * RANKING_PRICE_WEIGHT;
            } else {
                $score += 100 * RANKING_PRICE_WEIGHT; // Perfect match if exact budget
            }
        } else {
            // No price preference, give neutral score
            $score += 50 * RANKING_PRICE_WEIGHT;
        }

        // 3. Amenities Match Score (20%) - Based on user preferences
        if (!empty($userPreferences)) {
            $amenitiesScore = 0;
            $checkedAmenities = 0;

            $roomAmenities = $room['amenities'] ?? [];

            // Check common boolean preferences
            $amenityPreferences = ['wifi', 'ac', 'kitchen', 'parking', 'laundry', 'furniture'];
            foreach ($amenityPreferences as $amenity) {
                if (isset($userPreferences[$amenity])) {
                    $checkedAmenities++;
                    $userWants = (bool) $userPreferences[$amenity];
                    $roomHas = !empty($roomAmenities[$amenity]);

                    if ($userWants && $roomHas) {
                        $amenitiesScore += 100; // Perfect match
                    } elseif (!$userWants && !$roomHas) {
                        $amenitiesScore += 100; // Also good (user doesn't want, room doesn't have)
                    } elseif ($userWants && !$roomHas) {
                        $amenitiesScore += 0; // Bad (user wants but room doesn't have)
                    } else {
                        $amenitiesScore += 50; // Neutral (user doesn't want but room has - not terrible)
                    }
                }
            }

            if ($checkedAmenities > 0) {
                $amenitiesScore = $amenitiesScore / $checkedAmenities;
                $score += $amenitiesScore * RANKING_AMENITIES_WEIGHT;
            } else {
                $score += 50 * RANKING_AMENITIES_WEIGHT;
            }
        } else {
            $score += 50 * RANKING_AMENITIES_WEIGHT;
        }

        // 4. Popularity Score (10%) - Based on likes
        $likesCount = $room['likes_count'] ?? 0;
        // Normalize: 10+ likes = 100 score, 0 likes = 0 score
        $popularityScore = min(100, ($likesCount / 10) * 100);
        $score += $popularityScore * RANKING_POPULARITY_WEIGHT;

        return round($score, 2);
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
