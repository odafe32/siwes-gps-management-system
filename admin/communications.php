<?php
session_start();
require_once '../backend/config/db.php';
require_once '../backend/config/session.php';

// Check if user is logged in and has admin/coordinator role
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('coordinator'))) {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Communications - SIWES Logbook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin-styles.css">
    
    <!-- Additional communications styles -->
    <style>
        .message-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .message-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .message-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .message-sender {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .sender-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .sender-info h6 {
            margin: 0;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .sender-info small {
            color: #6c757d;
        }
        
        .message-time {
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        .message-content {
            color: #333;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .message-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.875rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .reply-btn {
            background: var(--info-color);
            color: white;
        }
        
        .reply-btn:hover {
            background: #138496;
            color: white;
        }
        
        .delete-btn {
            background: var(--danger-color);
            color: white;
        }
        
        .delete-btn:hover {
            background: #c82333;
            color: white;
        }
        
        .compose-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(26, 77, 46, 0.25);
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background: var(--secondary-color);
        }
        
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 0.5rem 1rem;
            border: 2px solid var(--primary-color);
            background: transparent;
            color: var(--primary-color);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .filter-tab.active {
            background: var(--primary-color);
            color: white;
        }
        
        .filter-tab:hover {
            background: var(--primary-color);
            color: white;
        }
        
        @media (max-width: 768px) {
            .filter-tabs {
                justify-content: center;
            }
            
            .message-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
            </button>
                <h1 class="page-title">Communications</h1>
            </div>
            
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></div>
                    <div class="user-role"><?php echo ucfirst($_SESSION['role'] ?? 'admin'); ?></div>
        </div>
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </button>
            </div>
        </header>
        
        <!-- Page Content -->
        <div class="page-content">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h2 class="mb-1">Communications</h2>
                    <p class="text-muted mb-0">Send messages and manage communications</p>
                </div>
            </div>
            
            <!-- Compose Message Section -->
            <div class="compose-section fade-in-up">
                <h3 class="section-title mb-3">
                    <i class="fas fa-edit"></i>
                    Compose Message
                </h3>
                <form id="composeForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="recipient" class="form-label">Recipient</label>
                            <select class="form-control" id="recipient" required>
                                <option value="">Select Recipient</option>
                                <option value="all_students">All Students</option>
                                <option value="all_supervisors">All Supervisors</option>
                                <option value="specific">Specific User</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        Send Message
                    </button>
                </form>
            </div>
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button class="filter-tab active" onclick="filterMessages('all')">All Messages</button>
                <button class="filter-tab" onclick="filterMessages('sent')">Sent</button>
                <button class="filter-tab" onclick="filterMessages('received')">Received</button>
                <button class="filter-tab" onclick="filterMessages('important')">Important</button>
        </div>

            <!-- Messages List -->
            <div id="messagesList">
                <!-- Sample Messages -->
                <div class="message-card fade-in-up">
                    <div class="message-header">
                        <div class="message-sender">
                            <div class="sender-avatar">J</div>
                            <div class="sender-info">
                                <h6>John Doe</h6>
                                <small>Student - Computer Science</small>
                            </div>
                            </div>
                        <div class="message-time">2 hours ago</div>
                                </div>
                    <div class="message-content">
                        Hello, I have a question about my SIWES logbook submission. Can you please help me understand the requirements for the weekly summary?
                                </div>
                    <div class="message-actions">
                        <button class="action-btn reply-btn" onclick="replyMessage(1)">
                            <i class="fas fa-reply"></i>
                            Reply
                        </button>
                        <button class="action-btn delete-btn" onclick="deleteMessage(1)">
                            <i class="fas fa-trash"></i>
                            Delete
                                </button>
                    </div>
                </div>

                <div class="message-card fade-in-up">
                    <div class="message-header">
                        <div class="message-sender">
                            <div class="sender-avatar">S</div>
                            <div class="sender-info">
                                <h6>Supervisor Smith</h6>
                                <small>Supervisor - Engineering</small>
                            </div>
                        </div>
                        <div class="message-time">1 day ago</div>
                    </div>
                    <div class="message-content">
                        I would like to report that one of my students has been consistently submitting high-quality work. I recommend them for special recognition.
                    </div>
                    <div class="message-actions">
                        <button class="action-btn reply-btn" onclick="replyMessage(2)">
                            <i class="fas fa-reply"></i>
                            Reply
                        </button>
                        <button class="action-btn delete-btn" onclick="deleteMessage(2)">
                            <i class="fas fa-trash"></i>
                            Delete
                        </button>
                    </div>
                </div>

                <div class="message-card fade-in-up">
                    <div class="message-header">
                        <div class="message-sender">
                            <div class="sender-avatar">A</div>
                            <div class="sender-info">
                                <h6>Admin System</h6>
                                <small>System Notification</small>
                    </div>
                        </div>
                        <div class="message-time">3 days ago</div>
                    </div>
                    <div class="message-content">
                        Weekly system maintenance will be performed on Sunday at 2:00 AM. The system will be unavailable for approximately 30 minutes.
                    </div>
                    <div class="message-actions">
                        <button class="action-btn reply-btn" onclick="replyMessage(3)">
                            <i class="fas fa-reply"></i>
                            Reply
                        </button>
                        <button class="action-btn delete-btn" onclick="deleteMessage(3)">
                            <i class="fas fa-trash"></i>
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin-scripts.js"></script>
    <script>
        // Communication functions
        function filterMessages(type) {
            // Remove active class from all tabs
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Add active class to clicked tab
            event.target.classList.add('active');
            
            showToast(`Filtering messages by ${type} will be implemented in the next update`, 'info');
        }
        
        function replyMessage(messageId) {
            showToast(`Reply to message ${messageId} will be implemented in the next update`, 'info');
        }
        
        function deleteMessage(messageId) {
            if (confirm('Are you sure you want to delete this message?')) {
                showToast(`Message ${messageId} deleted successfully`, 'success');
            }
        }
        
        // Form submission
        document.getElementById('composeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const recipient = document.getElementById('recipient').value;
            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value;
            
            if (!recipient || !subject || !message) {
                showToast('Please fill in all fields', 'error');
                return;
            }
            
            showToast('Message sent successfully!', 'success');
            document.getElementById('composeForm').reset();
        });
    </script>
</body>
</html> 