<?php
require_once 'backend/config/db.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully!\n";
    
    // Drop all existing tables
    $tables = ['sessions', 'notifications', 'evaluations', 'monthly_summaries', 'weekly_summaries', 'log_entries', 'students', 'supervisors', 'coordinators', 'users'];
    foreach ($tables as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
            echo "Dropped table: $table\n";
        } catch (Exception $e) {
            echo "Could not drop $table: " . $e->getMessage() . "\n";
        }
    }
    
    // Create users table
    $sql = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        role ENUM('student', 'supervisor', 'coordinator') NOT NULL,
        reference_id INT NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql);
    echo "Users table created successfully!\n";
    
    // Create students table
    $sql = "CREATE TABLE students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        matric_number VARCHAR(20) UNIQUE NOT NULL,
        department VARCHAR(100) NOT NULL,
        institution VARCHAR(100) NOT NULL,
        level VARCHAR(10) NOT NULL,
        siwes_start_date DATE NOT NULL,
        siwes_end_date DATE NOT NULL,
        workplace_name VARCHAR(200) NULL,
        supervisor_id INT NULL,
        coordinator_id INT NULL,
        phone VARCHAR(20) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql);
    echo "Students table created successfully!\n";
    
    // Create supervisors table
    $sql = "CREATE TABLE supervisors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(20) NULL,
        department VARCHAR(100) NULL,
        institution VARCHAR(100) NULL,
        position VARCHAR(100) NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql);
    echo "Supervisors table created successfully!\n";
    
    // Create coordinators table
    $sql = "CREATE TABLE coordinators (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(20) NULL,
        department VARCHAR(100) NULL,
        institution VARCHAR(100) NULL,
        position VARCHAR(100) NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql);
    echo "Coordinators table created successfully!\n";
    
    // Create log_entries table
    $sql = "CREATE TABLE log_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        activity TEXT NOT NULL,
        date DATE NOT NULL,
        workplace_name VARCHAR(200) NULL,
        latitude DECIMAL(10, 8) NULL,
        longitude DECIMAL(11, 8) NULL,
        file_path VARCHAR(500) NULL,
        file_name VARCHAR(255) NULL,
        file_size INT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        supervisor_comment TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql);
    echo "Log entries table created successfully!\n";
    
    // Create weekly_summaries table
    $sql = "CREATE TABLE weekly_summaries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        week_number INT NOT NULL,
        year INT NOT NULL,
        summary TEXT NOT NULL,
        achievements TEXT NULL,
        challenges TEXT NULL,
        next_week_plan TEXT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        supervisor_comment TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_week (student_id, week_number, year)
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql);
    echo "Weekly summaries table created successfully!\n";
    
    // Create monthly_summaries table
    $sql = "CREATE TABLE monthly_summaries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        month INT NOT NULL,
        year INT NOT NULL,
        summary TEXT NOT NULL,
        key_achievements TEXT NULL,
        skills_developed TEXT NULL,
        challenges_faced TEXT NULL,
        next_month_goals TEXT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        supervisor_comment TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_month (student_id, month, year)
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql);
    echo "Monthly summaries table created successfully!\n";
    
    // Create evaluations table
    $sql = "CREATE TABLE evaluations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        supervisor_id INT NOT NULL,
        evaluation_type ENUM('weekly', 'monthly', 'final') NOT NULL,
        period_start DATE NULL,
        period_end DATE NULL,
        technical_skills INT NULL,
        communication_skills INT NULL,
        teamwork INT NULL,
        initiative INT NULL,
        attendance INT NULL,
        overall_rating INT NULL,
        comments TEXT NULL,
        recommendations TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql);
    echo "Evaluations table created successfully!\n";
    
    // Create notifications table
    $sql = "CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
        is_read BOOLEAN DEFAULT FALSE,
        related_id INT NULL,
        related_type VARCHAR(50) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql);
    echo "Notifications table created successfully!\n";
    
    // Create sessions table
    $sql = "CREATE TABLE sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        session_token VARCHAR(255) UNIQUE NOT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NULL
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql);
    echo "Sessions table created successfully!\n";
    
    // Insert test data
    
    // Insert test coordinator
    $stmt = $pdo->prepare("INSERT INTO coordinators (full_name, email, phone, department, institution, position) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['Dr. Coordinator Admin', 'coordinator@siwes.com', '08012345678', 'Computer Science', 'University of Nigeria', 'SIWES Coordinator']);
    $coordinatorId = $pdo->lastInsertId();
    echo "Test coordinator created successfully!\n";
    
    // Insert test supervisor
    $stmt = $pdo->prepare("INSERT INTO supervisors (full_name, email, phone, department, institution, position) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['Dr. Jane Smith', 'jane.smith@techsolutions.com', '08023456789', 'Computer Science', 'University of Nigeria', 'Senior Lecturer']);
    $supervisorId = $pdo->lastInsertId();
    echo "Test supervisor created successfully!\n";
    
    // Insert test student
    $stmt = $pdo->prepare("INSERT INTO students (full_name, matric_number, department, institution, level, siwes_start_date, siwes_end_date, workplace_name, supervisor_id, coordinator_id, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute(['John Doe', 'STU001', 'Computer Science', 'University of Nigeria', '400', '2026-01-15', '2026-06-15', 'Tech Solutions Ltd', $supervisorId, $coordinatorId, '08034567890']);
    $studentId = $pdo->lastInsertId();
    echo "Test student created successfully!\n";
    
    // Insert users for authentication
    $users = [
        ['username' => 'student1', 'password' => password_hash('12345678', PASSWORD_DEFAULT), 'email' => 'student1@example.com', 'role' => 'student', 'reference_id' => $studentId],
        ['username' => 'supervisor1', 'password' => password_hash('12345678', PASSWORD_DEFAULT), 'email' => 'supervisor1@example.com', 'role' => 'supervisor', 'reference_id' => $supervisorId],
        ['username' => 'coordinator1', 'password' => password_hash('12345678', PASSWORD_DEFAULT), 'email' => 'coordinator1@example.com', 'role' => 'coordinator', 'reference_id' => $coordinatorId]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, reference_id) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($users as $user) {
        $stmt->execute([
            $user['username'],
            $user['password'],
            $user['email'],
            $user['role'],
            $user['reference_id']
        ]);
    }
    
    echo "Test users created successfully!\n";
    
    // Insert sample log entries for student
    $sampleEntries = [
        [
            'activity' => 'Today I worked on developing a web application using PHP and MySQL. I learned about database design and implemented user authentication features. The day was productive and I gained valuable experience in full-stack development.',
            'date' => '2026-01-15',
            'workplace_name' => 'Tech Solutions Ltd',
            'latitude' => 6.5244,
            'longitude' => 3.3792
        ],
        [
            'activity' => 'Continued working on the web application project. Today I focused on implementing the frontend using HTML, CSS, and JavaScript. I also learned about responsive design principles and Bootstrap framework.',
            'date' => '2026-01-16',
            'workplace_name' => 'Tech Solutions Ltd',
            'latitude' => 6.5244,
            'longitude' => 3.3792
        ],
        [
            'activity' => 'Attended a team meeting where we discussed project requirements and timelines. I presented my progress on the web application and received feedback from senior developers. This helped me understand industry best practices.',
            'date' => '2026-01-17',
            'workplace_name' => 'Tech Solutions Ltd',
            'latitude' => 6.5244,
            'longitude' => 3.3792
        ]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO log_entries (student_id, activity, date, workplace_name, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($sampleEntries as $entry) {
        $stmt->execute([
            $studentId,
            $entry['activity'],
            $entry['date'],
            $entry['workplace_name'],
            $entry['latitude'],
            $entry['longitude']
        ]);
    }
    
    echo "Sample log entries created successfully!\n";
    
    echo "\n=== DATABASE SETUP COMPLETE ===\n";
    echo "Test credentials:\n";
    echo "Student: username=student1, password=12345678\n";
    echo "Supervisor: username=supervisor1, password=12345678\n";
    echo "Coordinator: username=coordinator1, password=12345678\n";
    echo "\nYou can now access the system at: http://localhost:8000\n";
    
} catch(PDOException $e) {
    echo "Database setup failed: " . $e->getMessage() . "\n";
}
?> 