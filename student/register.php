<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $matric_number = trim($_POST['matric_number'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $institution = trim($_POST['institution'] ?? '');
    $siwes_start_date = trim($_POST['siwes_start_date'] ?? '');
    $siwes_end_date = trim($_POST['siwes_end_date'] ?? '');
    $workplace_name = trim($_POST['workplace_name'] ?? '');
    
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = 'Please fill in all required fields.';
        $messageType = 'error';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match.';
        $messageType = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long.';
        $messageType = 'error';
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $message = 'Email address already registered.';
                $messageType = 'error';
            } else {
                // Create new student account
                $userData = [
                    'name' => $name,
                    'email' => $email,
                    'matric_number' => $matric_number,
                    'student_id' => $student_id,
                    'department' => $department,
                    'institution' => $institution,
                    'siwes_start_date' => $siwes_start_date,
                    'siwes_end_date' => $siwes_end_date,
                    'workplace_name' => $workplace_name,
                    'password' => $password,
                    'role' => 'student'
                ];
                
                require_once '../backend/models/User.php';
                if (User::create($pdo, $userData)) {
                    $message = 'Registration successful! You can now login.';
                    $messageType = 'success';
                    // Clear form data
                    $_POST = array();
                } else {
                    $message = 'Registration failed. Please try again.';
                    $messageType = 'error';
                }
            }
        } catch (PDOException $e) {
            $message = 'Registration failed. Please try again.';
            $messageType = 'error';
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - SIWES Logbook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/student-styles.css">
    
    <style>
        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .register-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 450px;
            padding: 2rem;
        }
        
        .register-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .register-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }
        
        .logo-section {
            margin-bottom: 2rem;
        }
        
        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 2rem;
        }
        
        .register-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .register-subtitle {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(26, 77, 46, 0.25);
        }
        
        .input-group-text {
            background: transparent;
            border: 2px solid #e9ecef;
            border-right: none;
            color: var(--text-secondary);
        }
        
        .input-group .form-control {
            border-left: none;
        }
        
        .input-group .form-control:focus + .input-group-text {
            border-color: var(--primary-color);
        }
        
        .btn-register {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            color: white;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(26, 77, 46, 0.3);
            color: white;
        }
        
        .btn-register:active {
            transform: translateY(0);
        }
        
        .divider {
            margin: 1.5rem 0;
            text-align: center;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e9ecef;
        }
        
        .divider span {
            background: white;
            padding: 0 1rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .login-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .login-link:hover {
            color: var(--secondary-color);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            font-weight: 500;
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border-left: 4px solid #dc3545;
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border-left: 4px solid #28a745;
        }
        
        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
        }
        
        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }
        
        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }
        
        @media (max-width: 768px) {
            .register-container {
                padding: 1rem;
            }
            
            .register-card {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Floating shapes for background -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
                    </div>
                    
    <div class="register-container">
        <div class="register-card">
            <div class="logo-section">
                <div class="logo-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1 class="register-title">Student Registration</h1>
                <p class="register-subtitle">Create your SIWES Logbook account</p>
                        </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> mb-3">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    
            <form method="POST" id="registerForm">
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" class="form-control" name="name" placeholder="Full name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" class="form-control" name="email" placeholder="Email address" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-id-card"></i>
                        </span>
                        <input type="text" class="form-control" name="matric_number" placeholder="Matriculation Number" value="<?php echo htmlspecialchars($_POST['matric_number'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-id-badge"></i>
                        </span>
                        <input type="text" class="form-control" name="student_id" placeholder="Student ID" value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-building"></i>
                        </span>
                        <input type="text" class="form-control" name="department" placeholder="Department" value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-university"></i>
                        </span>
                        <input type="text" class="form-control" name="institution" placeholder="Institution" value="<?php echo htmlspecialchars($_POST['institution'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-calendar-alt"></i>
                        </span>
                        <input type="date" class="form-control" name="siwes_start_date" placeholder="SIWES Start Date" value="<?php echo htmlspecialchars($_POST['siwes_start_date'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-calendar-alt"></i>
                        </span>
                        <input type="date" class="form-control" name="siwes_end_date" placeholder="SIWES End Date" value="<?php echo htmlspecialchars($_POST['siwes_end_date'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-briefcase"></i>
                        </span>
                        <input type="text" class="form-control" name="workplace_name" placeholder="Workplace Name" value="<?php echo htmlspecialchars($_POST['workplace_name'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" name="password" placeholder="Password" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" name="confirm_password" placeholder="Confirm password" required>
                    </div>
                </div>
                        
                <button type="submit" class="btn btn-register">
                    <i class="fas fa-user-plus me-2"></i>
                    Create Account
                            </button>
                    </form>
                    
            <div class="divider">
                <span>Already have an account?</span>
            </div>
            
            <p class="text-center mb-0">
                <a href="login.php" class="login-link">
                    <i class="fas fa-sign-in-alt me-1"></i>
                    Sign In
                </a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/student-scripts.js"></script>
    <script>
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const name = document.querySelector('input[name="name"]').value.trim();
            const email = document.querySelector('input[name="email"]').value.trim();
            const password = document.querySelector('input[name="password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            
            if (!name || !email || !password || !confirmPassword) {
                e.preventDefault();
                showToast('Please fill in all fields', 'error');
                return;
            }
            
            if (!email.includes('@')) {
                e.preventDefault();
                showToast('Please enter a valid email address', 'error');
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                showToast('Passwords do not match', 'error');
                return;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                showToast('Password must be at least 6 characters long', 'error');
                return;
            }
        });
        
        // Add loading state to submit button
        document.getElementById('registerForm').addEventListener('submit', function() {
            const submitBtn = document.querySelector('.btn-register');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Account...';
        });
    </script>
</body>
</html>