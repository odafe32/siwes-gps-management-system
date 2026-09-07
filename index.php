<?php
session_start();
require_once 'backend/config/db.php';
require_once 'backend/config/session.php';

// Check if user is already logged in and redirect appropriately
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin':
        case 'coordinator':
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
    <meta name="description" content="SIWES Intern Tracking System using GPS Technology - GPS-based attendance verification and geofencing for industrial training monitoring">
    <meta name="keywords" content="SIWES, GPS, Geofencing, Intern Tracking, Industrial Training, NSUK, Nasarawa State University">
    <title>SIWES GPS Intern Tracking System - Nasarawa State University Keffi</title>
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
            --gradient-gold: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%);
            --shadow-soft: 0 10px 30px rgba(26, 77, 46, 0.1);
            --shadow-medium: 0 20px 40px rgba(26, 77, 46, 0.15);
            --shadow-strong: 0 30px 60px rgba(26, 77, 46, 0.2);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

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
            padding: 0.75rem 0;
        }
        .navbar-brand {
            font-weight: 800;
            color: white !important;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .navbar-brand .logo-icon {
            width: 38px; height: 38px;
            background: var(--gradient-gold);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: var(--nsuk-dark); font-size: 1.1rem;
        }
        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            margin: 0 0.15rem;
        }
        .nav-link:hover {
            color: white !important;
            background: rgba(255, 255, 255, 0.12);
        }

        /* Hero Section */
        .hero-section {
            background: var(--gradient-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            padding: 6rem 0 3rem;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background:
                radial-gradient(circle at 20% 50%, rgba(212, 175, 55, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(74, 124, 89, 0.2) 0%, transparent 50%);
        }
        .floating-elements { position: absolute; top: 0; left: 0; right: 0; bottom: 0; pointer-events: none; z-index: 1; }
        .floating-element {
            position: absolute;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        .floating-element:nth-child(1) { width: 80px; height: 80px; top: 15%; left: 8%; animation-delay: 0s; }
        .floating-element:nth-child(2) { width: 50px; height: 50px; top: 60%; right: 12%; animation-delay: 2s; }
        .floating-element:nth-child(3) { width: 100px; height: 100px; bottom: 20%; left: 15%; animation-delay: 4s; }
        .floating-element:nth-child(4) { width: 40px; height: 40px; top: 30%; right: 30%; animation-delay: 1s; }
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-25px) rotate(180deg); }
        }

        .hero-content { position: relative; z-index: 2; color: white; }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: rgba(212, 175, 55, 0.2);
            border: 1px solid rgba(212, 175, 55, 0.4);
            color: var(--nsuk-gold);
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        .hero-title {
            font-size: clamp(2.2rem, 5vw, 3.5rem);
            font-weight: 800;
            margin-bottom: 1.25rem;
            line-height: 1.15;
        }
        .hero-title .highlight { color: var(--nsuk-gold); }
        .hero-subtitle {
            font-size: clamp(1rem, 2.5vw, 1.25rem);
            margin-bottom: 2rem;
            opacity: 0.9;
            line-height: 1.7;
            max-width: 540px;
        }

        /* Login Cards */
        .login-cards { display: flex; gap: 1rem; flex-wrap: wrap; }
        .login-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 1.5rem;
            text-decoration: none;
            color: white;
            transition: all 0.3s ease;
            min-width: 180px;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            text-align: center;
        }
        .login-card:hover {
            background: rgba(255, 255, 255, 0.18);
            border-color: rgba(212, 175, 55, 0.5);
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
            color: white;
        }
        .login-card-icon {
            width: 56px; height: 56px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }
        .login-card-icon.student { background: linear-gradient(135deg, #28a745, #20c997); }
        .login-card-icon.supervisor { background: linear-gradient(135deg, #17a2b8, #6f42c1); }
        .login-card-icon.admin { background: linear-gradient(135deg, #d4af37, #f4d03f); color: var(--nsuk-dark); }
        .login-card-title { font-weight: 700; font-size: 1rem; }
        .login-card-desc { font-size: 0.8rem; opacity: 0.8; }

        /* Hero Visual */
        .hero-visual {
            position: relative;
            z-index: 2;
        }
        .hero-map-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: var(--shadow-strong);
        }
        .hero-map-card img { width: 100%; border-radius: 12px; }
        .hero-stats {
            display: flex;
            gap: 1.5rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        .hero-stat {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            text-align: center;
            flex: 1;
            min-width: 100px;
        }
        .hero-stat .num { font-size: 1.75rem; font-weight: 800; color: var(--nsuk-gold); }
        .hero-stat .lbl { font-size: 0.75rem; opacity: 0.85; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Features Section */
        .features-section { padding: 5rem 0; background: var(--nsuk-cream); }
        .section-title {
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            font-weight: 800;
            color: var(--nsuk-primary);
            text-align: center;
            margin-bottom: 0.75rem;
        }
        .section-subtitle {
            text-align: center;
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-bottom: 3rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        .feature-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            box-shadow: var(--shadow-soft);
            transition: all 0.3s ease;
            height: 100%;
            border: 1px solid rgba(26, 77, 46, 0.05);
        }
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-medium);
            border-color: rgba(26, 77, 46, 0.15);
        }
        .feature-icon {
            width: 70px; height: 70px;
            background: var(--gradient-primary);
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.25rem;
            font-size: 1.75rem;
            color: white;
            transition: transform 0.3s ease;
        }
        .feature-card:hover .feature-icon { transform: scale(1.1) rotate(-5deg); }
        .feature-title { font-size: 1.2rem; font-weight: 700; color: var(--nsuk-primary); margin-bottom: 0.75rem; }
        .feature-text { color: var(--text-secondary); line-height: 1.6; font-size: 0.95rem; }

        /* How It Works */
        .how-section { padding: 5rem 0; background: white; }
        .step-card {
            text-align: center;
            padding: 1.5rem;
            position: relative;
        }
        .step-number {
            width: 50px; height: 50px;
            background: var(--gradient-gold);
            color: var(--nsuk-dark);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; font-weight: 800;
            margin: 0 auto 1rem;
        }
        .step-title { font-weight: 700; color: var(--nsuk-primary); margin-bottom: 0.5rem; }
        .step-text { color: var(--text-secondary); font-size: 0.9rem; }

        /* Footer */
        .footer { background: var(--nsuk-dark); color: white; padding: 3rem 0 1rem; }
        .footer-title { font-weight: 700; margin-bottom: 1rem; color: var(--nsuk-gold); font-size: 1.1rem; }
        .footer-links { list-style: none; padding: 0; }
        .footer-links li { margin-bottom: 0.5rem; }
        .footer-links a {
            color: rgba(255, 255, 255, 0.75);
            text-decoration: none;
            transition: color 0.3s ease;
            display: inline-flex; align-items: center; gap: 0.5rem;
        }
        .footer-links a:hover { color: var(--nsuk-gold); }
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1.5rem;
            margin-top: 2rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in-up { animation: fadeInUp 0.8s ease forwards; }
        .fade-in-up-delay { animation: fadeInUp 0.8s ease 0.2s forwards; opacity: 0; }

        /* Responsive */
        @media (max-width: 991px) {
            .hero-section { padding: 5rem 0 2rem; }
            .hero-visual { margin-top: 2rem; }
        }
        @media (max-width: 768px) {
            .login-cards { flex-direction: column; }
            .login-card { min-width: auto; flex-direction: row; text-align: left; justify-content: start; }
            .login-card-icon { margin-bottom: 0; }
            .features-section, .how-section { padding: 3rem 0; }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <span class="logo-icon"><i class="fas fa-map-marker-alt"></i></span>
                SIWES GPS Tracking
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                    <li class="nav-item"><a class="nav-link" href="#login">Login</a></li>
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
            <div class="floating-element"></div>
        </div>

        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content fade-in-up">
                        <span class="hero-badge">
                            <i class="fas fa-satellite"></i> GPS-Powered Attendance Verification
                        </span>
                        <h1 class="hero-title">
                            SIWES Intern Tracking<br>
                            Using <span class="highlight">GPS Technology</span>
                        </h1>
                        <p class="hero-subtitle">
                            Real-time, location-based monitoring of industrial training interns.
                            Automatic geofence verification, attendance tracking, and breach alerts —
                            no more self-reported logbooks.
                        </p>

                        <!-- Login Cards -->
                        <div class="login-cards fade-in-up-delay" id="login">
                            <a href="student/login.php" class="login-card">
                                <div class="login-card-icon student"><i class="fas fa-user-graduate"></i></div>
                                <div>
                                    <div class="login-card-title">Student</div>
                                    <div class="login-card-desc">Check in & logbook</div>
                                </div>
                            </a>
                            <a href="supervisor/login.php" class="login-card">
                                <div class="login-card-icon supervisor"><i class="fas fa-user-tie"></i></div>
                                <div>
                                    <div class="login-card-title">Supervisor</div>
                                    <div class="login-card-desc">Monitor & review</div>
                                </div>
                            </a>
                            <a href="admin/login.php" class="login-card">
                                <div class="login-card-icon admin"><i class="fas fa-user-shield"></i></div>
                                <div>
                                    <div class="login-card-title">Admin</div>
                                    <div class="login-card-desc">Manage system</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="hero-visual fade-in-up-delay">
                        <div class="hero-map-card">
                            <img src="assets/images/lander.png" alt="SIWES GPS Tracking Dashboard" onerror="this.style.display='none'">
                        </div>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <div class="num">100m</div>
                                <div class="lbl">Geofence Radius</div>
                            </div>
                            <div class="hero-stat">
                                <div class="num">GPS</div>
                                <div class="lbl">Live Tracking</div>
                            </div>
                            <div class="hero-stat">
                                <div class="num">24/7</div>
                                <div class="lbl">Monitoring</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="container">
            <h2 class="section-title">GPS-Verified Attendance System</h2>
            <p class="section-subtitle">
                Beyond traditional logbooks — our system uses GPS and geofencing to independently verify
                that interns are physically present at their host organization during working hours.
            </p>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-satellite-dish"></i></div>
                        <h3 class="feature-title">GPS Check-in</h3>
                        <p class="feature-text">Interns check in with their phone's GPS. The system captures coordinates and verifies their location in real time.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-draw-polygon"></i></div>
                        <h3 class="feature-title">Geofencing Engine</h3>
                        <p class="feature-text">Virtual boundaries around host organizations. The Haversine formula calculates distance to determine if an intern is inside or outside.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-bell"></i></div>
                        <h3 class="feature-title">Breach Alerts</h3>
                        <p class="feature-text">Supervisors receive instant alerts when an intern leaves the geofenced area during working hours.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-map-marked-alt"></i></div>
                        <h3 class="feature-title">Live Map Dashboard</h3>
                        <p class="feature-text">Supervisors see all assigned interns on a live map with color-coded status — green for present, red for outside zone.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-clipboard-list"></i></div>
                        <h3 class="feature-title">Digital E-Logbook</h3>
                        <p class="feature-text">Daily activity entries linked to GPS-verified attendance. Supervisors review, approve, or reject entries remotely.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-chart-bar"></i></div>
                        <h3 class="feature-title">Attendance Reports</h3>
                        <p class="feature-text">Generate and export attendance and movement reports as evidence of genuine participation in the scheme.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-section" id="how-it-works">
        <div class="container">
            <h2 class="section-title">How It Works</h2>
            <p class="section-subtitle">From check-in to report — the complete GPS verification flow</p>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h4 class="step-title">Open & Check In</h4>
                        <p class="step-text">Intern opens the app and clicks Check In. GPS coordinates are captured from their phone.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h4 class="step-title">Geofence Check</h4>
                        <p class="step-text">The system compares coordinates against the organization's geofence using the Haversine formula.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h4 class="step-title">Record & Alert</h4>
                        <p class="step-text">Attendance is recorded as Present or Outside Zone. If outside, the supervisor gets an instant alert.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <h4 class="step-title">Monitor & Report</h4>
                        <p class="step-text">Supervisors monitor on a live map and generate attendance reports for evidence-based supervision.</p>
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
                    <h5 class="footer-title">SIWES GPS Tracking</h5>
                    <p style="color:rgba(255,255,255,0.7);">
                        GPS-based intern tracking system for the Student Industrial Work Experience Scheme.
                        Developed for Nasarawa State University, Keffi.
                    </p>
                </div>
                <div class="col-md-4">
                    <h5 class="footer-title">Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="student/login.php"><i class="fas fa-user-graduate"></i>Student Portal</a></li>
                        <li><a href="supervisor/login.php"><i class="fas fa-user-tie"></i>Supervisor Portal</a></li>
                        <li><a href="admin/login.php"><i class="fas fa-user-shield"></i>Admin Portal</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5 class="footer-title">Contact Info</h5>
                    <ul class="footer-links">
                        <li><i class="fas fa-university me-2"></i>Nasarawa State University Keffi</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i>Keffi, Nasarawa State</li>
                        <li><i class="fas fa-envelope me-2"></i>siwes@nsuk.edu.ng</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 SIWES Intern Tracking System using GPS Technology | Nasarawa State University Keffi</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scroll for nav links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const target = document.querySelector(this.getAttribute('href'));
                if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
            });
        });
    </script>
</body>
</html>
