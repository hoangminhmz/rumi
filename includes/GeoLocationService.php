<?php
/**
 * RUMI - GeoLocation Service
 * Handles geocoding and distance calculations
 */

class GeoLocationService {
    private $db;
    private $apiKey;

    /**
     * Constructor
     */
    public function __construct() {
        // Use getDB() instead of global $db to ensure proper connection
        $this->db = getDB();

        // Get API key from constants or config
        $this->apiKey = defined('MAPBOX_API_KEY') ? MAPBOX_API_KEY : '';
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     * @param float $lat1 Latitude of first point
     * @param float $lng1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lng2 Longitude of second point
     * @return float Distance in kilometers
     */
    public function calculateDistance($lat1, $lng1, $lat2, $lng2) {
        // Validate coordinates
        if (!$this->isValidCoordinate($lat1, $lng1) || !$this->isValidCoordinate($lat2, $lng2)) {
            return null;
        }

        $earthRadius = defined('EARTH_RADIUS_KM') ? EARTH_RADIUS_KM : 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;

        return round($distance, 2);
    }

    /**
     * Validate coordinate values
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @return bool
     */
    private function isValidCoordinate($lat, $lng) {
        return is_numeric($lat) && is_numeric($lng) &&
               $lat >= -90 && $lat <= 90 &&
               $lng >= -180 && $lng <= 180;
    }

    /**
     * Geocode address to coordinates using Mapbox API
     * @param string $address Full address
     * @return array|null ['latitude' => float, 'longitude' => float] or null on failure
     */
    public function geocodeAddress($address) {
        if (empty($this->apiKey)) {
            error_log("RUMI: Mapbox API key not configured");
            return null;
        }

        if (empty($address)) {
            return null;
        }

        try {
            // Mapbox Geocoding API endpoint
            $encodedAddress = urlencode($address);
            $url = "https://api.mapbox.com/geocoding/v5/mapbox.places/{$encodedAddress}.json?access_token={$this->apiKey}&country=VN&limit=1";

            // Use cURL for better error handling
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                error_log("RUMI Geocoding cURL Error: {$error}");
                return null;
            }

            if ($httpCode !== 200) {
                error_log("RUMI Geocoding HTTP Error: {$httpCode}");
                return null;
            }

            $data = json_decode($response, true);

            if (isset($data['features'][0]['geometry']['coordinates'])) {
                $coordinates = $data['features'][0]['geometry']['coordinates'];

                // Mapbox returns [longitude, latitude], we need [latitude, longitude]
                return [
                    'latitude' => $coordinates[1],
                    'longitude' => $coordinates[0]
                ];
            }

            return null;

        } catch (Exception $e) {
            error_log("RUMI Geocoding Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get district center coordinates from database
     * @param int $districtId District ID
     * @return array|null ['latitude' => float, 'longitude' => float] or null
     */
    public function getDistrictCenter($districtId) {
        try {
            $stmt = $this->db->prepare("
                SELECT latitude, longitude
                FROM districts
                WHERE id = ? AND latitude IS NOT NULL AND longitude IS NOT NULL
            ");
            $stmt->execute([$districtId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && $this->isValidCoordinate($result['latitude'], $result['longitude'])) {
                return [
                    'latitude' => (float) $result['latitude'],
                    'longitude' => (float) $result['longitude']
                ];
            }

            return null;

        } catch (PDOException $e) {
            error_log("RUMI: Error fetching district center: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Geocode and update room coordinates
     * @param int $roomId Room ID
     * @param string $fullAddress Full address string
     * @return bool Success status
     */
    public function geocodeRoom($roomId, $fullAddress) {
        $coordinates = $this->geocodeAddress($fullAddress);

        if (!$coordinates) {
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                UPDATE rooms
                SET latitude = ?, longitude = ?
                WHERE id = ?
            ");

            return $stmt->execute([
                $coordinates['latitude'],
                $coordinates['longitude'],
                $roomId
            ]);

        } catch (PDOException $e) {
            error_log("RUMI: Error updating room coordinates: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's reference location (district center or custom location)
     * @param int $userId User ID
     * @return array|null ['latitude' => float, 'longitude' => float] or null
     */
    public function getUserLocation($userId) {
        try {
            // Get user's district
            $stmt = $this->db->prepare("SELECT district_id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return null;
            }

            // Use district center as user location
            return $this->getDistrictCenter($user['district_id']);

        } catch (PDOException $e) {
            error_log("RUMI: Error getting user location: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Format distance for display
     * @param float $distanceKm Distance in kilometers
     * @return string Formatted distance string
     */
    public function formatDistance($distanceKm) {
        if ($distanceKm === null) {
            return '';
        }

        if ($distanceKm < 1) {
            return round($distanceKm * 1000) . ' m';
        }

        return round($distanceKm, 1) . ' km';
    }

    /**
     * Calculate midpoint between two coordinates
     * Used for finding ideal location between two users
     *
     * @param float $lat1 Latitude of first point
     * @param float $lng1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lng2 Longitude of second point
     * @return array ['latitude' => float, 'longitude' => float]
     */
    public function calculateMidpoint($lat1, $lng1, $lat2, $lng2) {
        // Validate coordinates
        if (!$this->isValidCoordinate($lat1, $lng1) || !$this->isValidCoordinate($lat2, $lng2)) {
            return [
                'latitude' => ($lat1 + $lat2) / 2,
                'longitude' => ($lng1 + $lng2) / 2
            ];
        }

        // Convert to radians
        $lat1Rad = deg2rad($lat1);
        $lng1Rad = deg2rad($lng1);
        $lat2Rad = deg2rad($lat2);
        $lng2Rad = deg2rad($lng2);

        // Calculate midpoint using spherical geometry
        $dLng = $lng2Rad - $lng1Rad;

        $bx = cos($lat2Rad) * cos($dLng);
        $by = cos($lat2Rad) * sin($dLng);

        $lat3Rad = atan2(
            sin($lat1Rad) + sin($lat2Rad),
            sqrt((cos($lat1Rad) + $bx) * (cos($lat1Rad) + $bx) + $by * $by)
        );

        $lng3Rad = $lng1Rad + atan2($by, cos($lat1Rad) + $bx);

        // Convert back to degrees
        return [
            'latitude' => rad2deg($lat3Rad),
            'longitude' => rad2deg($lng3Rad)
        ];
    }

    /**
     * Search address using Mapbox Geocoding API
     * @param string $query Search query
     * @param string $country Country code (default: VN)
     * @return array Array of results
     */
    public function searchAddress($query, $country = 'VN') {
        if (empty($this->apiKey)) {
            error_log("RUMI: Mapbox API key not configured");
            return [];
        }

        if (empty($query)) {
            return [];
        }

        try {
            $encodedQuery = urlencode($query);
            $url = "https://api.mapbox.com/geocoding/v5/mapbox.places/{$encodedQuery}.json?access_token={$this->apiKey}&country={$country}&limit=5";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error || $httpCode !== 200) {
                error_log("RUMI Geocoding search error: {$error}");
                return [];
            }

            $data = json_decode($response, true);

            if (isset($data['features']) && is_array($data['features'])) {
                return $data['features'];
            }

            return [];

        } catch (Exception $e) {
            error_log("RUMI Geocoding search exception: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Reverse geocode coordinates to address
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @return array|null Address components or null
     */
    public function reverseGeocode($lat, $lng) {
        if (empty($this->apiKey)) {
            error_log("RUMI: Mapbox API key not configured");
            return null;
        }

        if (!$this->isValidCoordinate($lat, $lng)) {
            return null;
        }

        try {
            $url = "https://api.mapbox.com/geocoding/v5/mapbox.places/{$lng},{$lat}.json?access_token={$this->apiKey}&types=address,place";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error || $httpCode !== 200) {
                error_log("RUMI Reverse geocoding error: {$error}");
                return null;
            }

            $data = json_decode($response, true);

            if (isset($data['features'][0])) {
                $feature = $data['features'][0];

                return [
                    'address' => $feature['place_name'] ?? '',
                    'context' => $feature['context'] ?? [],
                    'coordinates' => $feature['geometry']['coordinates'] ?? []
                ];
            }

            return null;

        } catch (Exception $e) {
            error_log("RUMI Reverse geocoding exception: " . $e->getMessage());
            return null;
        }
    }
}
