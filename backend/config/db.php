<?php
$host = 'localhost:3306';
$dbname = 'siwes_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // If the database already exists, ensure new tables/columns are added (Phase 1 upgrade)
    _ensure_schema($pdo);

} catch(PDOException $e) {
    // If database doesn't exist, create it from scratch
    if ($e->getCode() == 1049) {
        try {
            $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("CREATE DATABASE $dbname");
            $pdo->exec("USE $dbname");
            _create_all_tables($pdo);
            _seed_test_data($pdo);
            echo "Database and tables created successfully with test data!\n";
        } catch(PDOException $e2) {
            die("Error creating database: " . $e2->getMessage());
        }
    } else {
        die("Connection failed: " . $e->getMessage());
    }
}

/**
 * Create all tables from scratch (fresh database)
 */
function _create_all_tables($pdo) {
    // ===== USERS =====
    $pdo->exec("
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255),
            username VARCHAR(255),
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('student', 'supervisor', 'coordinator', 'admin') NOT NULL,
            matric_number VARCHAR(50),
            student_id VARCHAR(50),
            department VARCHAR(255),
            institution VARCHAR(255),
            siwes_start_date DATE,
            siwes_end_date DATE,
            workplace_name VARCHAR(255),
            workplace_latitude DECIMAL(10,8),
            workplace_longitude DECIMAL(11,8),
            workplace_address VARCHAR(255),
            phone VARCHAR(20),
            level VARCHAR(10),
            is_active TINYINT(1) DEFAULT 1,
            supervisor_id INT,
            reference_id INT,
            organization_id INT,
            supervisor_type ENUM('industry', 'school'),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // ===== ORGANIZATIONS (Phase 1 — thesis Table 3.2) =====
    $pdo->exec("
        CREATE TABLE organizations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            org_name VARCHAR(150) NOT NULL,
            address VARCHAR(255),
            geo_latitude DECIMAL(10,7) NOT NULL,
            geo_longitude DECIMAL(10,7) NOT NULL,
            geofence_radius_m INT DEFAULT 100,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // ===== LOG_ENTRIES =====
    $pdo->exec("
        CREATE TABLE log_entries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            activity TEXT NOT NULL,
            date DATE NOT NULL,
            latitude DECIMAL(10, 8),
            longitude DECIMAL(11, 8),
            location_address VARCHAR(255),
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            accuracy DECIMAL(10,2),
            altitude DECIMAL(10,2),
            heading DECIMAL(10,2),
            speed DECIMAL(10,2),
            file_upload VARCHAR(255),
            supervisor_comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // ===== WEEKLY_SUMMARIES =====
    $pdo->exec("
        CREATE TABLE weekly_summaries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            week_number INT NOT NULL,
            summary_of_work TEXT NOT NULL,
            problems_encountered TEXT,
            suggestions_for_improvement TEXT,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            supervisor_id INT,
            supervisor_comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_week_summary (student_id, week_number)
        )
    ");

    // ===== MONTHLY_SUMMARIES =====
    $pdo->exec("
        CREATE TABLE monthly_summaries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            month_number INT NOT NULL,
            learning_outcomes TEXT NOT NULL,
            innovations_initiatives TEXT,
            general_reflections TEXT,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            supervisor_id INT,
            supervisor_comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_month_summary (student_id, month_number)
        )
    ");

    // ===== EVALUATIONS =====
    $pdo->exec("
        CREATE TABLE evaluations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            supervisor_id INT NOT NULL,
            evaluation_type ENUM('weekly', 'monthly', 'final') NOT NULL,
            period_reference VARCHAR(50),
            punctuality_rating INT,
            technical_skill_rating INT,
            communication_rating INT,
            attitude_rating INT,
            final_recommendation TEXT,
            digital_signature VARCHAR(255),
            comments TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (supervisor_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // ===== NOTIFICATIONS (updated with geofence_breach type) =====
    $pdo->exec("
        CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('info', 'success', 'warning', 'error', 'geofence_breach') DEFAULT 'info',
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // ===== LOCATION_LOGS (Phase 1 — thesis Table 3.3) =====
    $pdo->exec("
        CREATE TABLE location_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            intern_id INT NOT NULL,
            latitude DECIMAL(10,7) NOT NULL,
            longitude DECIMAL(10,7) NOT NULL,
            captured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            in_geofence BOOLEAN NOT NULL,
            FOREIGN KEY (intern_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // ===== ATTENDANCE (Phase 1 — thesis Table 3.4) =====
    $pdo->exec("
        CREATE TABLE attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            intern_id INT NOT NULL,
            date DATE NOT NULL,
            check_in_time TIME,
            check_out_time TIME,
            status VARCHAR(30) DEFAULT 'Absent',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (intern_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_attendance (intern_id, date)
        )
    ");
}

/**
 * Seed test data (fresh database)
 */
function _seed_test_data($pdo) {
    $hashedPassword = password_hash('12345678', PASSWORD_DEFAULT);

    // Test organization (thesis: host organization with geofence)
    $pdo->exec("
        INSERT INTO organizations (org_name, address, geo_latitude, geo_longitude, geofence_radius_m)
        VALUES ('Tech Solutions Ltd', '123 Business District, Lagos, Nigeria', 6.5244, 3.3792, 100)
    ");
    $orgId = $pdo->lastInsertId();

    // Test student — linked to organization
    $pdo->exec("
        INSERT INTO users (name, username, full_name, email, password, role, matric_number, student_id, department, institution, siwes_start_date, siwes_end_date, workplace_name, workplace_address, workplace_latitude, workplace_longitude, phone, level, organization_id)
        VALUES ('John Doe', 'johndoe', 'John Doe', 'student@test.com', '$hashedPassword', 'student', '2021/123456', 'STU001', 'Computer Science', 'University of Nigeria', '2026-01-15', '2026-06-15', 'Tech Solutions Ltd', '123 Business District, Lagos, Nigeria', 6.5244, 3.3792, '08034567890', '400', $orgId)
    ");
    $studentId = $pdo->lastInsertId();

    // Test supervisor — school-based, linked to the same organization
    $pdo->exec("
        INSERT INTO users (name, username, full_name, email, password, role, department, institution, phone, supervisor_type, organization_id)
        VALUES ('Dr. Jane Smith', 'janesmith', 'Dr. Jane Smith', 'supervisor@test.com', '$hashedPassword', 'supervisor', 'Computer Science', 'University of Nigeria', '08023456789', 'school', $orgId)
    ");
    $supervisorId = $pdo->lastInsertId();

    // Test admin
    $pdo->exec("
        INSERT INTO users (name, username, full_name, email, password, role, department, institution)
        VALUES ('Admin User', 'admin', 'Admin User', 'admin@test.com', '$hashedPassword', 'admin', 'IT Department', 'University of Nigeria')
    ");

    // Assign the test student to the test supervisor
    $pdo->exec("UPDATE users SET supervisor_id = $supervisorId WHERE id = $studentId");
}

/**
 * Ensure existing database has all Phase 1 tables/columns.
 * This runs every time db.php is loaded — it's idempotent (safe to run repeatedly).
 */
function _ensure_schema($pdo) {
    // --- Add columns to users if they don't exist ---
    _add_column_if_missing($pdo, 'users', 'organization_id', "INT");
    _add_column_if_missing($pdo, 'users', 'supervisor_type', "ENUM('industry', 'school')");

    // --- Create organizations table if it doesn't exist ---
    _create_table_if_missing($pdo, 'organizations', "
        CREATE TABLE organizations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            org_name VARCHAR(150) NOT NULL,
            address VARCHAR(255),
            geo_latitude DECIMAL(10,7) NOT NULL,
            geo_longitude DECIMAL(10,7) NOT NULL,
            geofence_radius_m INT DEFAULT 100,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // --- Create location_logs table if it doesn't exist ---
    _create_table_if_missing($pdo, 'location_logs', "
        CREATE TABLE location_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            intern_id INT NOT NULL,
            latitude DECIMAL(10,7) NOT NULL,
            longitude DECIMAL(10,7) NOT NULL,
            captured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            in_geofence BOOLEAN NOT NULL,
            FOREIGN KEY (intern_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // --- Create attendance table if it doesn't exist ---
    _create_table_if_missing($pdo, 'attendance', "
        CREATE TABLE attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            intern_id INT NOT NULL,
            date DATE NOT NULL,
            check_in_time TIME,
            check_out_time TIME,
            status VARCHAR(30) DEFAULT 'Absent',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (intern_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_attendance (intern_id, date)
        )
    ");

    // --- Update notifications type enum to include geofence_breach ---
    try {
        $pdo->exec("ALTER TABLE notifications MODIFY COLUMN type ENUM('info', 'success', 'warning', 'error', 'geofence_breach') DEFAULT 'info'");
    } catch (PDOException $e) { /* ignore if already updated */ }

    // --- Seed test organization if none exists ---
    $count = $pdo->query("SELECT COUNT(*) FROM organizations")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("
            INSERT INTO organizations (org_name, address, geo_latitude, geo_longitude, geofence_radius_m)
            VALUES ('Tech Solutions Ltd', '123 Business District, Lagos, Nigeria', 6.5244, 3.3792, 100)
        ");
        $orgId = $pdo->lastInsertId();

        // Link existing test student to the org
        $pdo->exec("UPDATE users SET organization_id = $orgId WHERE email = 'student@test.com'");
        // Link existing test supervisor to the org and set type
        $pdo->exec("UPDATE users SET organization_id = $orgId, supervisor_type = 'school' WHERE email = 'supervisor@test.com'");
    }
}

/**
 * Helper: add a column to a table if it doesn't already exist
 */
function _add_column_if_missing($pdo, $table, $column, $definition) {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        }
    } catch (PDOException $e) { /* table might not exist yet — ignore */ }
}

/**
 * Helper: create a table if it doesn't already exist
 */
function _create_table_if_missing($pdo, $table, $ddl) {
    try {
        $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
    } catch (PDOException $e) {
        $pdo->exec($ddl);
    }
}
?>
