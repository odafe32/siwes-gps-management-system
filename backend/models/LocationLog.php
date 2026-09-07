<?php
/**
 * LocationLog Model
 *
 * Stores periodic GPS coordinates captured from each intern's device,
 * together with a flag indicating whether the coordinate fell within
 * the assigned geofence.
 *
 * Thesis Table 3.3: Location Logs Table.
 */
class LocationLog {

    /**
     * Log a single GPS coordinate with geofence status
     *
     * @param PDO   $pdo
     * @param int   $internId   User ID of the student
     * @param float $latitude
     * @param float $longitude
     * @param bool  $inGeofence Whether this point is inside the geofence
     * @return int|false  Inserted row ID or false on failure
     */
    public static function log($pdo, $internId, $latitude, $longitude, $inGeofence) {
        $stmt = $pdo->prepare("
            INSERT INTO location_logs (intern_id, latitude, longitude, in_geofence)
            VALUES (?, ?, ?, ?)
        ");
        $ok = $stmt->execute([
            $internId,
            $latitude,
            $longitude,
            $inGeofence ? 1 : 0
        ]);
        return $ok ? $pdo->lastInsertId() : false;
    }

    /**
     * Get location history for a student, most recent first
     *
     * @param PDO   $pdo
     * @param int   $internId
     * @param int   $limit  Max number of records (default 50)
     * @return array
     */
    public static function getByIntern($pdo, $internId, $limit = 50) {
        $limit = (int) $limit;
        $stmt = $pdo->prepare("
            SELECT * FROM location_logs
            WHERE intern_id = ?
            ORDER BY captured_at DESC
            LIMIT $limit
        ");
        $stmt->execute([$internId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get the most recent location for a student
     *
     * @param PDO $pdo
     * @param int $internId
     * @return array|false
     */
    public static function getLatest($pdo, $internId) {
        $stmt = $pdo->prepare("
            SELECT * FROM location_logs
            WHERE intern_id = ?
            ORDER BY captured_at DESC
            LIMIT 1
        ");
        $stmt->execute([$internId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get location history for a student within a date range
     *
     * @param PDO   $pdo
     * @param int   $internId
     * @param string $startDate  Y-m-d
     * @param string $endDate    Y-m-d
     * @return array
     */
    public static function getByDateRange($pdo, $internId, $startDate, $endDate) {
        $stmt = $pdo->prepare("
            SELECT * FROM location_logs
            WHERE intern_id = ?
              AND DATE(captured_at) BETWEEN ? AND ?
            ORDER BY captured_at ASC
        ");
        $stmt->execute([$internId, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get the latest location for each student assigned to a supervisor
     *
     * @param PDO $pdo
     * @param int $supervisorId
     * @return array  Each row: student data + latest location
     */
    public static function getLatestForSupervisor($pdo, $supervisorId) {
        $stmt = $pdo->prepare("
            SELECT u.id, u.full_name, u.matric_number,
                   ll.latitude, ll.longitude, ll.captured_at, ll.in_geofence,
                   o.org_name, o.geo_latitude, o.geo_longitude, o.geofence_radius_m
            FROM users u
            LEFT JOIN location_logs ll ON ll.id = (
                SELECT id FROM location_logs
                WHERE intern_id = u.id
                ORDER BY captured_at DESC LIMIT 1
            )
            LEFT JOIN organizations o ON o.id = u.organization_id
            WHERE u.supervisor_id = ? AND u.role = 'student'
            ORDER BY u.full_name
        ");
        $stmt->execute([$supervisorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
