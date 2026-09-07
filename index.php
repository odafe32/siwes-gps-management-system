<?php
session_start();
require_once 'backend/config/db.php';
require_once 'backend/config/session.php';

// Check if user is already logged in and redirect appropriately
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: admin/dashboard.php');
            exit();
        case 'student':
            header('Location: student/dashboard.php');
            exit();
        case 'supervisor':
            header('Location: supervisor/dashboard.php');
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SIWES Logbook System - Comprehensive digital platform for managing Student Industrial Work Experience Scheme at Nasarawa State University Keffi">
    <meta name="keywords" content="SIWES, Logbook, Industrial Training, NSUK, Nasarawa State University">
    <meta name="author" content="Nasarawa State University Keffi">
    <title>SIWES Logbook - Nasarawa State University Keffi</title>
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
            line-height: 1.6;
            color: var(--text-primary);
            overflow-x: hidden;
        }
        
        /* Navigation */
        .navbar {
            background: rgba(26, 77, 46, 0.95);
            backdrop-filter: blur(20px);
            transition: all 0.3s ease;
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: white !important;
            font-size: 1.5rem;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            margin: 0 0.25rem;
        }
        
        .nav-link:hover, .nav-link:focus {
            color: white !important;
            transform: translateY(-1px);
            background: rgba(255, 255, 255, 0.1);
        }
        
        .navbar-toggler {
            border: none;
            padding: 0.25rem 0.5rem;
        }
        
        .navbar-toggler:focus {
            box-shadow: none;
        }
        
        /* Hero Section */
        .hero-section {
            background: var(--gradient-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            padding: 2rem 0;
        }
        
        .hero-section::before {
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
        
        .hero-content {
            position: relative;
            z-index: 2;
            color: white;
        }
        
        .hero-title {
            font-size: clamp(2.5rem, 5vw, 3.5rem);
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }
        
        .hero-subtitle {
            font-size: clamp(1.1rem, 2.5vw, 1.3rem);
            margin-bottom: 2rem;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .btn-hero {
            padding: 1rem 2rem;
            font-weight: 600;
            border-radius: 15px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-width: 200px;
            justify-content: center;
            border: none;
            font-size: 1rem;
        }
        
        .btn-primary-hero {
            background: var(--gradient-secondary);
            color: var(--nsuk-dark);
        }
        
        .btn-primary-hero:hover, .btn-primary-hero:focus {
            transform: translateY(-3px);
            box-shadow: var(--shadow-medium);
            color: var(--nsuk-dark);
            background: var(--gradient-secondary);
        }
        
        .btn-outline-hero {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-outline-hero:hover, .btn-outline-hero:focus {
            background: white;
            color: var(--nsuk-primary);
            transform: translateY(-3px);
        }
        
        .hero-image {
            max-width: 100%;
            height: auto;
            border-radius: 20px;
            box-shadow: var(--shadow-strong);
            transition: transform 0.3s ease;
        }
        
        .hero-image:hover {
            transform: scale(1.02);
        }
        
        /* Features Section */
        .features-section {
            padding: 5rem 0;
            background: var(--nsuk-cream);
        }
        
        .section-title {
            font-size: clamp(2rem, 4vw, 2.5rem);
            font-weight: 700;
            color: var(--nsuk-primary);
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            box-shadow: var(--shadow-soft);
            transition: all 0.3s ease;
            height: 100%;
            border: none;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-medium);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: white;
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.1);
        }
        
        .feature-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--nsuk-primary);
            margin-bottom: 1rem;
        }
        
        .feature-text {
            color: var(--text-secondary);
            line-height: 1.6;
        }
        
        /* Footer */
        .footer {
            background: var(--nsuk-dark);
            color: white;
            padding: 3rem 0 1rem;
        }
        
        .footer-title {
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--nsuk-gold);
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
        }
        
        .footer-links li {
            margin-bottom: 0.5rem;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .footer-links a:hover {
            color: var(--nsuk-gold);
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1rem;
            margin-top: 2rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-section {
                min-height: 80vh;
                padding: 1rem 0;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn-hero {
                min-width: auto;
                width: 100%;
                justify-content: center;
            }
            
            .features-section {
                padding: 3rem 0;
            }
            
            .feature-card {
                margin-bottom: 1rem;
            }
            
            .navbar-nav {
                text-align: center;
                margin-top: 1rem;
            }
            
            .nav-link {
                margin: 0.25rem 0;
            }
        }
        
        @media (max-width: 576px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
            
            .section-title {
                font-size: 1.8rem;
            }
            
            .feature-card {
                padding: 1.5rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
        }
        
        /* Accessibility Improvements */
        .btn-hero:focus,
        .nav-link:focus,
        .feature-card:focus-within {
            outline: 2px solid var(--nsuk-gold);
            outline-offset: 2px;
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
        
        /* Loading states */
        .btn-hero:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Skip to content link for accessibility */
        .skip-link {
            position: absolute;
            top: -40px;
            left: 6px;
            background: var(--nsuk-primary);
            color: white;
            padding: 8px;
            text-decoration: none;
            border-radius: 4px;
            z-index: 1000;
        }
        
        .skip-link:focus {
            top: 6px;
        }
    </style>
</head>
<body>
    <!-- Skip to content link for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#" aria-label="SIWES Logbook Home">
                <i class="fas fa-graduation-cap me-2" aria-hidden="true"></i>SIWES Logbook
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features" aria-label="View features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php" aria-label="About SIWES">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php" aria-label="Contact us">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="features.php" aria-label="Learn more about features">Learn More</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" id="main-content">
        <div class="floating-elements" aria-hidden="true">
            <div class="floating-element"></div>
            <div class="floating-element"></div>
            <div class="floating-element"></div>
        </div>
        
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content fade-in-up">
                        <h1 class="hero-title">SIWES Logbook System</h1>
                        <p class="hero-subtitle">
                            Streamline your Student Industrial Work Experience Scheme with our comprehensive digital logbook platform. 
                            Track progress, manage activities, and enhance your learning experience.
                        </p>
                        <div class="hero-buttons fade-in-up-delay">
                            <a href="student/login.php" class="btn-hero btn-primary-hero" aria-label="Login as student">
                                <i class="fas fa-user-graduate" aria-hidden="true"></i>
                                Student Login
                            </a>
                            <a href="admin/login.php" class="btn-hero btn-outline-hero" aria-label="Login as coordinator">
                                <i class="fas fa-user-shield" aria-hidden="true"></i>
                                Coordinator Login
                            </a>
                             <a href="supervisor/login.php" class="btn-hero btn-outline-hero" aria-label="Login as coordinator">
                                <i class="fas fa-user-school" aria-hidden="true"></i>

                                Supervisor Login
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="text-center fade-in-up-delay">
                        <img src="assets/images/lander.png" alt="SIWES Platform Interface" class="hero-image">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="container">
            <h2 class="section-title">Why Choose Our SIWES Platform?</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                        </div>
                        <h3 class="feature-title">Digital Logbook</h3>
                        <p class="feature-text">
                            Maintain detailed records of your industrial training activities with our comprehensive digital logbook system.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                        </div>
                        <h3 class="feature-title">Location Tracking</h3>
                        <p class="feature-text">
                            Automatically capture and verify your training location for enhanced accountability and transparency.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line" aria-hidden="true"></i>
                        </div>
                        <h3 class="feature-title">Progress Monitoring</h3>
                        <p class="feature-text">
                            Track your learning progress with real-time analytics and comprehensive reporting features.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users" aria-hidden="true"></i>
                        </div>
                        <h3 class="feature-title">Supervisor Integration</h3>
                        <p class="feature-text">
                            Seamless communication with supervisors for guidance, feedback, and evaluation of your work.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt" aria-hidden="true"></i>
                        </div>
                        <h3 class="feature-title">Secure Platform</h3>
                        <p class="feature-text">
                            Your data is protected with enterprise-grade security measures and regular backups.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt" aria-hidden="true"></i>
                        </div>
                        <h3 class="feature-title">Mobile Friendly</h3>
                        <p class="feature-text">
                            Access your logbook from any device with our responsive design and mobile-optimized interface.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5 class="footer-title">SIWES Logbook</h5>
                    <p class="text-muted">
                        Empowering students with a comprehensive digital platform for managing their industrial training experience.
                    </p>
                </div>
                <div class="col-md-4">
                    <h5 class="footer-title">Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="student/login.php"><i class="fas fa-user-graduate" aria-hidden="true"></i>Student Portal</a></li>
                        <li><a href="admin/login.php"><i class="fas fa-user-shield" aria-hidden="true"></i>Coordinator Portal</a></li>
                        <li><a href="about.php"><i class="fas fa-info-circle" aria-hidden="true"></i>About SIWES</a></li>
                        <li><a href="contact.php"><i class="fas fa-envelope" aria-hidden="true"></i>Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5 class="footer-title">Contact Info</h5>
                    <ul class="footer-links">
                        <li><i class="fas fa-university me-2" aria-hidden="true"></i>Nasarawa State University Keffi</li>
                        <li><i class="fas fa-map-marker-alt me-2" aria-hidden="true"></i>Keffi, Nasarawa State</li>
                        <li><i class="fas fa-envelope me-2" aria-hidden="true"></i>siwes@nsuk.edu.ng</li>
                        <li><i class="fas fa-phone me-2" aria-hidden="true"></i>+234 XXX XXX XXXX</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 SIWES Logbook System. All rights reserved. | Nasarawa State University Keffi</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(26, 77, 46, 0.98)';
            } else {
                navbar.style.background = 'rgba(26, 77, 46, 0.95)';
            }
        });
        
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Add loading states to buttons
        document.querySelectorAll('.btn-hero').forEach(button => {
            button.addEventListener('click', function() {
                this.disabled = true;
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-2" aria-hidden="true"></i>Loading...';
                
                // Re-enable after a short delay (in case of navigation issues)
                setTimeout(() => {
                    this.disabled = false;
                    this.innerHTML = originalText;
                }, 3000);
            });
        });
        
        // Intersection Observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        // Observe feature cards for animation
        document.querySelectorAll('.feature-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html> 