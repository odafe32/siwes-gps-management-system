<?php
require_once 'backend/config/db.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully!\n";
    
    // Drop existing tables if they exist
    $tables = ['sessions', 'notifications', 'evaluations', 'monthly_summaries', 'weekly_summaries', 'log_entries', 'users'];
    foreach ($tables as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS $table");
            echo "Dropped table: $table\n";
        } catch (Exception $e) {
            // Table might not exist, continue
        }
    }
    
    // Create users table
    $sql = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('student', 'supervisor', 'admin') NOT NULL,
        student_id VARCHAR(20) NULL,
        department VARCHAR(100) NULL,
        institution VARCHAR(100) NULL,
        siwes_start_date DATE NULL,
        siwes_end_date DATE NULL,
        workplace_name VARCHAR(200) NULL,
        supervisor_name VARCHAR(100) NULL,
        supervisor_email VARCHAR(100) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql);
    echo "Users table created successfully!\n";
    
    // Create log_entries table
    $sql = "CREATE TABLE log_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql);
    echo "Log entries table created successfully!\n";
    
    // Create weekly_summaries table
    $sql = "CREATE TABLE weekly_summaries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        week_number INT NOT NULL,
        year INT NOT NULL,
        summary TEXT NOT NULL,
        achievements TEXT NULL,
        challenges TEXT NULL,
        next_week_plan TEXT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        supervisor_comment TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_week (user_id, week_number, year)
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql);
    echo "Weekly summaries table created successfully!\n";
    
    // Create monthly_summaries table
    $sql = "CREATE TABLE monthly_summaries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
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
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_month (user_id, month, year)
    ) ENGINE=InnoDB";
    
    $pdo->exec($sql);
    echo "Monthly summaries table created successfully!\n";
    
    // Create evaluations table
    $sql = "CREATE TABLE evaluations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        evaluator_id INT NOT NULL,
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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
    
    // Now add foreign key constraints
    echo "Adding foreign key constraints...\n";
    
    $foreignKeys = [
        "ALTER TABLE log_entries ADD CONSTRAINT fk_log_entries_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE weekly_summaries ADD CONSTRAINT fk_weekly_summaries_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE monthly_summaries ADD CONSTRAINT fk_monthly_summaries_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE evaluations ADD CONSTRAINT fk_evaluations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE evaluations ADD CONSTRAINT fk_evaluations_evaluator FOREIGN KEY (evaluator_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE notifications ADD CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
        "ALTER TABLE sessions ADD CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE"
    ];
    
    foreach ($foreignKeys as $fk) {
        try {
            $pdo->exec($fk);
            echo "Added foreign key constraint successfully\n";
        } catch (Exception $e) {
            echo "Warning: Could not add foreign key: " . $e->getMessage() . "\n";
        }
    }
    
    // Insert test users
    $testUsers = [
        [
            'username' => 'student1',
            'password' => password_hash('12345678', PASSWORD_DEFAULT),
            'email' => 'student1@example.com',
            'full_name' => 'John Doe',
            'role' => 'student',
            'student_id' => 'STU001',
            'department' => 'Computer Science',
            'institution' => 'University of Nigeria',
            'siwes_start_date' => '2026-01-15',
            'siwes_end_date' => '2026-06-15',
            'workplace_name' => 'Tech Solutions Ltd',
            'supervisor_name' => 'Dr. Jane Smith',
            'supervisor_email' => 'jane.smith@techsolutions.com'
        ],
        [
            'username' => 'supervisor1',
            'password' => password_hash('12345678', PASSWORD_DEFAULT),
            'email' => 'supervisor1@example.com',
            'full_name' => 'Dr. Jane Smith',
            'role' => 'supervisor',
            'department' => 'Computer Science',
            'institution' => 'University of Nigeria'
        ],
        [
            'username' => 'admin',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'email' => 'admin@siwes.com',
            'full_name' => 'System Administrator',
            'role' => 'admin'
        ]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role, student_id, department, institution, siwes_start_date, siwes_end_date, workplace_name, supervisor_name, supervisor_email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($testUsers as $user) {
        $stmt->execute([
            $user['username'],
            $user['password'],
            $user['email'],
            $user['full_name'],
            $user['role'],
            $user['student_id'] ?? null,
            $user['department'] ?? null,
            $user['institution'] ?? null,
            $user['siwes_start_date'] ?? null,
            $user['siwes_end_date'] ?? null,
            $user['workplace_name'] ?? null,
            $user['supervisor_name'] ?? null,
            $user['supervisor_email'] ?? null
        ]);
    }
    
    echo "Test users created successfully!\n";
    
    // Insert sample log entries for student1
    $studentId = $pdo->query("SELECT id FROM users WHERE username = 'student1'")->fetchColumn();
    
    if ($studentId) {
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
        
        $stmt = $pdo->prepare("INSERT INTO log_entries (user_id, activity, date, workplace_name, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?)");
        
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
    }
    
    echo "\n=== DATABASE SETUP COMPLETE ===\n";
    echo "Test credentials:\n";
    echo "Student: username=student1, password=12345678\n";
    echo "Supervisor: username=supervisor1, password=12345678\n";
    echo "Admin: username=admin, password=admin123\n";
    echo "\nYou can now access the system at: http://localhost:8000\n";
    
} catch(PDOException $e) {
    echo "Database setup failed: " . $e->getMessage() . "\n";
}
?> 