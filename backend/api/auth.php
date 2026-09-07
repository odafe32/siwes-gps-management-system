<?php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../models/User.php';

// Handle GET requests (for logout links)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'logout') {
        session_destroy();
        header('Location: ../../index.php');
        exit();
    }
}

// Handle form-based POST requests (non-JSON). JSON requests are handled below.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';

    // If this is a JSON request, skip this block and go to the JSON API section below
    if (strpos($content_type, 'application/json') === false) {
        $data = $_POST;
        $action = $data['action'] ?? '';
    
    if ($action === 'login') {
        $email = $data['email'] ?? $data['username'] ?? '';
        $matric_number = $data['matric_number'] ?? '';
        $password = $data['password'];
        $role = $data['role'];

        // Handle different login methods based on role
        if ($role === 'admin' || $role === 'coordinator') {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role IN ('admin', 'coordinator')");
            $stmt->execute([$email]);
        } elseif ($role === 'student') {
            // For students, use matric number instead of email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE matric_number = ? AND role = 'student'");
            $stmt->execute([$matric_number]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
            $stmt->execute([$email, $role]);
        }
        
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            
            // Set user name from username field
            $_SESSION['name'] = $user['username'] ?? $user['email'];
            
            // Redirect based on role
            switch ($role) {
                case 'student':
                    header('Location: ../../student/dashboard.php');
                    break;
                case 'supervisor':
                    header('Location: ../../supervisor/dashboard.php');
                    break;
                case 'coordinator':
                case 'admin':
                    header('Location: ../../admin/dashboard.php');
                    break;
                default:
                    header('Location: ../../index.php');
            }
            exit();
        } else {
            // Redirect back with error
            $error = urlencode('Invalid credentials');
            switch ($role) {
                case 'student':
                    header('Location: ../../student/login.php?error=' . $error);
                    break;
                case 'supervisor':
                    header('Location: ../../supervisor/login.php?error=' . $error);
                    break;
                case 'admin':
                    header('Location: ../../admin/login.php?error=' . $error);
                    break;
                default:
                    header('Location: ../../index.php?error=' . $error);
            }
            exit();
        }
    }
    
    if ($action === 'logout') {
        session_destroy();
        header('Location: ../../index.php');
        exit();
    }
    } // end non-JSON POST handling
}

// Handle JSON API requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if ($data['action'] === 'register') {
    // Registration logic (students only)
    $user = User::findByEmail($pdo, $data['email']);
    if ($user) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }
    
    $ok = User::create($pdo, [
        'name' => $data['name'],
        'email' => $data['email'],
        'matric_number' => $data['matric_number'],
        'department' => $data['department'],
        'institution' => $data['institution'],
        'password' => $data['password'],
        'role' => 'student'
    ]);
    
    if ($ok) {
        echo json_encode(['success' => true, 'message' => 'Registration successful']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed']);
    }
    exit;
}

if ($data['action'] === 'login') {
    $email = $data['email'] ?? $data['username'] ?? '';
    $matric_number = $data['matric_number'] ?? '';
    $password = $data['password'];
    $role = $data['role'];

    // Handle different login methods based on role
    if ($role === 'admin' || $role === 'coordinator') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role IN ('admin', 'coordinator')");
        $stmt->execute([$email]);
    } elseif ($role === 'student') {
        // For students, use matric number instead of email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE matric_number = ? AND role = 'student'");
        $stmt->execute([$matric_number]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$email, $role]);
    }
    
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['username'] ?? $user['email'];
        
        echo json_encode([
            'success' => true, 
            'role' => $user['role'],
            'name' => $user['username'] ?? $user['email'],
            'message' => 'Login successful'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }
    exit;
}

if ($data['action'] === 'logout') {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    exit;
}

if ($data['action'] === 'check_auth') {
    if (isLoggedIn()) {
        echo json_encode([
            'success' => true,
            'user_id' => $_SESSION['user_id'],
            'role' => $_SESSION['role'],
            'name' => $_SESSION['name']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>