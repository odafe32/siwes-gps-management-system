<?php
$host = 'localhost:3306'; // Adjust port if necessary
$dbname = 'siwes_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // If database doesn't exist, create it
    if ($e->getCode() == 1049) {
        try {
            $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("CREATE DATABASE $dbname");
            $pdo->exec("USE $dbname");

            // Create users table with all columns the application references.
            // The codebase uses name, username, AND full_name in different files,
            // so all three are included. role includes 'coordinator' (auth.php/admin.php).
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
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            ");

            // Create log_entries table matching LogEntry model and student/log-entry.php.
            // Uses: activity (not activity_description), location_address, accuracy, altitude, heading, speed.
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

            // Create weekly_summaries table matching WeeklySummary model.
            // Model inserts status, supervisor_id, supervisor_comment.
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

            // Create monthly_summaries table matching MonthlySummary model.
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

            // Create evaluations table matching Evaluation model.
            // Model uses: evaluation_type, period_reference, punctuality_rating,
            // technical_skill_rating, communication_rating, attitude_rating,
            // final_recommendation, digital_signature.
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

            // Create notifications table matching Notification model.
            $pdo->exec("
                CREATE TABLE notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
                    is_read BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");

            // Insert test data
            $hashedPassword = password_hash('12345678', PASSWORD_DEFAULT);

            // Test student
            $pdo->exec("
                INSERT INTO users (name, username, full_name, email, password, role, matric_number, student_id, department, institution, siwes_start_date, siwes_end_date, workplace_name, workplace_address, workplace_latitude, workplace_longitude, phone, level)
                VALUES ('John Doe', 'johndoe', 'John Doe', 'student@test.com', '$hashedPassword', 'student', '2021/123456', 'STU001', 'Computer Science', 'University of Nigeria', '2026-01-15', '2026-06-15', 'Tech Solutions Ltd', '123 Business District, Lagos, Nigeria', 6.5244, 3.3792, '08034567890', '400')
            ");

            // Test supervisor
            $pdo->exec("
                INSERT INTO users (name, username, full_name, email, password, role, department, institution, phone)
                VALUES ('Dr. Jane Smith', 'janesmith', 'Dr. Jane Smith', 'supervisor@test.com', '$hashedPassword', 'supervisor', 'Computer Science', 'University of Nigeria', '08023456789')
            ");

            // Test admin
            $pdo->exec("
                INSERT INTO users (name, username, full_name, email, password, role, department, institution)
                VALUES ('Admin User', 'admin', 'Admin User', 'admin@test.com', '$hashedPassword', 'admin', 'IT Department', 'University of Nigeria')
            ");

            // Assign the test student to the test supervisor
            $pdo->exec("UPDATE users SET supervisor_id = (SELECT id FROM (SELECT id FROM users WHERE email = 'supervisor@test.com') tmp) WHERE email = 'student@test.com'");

            echo "Database and tables created successfully with test data!\n";

        } catch(PDOException $e) {
            die("Error creating database: " . $e->getMessage());
        }
    } else {
        die("Connection failed: " . $e->getMessage());
    }
}
?>
