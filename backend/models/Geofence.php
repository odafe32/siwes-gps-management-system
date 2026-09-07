<?php
/**
 * Geofence Engine — Haversine Formula
 *
 * Core thesis contribution: calculates the distance between an intern's
 * GPS coordinates and their organization's geofence center, then determines
 * whether the intern is inside or outside the geofence boundary.
 *
 * Reference: thesis Section 3.5 — Geofencing and Distance Engine
 */
class Geofence {

    /**
     * Calculate the great-circle distance between two points
     * on the Earth's surface using the Haversine formula.
     *
     * @param float $lat1 Latitude of point 1 (degrees)
     * @param float $lng1 Longitude of point 1 (degrees)
     * @param float $lat2 Latitude of point 2 (degrees)
     * @param float $lng2 Longitude of point 2 (degrees)
     * @return float Distance in meters
     */
    public static function haversineDistance($lat1, $lng1, $lat2, $lng2) {
        $earthRadiusM = 6371000; // Earth's radius in meters

        // Convert degrees to radians
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        // Haversine formula
        $a = sin($deltaLat / 2) * sin($deltaLat / 2)
           + cos($lat1Rad) * cos($lat2Rad)
           * sin($deltaLng / 2) * sin($deltaLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusM * $c;
    }

    /**
     * Determine whether a coordinate falls within a geofence boundary.
     *
     * @param float $userLat  Intern's latitude
     * @param float $userLng  Intern's longitude
     * @param float $orgLat   Organization geofence center latitude
     * @param float $orgLng   Organization geofence center longitude
     * @param int   $radiusM  Geofence radius in meters (default 100)
     * @return bool True if within geofence, false otherwise
     */
    public static function isWithinGeofence($userLat, $userLng, $orgLat, $orgLng, $radiusM = 100) {
        $distance = self::haversineDistance($userLat, $userLng, $orgLat, $orgLng);
        return $distance <= $radiusM;
    }

    /**
     * Check if an intern is within their assigned organization's geofence.
     * Looks up the organization from the database.
     *
     * @param PDO   $pdo       Database connection
     * @param int   $studentId User ID of the student
     * @param float $userLat   Intern's current latitude
     * @param float $userLng   Intern's current longitude
     * @return array ['within' => bool, 'distance' => float, 'organization' => array|null]
     */
    public static function checkStudentGeofence($pdo, $studentId, $userLat, $userLng) {
        // Get the student's organization
        $stmt = $pdo->prepare("
            SELECT o.* FROM organizations o
            JOIN users u ON u.organization_id = o.id
            WHERE u.id = ?
        ");
        $stmt->execute([$studentId]);
        $org = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$org) {
            return ['within' => false, 'distance' => null, 'organization' => null];
        }

        $distance = self::haversineDistance(
            $userLat, $userLng,
            $org['geo_latitude'], $org['geo_longitude']
        );

        $within = $distance <= $org['geofence_radius_m'];

        return [
            'within' => $within,
            'distance' => round($distance, 2),
            'organization' => $org
        ];
    }
}
?>
