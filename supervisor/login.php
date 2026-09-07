<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';

// Check if user is already logged in
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'supervisor') {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Login - SIWES Logbook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --nsukka-green: #2E8B57;
            --nsukka-green-light: #3CB371;
            --nsukka-green-dark: #228B22;
            --nsukka-gold: #FFD700;
            --nsukka-cream: #F5F5DC;
            --text-dark: #2C3E50;
            --text-light: #6C757D;
        }
        
        body {
            background: linear-gradient(135deg, var(--nsukka-green) 0%, var(--nsukka-green-dark) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            max-width: 450px;
            width: 100%;
            border: 3px solid var(--nsukka-green);
        }
        
        .login-header {
            background: linear-gradient(45deg, var(--nsukka-green), var(--nsukka-green-light));
            color: white;
            padding: 2rem;
            border-radius: 17px 17px 0 0;
            text-align: center;
        }
        
        .login-header h2 {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            margin-bottom: 0;
            opacity: 0.9;
        }
        
        .login-body {
            padding: 2.5rem;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--nsukka-green);
            box-shadow: 0 0 0 0.2rem rgba(46, 139, 87, 0.25);
        }
        
        .input-group-text {
            background: var(--nsukka-green);
            color: white;
            border: 2px solid var(--nsukka-green);
            border-radius: 10px 0 0 10px;
        }
        
        .password-toggle {
            background: var(--nsukka-green-light);
            color: white;
            border: 2px solid var(--nsukka-green);
            border-radius: 0 10px 10px 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .password-toggle:hover {
            background: var(--nsukka-green-dark);
        }
        
        .btn-nsukka {
            background: linear-gradient(45deg, var(--nsukka-green), var(--nsukka-green-light));
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(46, 139, 87, 0.3);
        }
        
        .btn-nsukka:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 139, 87, 0.4);
            color: white;
        }
        
        .university-logo {
            max-width: 80px;
            height: auto;
            margin-bottom: 1rem;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border-left: 4px solid #dc3545;
        }
        
        .back-link {
            color: var(--nsukka-green);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: var(--nsukka-green-dark);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-container">
                    <div class="login-header">
                        <img src="../assets/images/logo.jpg" alt="Nsukka Keffi Logo" class="university-logo">

                        <h2>Supervisor Login</h2>
                        <p>Review and approve student log entries</p>
                    </div>
                    
                    <div class="login-body">
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($_GET['error']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form id="loginForm">
                            <input type="hidden" id="action" value="login">
                            <input type="hidden" id="role" value="supervisor">
                            
                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label fw-bold">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                                    <span class="input-group-text password-toggle" onclick="togglePassword('password')">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-nsukka" id="loginButton">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login as Supervisor
                                </button>
                            </div>
                        </form>

                        <div style="margin-top:1rem;padding:1rem;background:#f0f7f0;border:1px dashed #4a7c59;border-radius:10px;font-size:0.85rem;color:#1a4d2e;">
                            <strong><i class="fas fa-info-circle me-1"></i>Test Credentials:</strong><br>
                            Email: <code>supervisor@test.com</code><br>
                            Password: <code>12345678</code>
                        </div>
                        
                        <div class="text-center">
                            <a href="../index.php" class="back-link">
                                <i class="fas fa-arrow-left me-1"></i>Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const icon = document.querySelector('.password-toggle i');
            
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
        
        // Handle login form submission
        $(document).ready(function() {
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                
                const email = $('#email').val();
                const password = $('#password').val();
                const action = $('#action').val();
                const role = $('#role').val();
                
                // Disable button and show loading state
                $('#loginButton').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Logging in...');
                
                // Send AJAX request
                $.ajax({
                    url: '../backend/api/auth.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        action: action,
                        role: role,
                        email: email,
                        password: password
                    }),
                    success: function(response) {
                        if (response.success) {
                            window.location.href = 'dashboard.php';
                        } else {
                            // Show error message
                            const errorMsg = response.message || 'Login failed. Please try again.';
                            const alertHtml = `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>${errorMsg}</div>`;
                            
                            // Remove any existing alerts
                            $('.alert').remove();
                            
                            // Add new alert before the form
                            $('#loginForm').before(alertHtml);
                            
                            // Reset button
                            $('#loginButton').prop('disabled', false).html('<i class="fas fa-sign-in-alt me-2"></i>Login as Supervisor');
                        }
                    },
                    error: function(xhr, status, error) {
                        // Show error message
                        const alertHtml = `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Server error. Please try again later.</div>`;
                        
                        // Remove any existing alerts
                        $('.alert').remove();
                        
                        // Add new alert before the form
                        $('#loginForm').before(alertHtml);
                        
                        // Reset button
                        $('#loginButton').prop('disabled', false).html('<i class="fas fa-sign-in-alt me-2"></i>Login as Supervisor');
                        
                        console.error('Login error:', error);
                    }
                });
            });
        });
    </script>
</body>
</html>