<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../models/User.php';

// Check if user is logged in and has admin/coordinator role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'coordinator')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // Handle adding new student
        if (isset($_POST['action']) && $_POST['action'] === 'add_student') {
            try {
                // Validate required fields
                $requiredFields = ['name', 'email', 'matric_number', 'department', 'institution'];
                foreach ($requiredFields as $field) {
                    if (empty($_POST[$field])) {
                        throw new Exception("Field $field is required");
                    }
                }
                
                // Check if email already exists
                $existingUser = User::findByEmail($pdo, $_POST['email']);
                if ($existingUser) {
                    throw new Exception("Email already exists");
                }
                
                // Prepare student data
                $studentData = [
                    'name' => $_POST['name'],
                    'email' => $_POST['email'],
                    'matric_number' => $_POST['matric_number'],
                    'department' => $_POST['department'],
                    'institution' => $_POST['institution'],
                    'phone' => $_POST['phone'] ?? null,
                    'level' => $_POST['level'] ?? null,
                    'role' => 'student',
                    'password' => '12345678' // Default password, should be changed on first login
                ];
                
                // Create student
                $success = User::create($pdo, $studentData);
                
                if ($success) {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Student added successfully'
                    ]);
                } else {
                    throw new Exception("Failed to add student");
                }
                
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode([
                    'success' => false, 
                    'message' => $e->getMessage()
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        break;
        
    case 'GET':
        // Handle getting students list
        if (isset($_GET['action']) && $_GET['action'] === 'get_students') {
            try {
                $students = User::getAllStudents($pdo);
                echo json_encode([
                    'success' => true,
                    'data' => $students
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to fetch students'
                ]);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>