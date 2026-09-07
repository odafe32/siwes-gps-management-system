<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';

$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Login - SIWES Logbook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --nsuk-primary: #1a4d2e;
            --nsuk-secondary: #2d5a3d;
            --nsuk-accent: #4a7c59;
            --nsuk-gold: #d4af37;
            --nsuk-cream: #f8f6f0;
            --nsuk-dark: #0f2b1a;
            --nsuk-light: #e8f5e8;
            --text-primary: #2c3e50;
            --text-secondary: #6c757d;
            --gradient-primary: linear-gradient(135deg, #1a4d2e 0%, #2d5a3d 50%, #4a7c59 100%);
            --gradient-secondary: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%);
            --shadow-soft: 0 10px 30px rgba(26, 77, 46, 0.1);
            --shadow-medium: 0 20px 40px rgba(26, 77, 46, 0.15);
            --shadow-strong: 0 30px 60px rgba(26, 77, 46, 0.2);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--gradient-primary);
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
        
        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: 1;
        }
        
        .floating-element {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-element:nth-child(1) {
            width: 60px;
            height: 60px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .floating-element:nth-child(2) {
            width: 40px;
            height: 40px;
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }
        
        .floating-element:nth-child(3) {
            width: 80px;
            height: 80px;
            bottom: 30%;
            left: 20%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            box-shadow: var(--shadow-strong);
            overflow: hidden;
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .login-header {
            background: var(--gradient-primary);
            padding: 3rem 2rem 2rem;
            text-align: center;
            color: white;
            position: relative;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
        }
        
        .login-logo {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: white;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            z-index: 2;
        }
        
        .login-title {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 2;
        }
        
        .login-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            position: relative;
            z-index: 2;
        }
        
        .login-form {
            padding: 3rem 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--nsuk-primary);
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-control {
            border: 2px solid rgba(26, 77, 46, 0.1);
            border-radius: 15px;
            padding: 1rem 1.5rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
            width: 100%;
        }
        
        .form-control:focus {
            border-color: var(--nsuk-accent);
            box-shadow: 0 0 0 0.2rem rgba(74, 124, 89, 0.25);
            background: white;
            outline: none;
        }
        
        .input-group {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6C757D;
            z-index: 10;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: var(--nsuk-primary);
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--nsuk-accent);
            z-index: 3;
        }
        
        .form-control.with-icon {
            padding-left: 3rem;
        }
        
        .btn-login {
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 15px;
            padding: 1rem 2rem;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: var(--shadow-medium);
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-strong);
            color: white;
        }
        
        .alert {
            border-radius: 15px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border-left: 4px solid #dc3545;
        }
        
        .back-link {
            text-align: center;
            margin-top: 2rem;
        }
        
        .back-link a {
            color: var(--nsuk-accent);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-link a:hover {
            color: var(--nsuk-primary);
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.8s ease forwards;
        }
        
        .fade-in-up-delay {
            animation: fadeInUp 0.8s ease forwards;
            animation-delay: 0.2s;
        }
        
        /* Responsive */
        @media (max-width: 576px) {
            .login-container {
                margin: 1rem;
                border-radius: 20px;
            }
            
            .login-header {
                padding: 2rem 1.5rem 1.5rem;
            }
            
            .login-form {
                padding: 2rem 1.5rem;
            }
            
            .login-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="floating-elements">
        <div class="floating-element"></div>
        <div class="floating-element"></div>
        <div class="floating-element"></div>
    </div>
    
    <div class="login-container fade-in-up">
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-user-shield"></i>
            </div>
            <h1 class="login-title">Coordinator Login</h1>
            <p class="login-subtitle">Access SIWES Management System</p>
        </div>
        
        <div class="login-form fade-in-up-delay">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form id="loginForm" method="POST" action="../backend/api/auth.php">
                <input type="hidden" id="action" name="action" value="login">
                <input type="hidden" id="role" name="role" value="admin">
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" class="form-control with-icon" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control with-icon" id="password" name="password" required>
                        <i class="fas fa-eye password-toggle" onclick="togglePassword('password')"></i>
                    </div>
                </div>
                
                <button type="submit" id="loginButton" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Login to Dashboard
                </button>
            </form>
            
            <div class="back-link">
                <a href="../index.php">
                    <i class="fas fa-arrow-left"></i>
                    Back to Homepage
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Add focus effects
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
        
        // AJAX Form submission
        $(document).ready(function() {
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                
                const email = $('#email').val().trim();
                const password = $('#password').val();
                
                if (!email || !password) {
                    alert('Please fill in all fields.');
                    return;
                }
                
                // Disable button and show loading state
                $('#loginButton').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Logging in...');
                
                // Prepare data in JSON format
                const formData = {
                    action: $('#action').val(),
                    role: $('#role').val(),
                    email: email,
                    password: password
                };
                
                // Send AJAX request
                $.ajax({
                    url: '../backend/api/auth.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(formData),
                    success: function(response) {
                        if (response.success) {
                            window.location.href = 'dashboard.php';
                        } else {
                            const errorMessage = response.message || 'Login failed. Please try again.';
                            const alertHtml = `
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>${errorMessage}
                                </div>
                            `;
                            $('.alert').remove();
                            $('#loginForm').before(alertHtml);
                            $('#loginButton').prop('disabled', false).html('<i class="fas fa-sign-in-alt"></i> Login to Dashboard');
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Login failed. Please try again.';
                        
                        // Try to get error message from response
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response && response.message) {
                                errorMessage = response.message;
                            }
                        } catch (e) {}
                        
                        // Show error message
                        const alertHtml = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                ${errorMessage}
                            </div>
                        `;
                        
                        // Add alert before the form
                        $('#loginForm').before(alertHtml);
                        
                        // Reset button state
                        $('#loginButton').prop('disabled', false).html('<i class="fas fa-sign-in-alt"></i> Login to Dashboard');
                    }
                });
            });
        });
        
        // Toggle password visibility
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const icon = document.querySelector('.password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>