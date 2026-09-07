<?php
/**
 * Organization Model
 *
 * Represents a host organization with geofence data.
 * Thesis Table 3.2: Organizations table.
 */
class Organization {

    /**
     * Find an organization by ID
     */
    public static function find($pdo, $id) {
        $stmt = $pdo->prepare("SELECT * FROM organizations WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all organizations
     */
    public static function all($pdo) {
        return $pdo->query("SELECT * FROM organizations ORDER BY org_name")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new organization
     *
     * @param PDO   $pdo
     * @param array $data  ['org_name', 'address', 'geo_latitude', 'geo_longitude', 'geofence_radius_m']
     * @return int|false   New organization ID or false on failure
     */
    public static function create($pdo, $data) {
        $stmt = $pdo->prepare("
            INSERT INTO organizations (org_name, address, geo_latitude, geo_longitude, geofence_radius_m)
            VALUES (?, ?, ?, ?, ?)
        ");
        $ok = $stmt->execute([
            $data['org_name'],
            $data['address'] ?? null,
            $data['geo_latitude'],
            $data['geo_longitude'],
            $data['geofence_radius_m'] ?? 100
        ]);
        return $ok ? $pdo->lastInsertId() : false;
    }

    /**
     * Update an organization
     */
    public static function update($pdo, $id, $data) {
        $stmt = $pdo->prepare("
            UPDATE organizations
            SET org_name = ?, address = ?, geo_latitude = ?, geo_longitude = ?, geofence_radius_m = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['org_name'],
            $data['address'] ?? null,
            $data['geo_latitude'],
            $data['geo_longitude'],
            $data['geofence_radius_m'] ?? 100,
            $id
        ]);
    }

    /**
     * Delete an organization
     */
    public static function delete($pdo, $id) {
        // Unlink students/supervisors first
        $pdo->prepare("UPDATE users SET organization_id = NULL WHERE organization_id = ?")->execute([$id]);
        $stmt = $pdo->prepare("DELETE FROM organizations WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Get all students assigned to an organization
     */
    public static function getStudents($pdo, $orgId) {
        $stmt = $pdo->prepare("
            SELECT u.* FROM users u
            WHERE u.organization_id = ? AND u.role = 'student'
            ORDER BY u.full_name
        ");
        $stmt->execute([$orgId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all supervisors assigned to an organization
     */
    public static function getSupervisors($pdo, $orgId) {
        $stmt = $pdo->prepare("
            SELECT u.* FROM users u
            WHERE u.organization_id = ? AND u.role = 'supervisor'
            ORDER BY u.full_name
        ");
        $stmt->execute([$orgId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count organizations
     */
    public static function count($pdo) {
        return (int) $pdo->query("SELECT COUNT(*) FROM organizations")->fetchColumn();
    }
}
?>
