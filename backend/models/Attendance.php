<?php
/**
 * Attendance Model
 *
 * Stores daily check-in/check-out times and overall attendance status
 * for each intern. Status: Present / Absent / Outside Zone.
 *
 * Thesis Table 3.4: Attendance Table.
 */
class Attendance {

    /**
     * Get attendance record for a student on a specific date
     *
     * @param PDO    $pdo
     * @param int    $internId
     * @param string $date  Y-m-d
     * @return array|false
     */
    public static function getByDate($pdo, $internId, $date) {
        $stmt = $pdo->prepare("
            SELECT * FROM attendance
            WHERE intern_id = ? AND date = ?
        ");
        $stmt->execute([$internId, $date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get today's attendance for a student
     */
    public static function getToday($pdo, $internId) {
        return self::getByDate($pdo, $internId, date('Y-m-d'));
    }

    /**
     * Check in a student.
     * Creates a new attendance record for today (or updates existing).
     *
     * @param PDO   $pdo
     * @param int   $internId
     * @param bool  $withinGeofence  Whether the student is inside the geofence
     * @return array  The attendance record
     */
    public static function checkIn($pdo, $internId, $withinGeofence) {
        $today = date('Y-m-d');
        $now = date('H:i:s');
        $status = $withinGeofence ? 'Present' : 'Outside Zone';

        $existing = self::getByDate($pdo, $internId, $today);

        if ($existing) {
            // Update existing record
            $stmt = $pdo->prepare("
                UPDATE attendance
                SET check_in_time = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([$now, $status, $existing['id']]);
        } else {
            // Create new record
            $stmt = $pdo->prepare("
                INSERT INTO attendance (intern_id, date, check_in_time, status)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$internId, $today, $now, $status]);
        }

        return self::getByDate($pdo, $internId, $today);
    }

    /**
     * Check out a student
     *
     * @param PDO   $pdo
     * @param int   $internId
     * @param bool  $withinGeofence  Whether the student is inside the geofence at checkout
     * @return array|false  The updated attendance record
     */
    public static function checkOut($pdo, $internId, $withinGeofence = null) {
        $today = date('Y-m-d');
        $now = date('H:i:s');

        $existing = self::getByDate($pdo, $internId, $today);

        if (!$existing) {
            // No check-in was recorded — mark as Absent
            $stmt = $pdo->prepare("
                INSERT INTO attendance (intern_id, date, check_out_time, status)
                VALUES (?, ?, ?, 'Absent')
            ");
            $stmt->execute([$internId, $today, $now]);
        } else {
            // Update checkout time
            $status = $existing['status'];
            // If they were present but leave outside geofence during work hours,
            // mark as Outside Zone (breach handled separately via notifications)
            if ($withinGeofence === false && $status === 'Present') {
                $status = 'Outside Zone';
            }
            $stmt = $pdo->prepare("
                UPDATE attendance
                SET check_out_time = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([$now, $status, $existing['id']]);
        }

        return self::getByDate($pdo, $internId, $today);
    }

    /**
     * Get attendance history for a student
     *
     * @param PDO   $pdo
     * @param int   $internId
     * @param int   $limit  Max records (default 30)
     * @return array
     */
    public static function getHistory($pdo, $internId, $limit = 30) {
        $limit = (int) $limit;
        $stmt = $pdo->prepare("
            SELECT * FROM attendance
            WHERE intern_id = ?
            ORDER BY date DESC
            LIMIT $limit
        ");
        $stmt->execute([$internId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get attendance for all students assigned to a supervisor for a date
     *
     * @param PDO    $pdo
     * @param int    $supervisorId
     * @param string $date  Y-m-d (default: today)
     * @return array
     */
    public static function getSupervisorAttendance($pdo, $supervisorId, $date = null) {
        $date = $date ?: date('Y-m-d');
        $stmt = $pdo->prepare("
            SELECT u.id, u.full_name, u.matric_number,
                   a.check_in_time, a.check_out_time, a.status
            FROM users u
            LEFT JOIN attendance a ON a.intern_id = u.id AND a.date = ?
            WHERE u.supervisor_id = ? AND u.role = 'student'
            ORDER BY u.full_name
        ");
        $stmt->execute([$date, $supervisorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get attendance summary counts for a date
     *
     * @param PDO    $pdo
     * @param int    $supervisorId
     * @param string $date  Y-m-d
     * @return array  ['present' => n, 'absent' => n, 'outside_zone' => n, 'total' => n]
     */
    public static function getSummary($pdo, $supervisorId, $date = null) {
        $date = $date ?: date('Y-m-d');
        $records = self::getSupervisorAttendance($pdo, $supervisorId, $date);

        $summary = ['present' => 0, 'absent' => 0, 'outside_zone' => 0, 'total' => count($records)];
        foreach ($records as $r) {
            if (!$r['status'] || $r['status'] === 'Absent') {
                $summary['absent']++;
            } elseif ($r['status'] === 'Present') {
                $summary['present']++;
            } elseif ($r['status'] === 'Outside Zone') {
                $summary['outside_zone']++;
            }
        }
        return $summary;
    }
}
?>
