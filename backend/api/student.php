<?php
require_once '../config/db.php';
require_once '../models/LogEntry.php';
require_once '../models/WeeklySummary.php';
require_once '../models/MonthlySummary.php';
require_once '../models/Evaluation.php';
require_once '../models/Notification.php';

header('Content-Type: application/json');

// Check if user is authenticated and is a student
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Handle form data (for file uploads)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
} else {
    // Handle JSON data
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
}

try {
    switch ($action) {
        case 'add_log':
            // Debug: Log the incoming data
            error_log("Received add_log request");
            error_log("POST data: " . print_r($_POST, true));
            error_log("FILES data: " . print_r($_FILES, true));
            
            $activity = $_POST['activity'] ?? '';
            $date = $_POST['date'] ?? date('Y-m-d');
            $latitude = $_POST['latitude'] ?? null;
            $longitude = $_POST['longitude'] ?? null;
            $workplace_name = $_POST['workplace_name'] ?? null;
            $file_upload = null;
            
            // Handle file upload
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../../uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = time() . '_' . $_FILES['file']['name'];
                $filePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
                    $file_upload = $fileName;
                }
            }
            
            if (empty($activity) || strlen($activity) < 50) {
                echo json_encode(['success' => false, 'message' => 'Activity description must be at least 50 characters']);
                exit;
            }
            
            // Get student's SIWES start date
            $stmt = $pdo->prepare("SELECT siwes_start_date FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $student = $stmt->fetch();
            
            if (!$student['siwes_start_date']) {
                echo json_encode(['success' => false, 'message' => 'SIWES start date not set. Please contact your coordinator.']);
                exit;
            }
            
            // Calculate week number and day name
            $weekNumber = LogEntry::calculateWeekNumber($student['siwes_start_date'], $date);
            $dayName = LogEntry::getDayName($date);
            $time = date('H:i:s');
            
            $logData = [
                'student_id' => $_SESSION['user_id'],
                'activity' => $activity,
                'date' => $date,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'location_address' => $workplace_name
            ];
            
            error_log("Log data to insert: " . print_r($logData, true));
            
            if (LogEntry::create($pdo, $logData)) {
                // Check if weekly summary is due (5 entries in a week)
                $weekEntries = LogEntry::getByWeek($pdo, $_SESSION['user_id'], $weekNumber);
                if (count($weekEntries) >= 5 && !WeeklySummary::existsForWeek($pdo, $_SESSION['user_id'], $weekNumber)) {
                    // Notify student about weekly summary
                    Notification::notifyWeeklySummaryDue($pdo, $_SESSION['user_id'], $weekNumber);
                }
                
                echo json_encode(['success' => true, 'message' => 'Log entry submitted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to submit log entry']);
            }
            break;
            
        case 'get_logs':
            $logs = LogEntry::getByStudentId($pdo, $_SESSION['user_id']);
            echo json_encode(['success' => true, 'data' => $logs]);
            break;
            
        case 'get_stats':
    $stats = LogEntry::getStatsByStudent($pdo, $_SESSION['user_id']);
            $weeklyStats = LogEntry::getWeeklyStats($pdo, $_SESSION['user_id']);
            $recentEntries = LogEntry::getRecentEntries($pdo, $_SESSION['user_id'], 5);
    
    echo json_encode([
        'success' => true,
                'data' => [
                    'stats' => $stats,
                    'weekly_stats' => $weeklyStats,
                    'recent_entries' => $recentEntries
                ]
            ]);
            break;
            
        case 'add_weekly_summary':
            $weekNumber = $input['week_number'] ?? null;
            $summaryOfWork = $input['summary_of_work'] ?? '';
            $problemsEncountered = $input['problems_encountered'] ?? '';
            $suggestionsForImprovement = $input['suggestions_for_improvement'] ?? '';
            
            if (!$weekNumber || empty($summaryOfWork)) {
                echo json_encode(['success' => false, 'message' => 'Week number and summary are required']);
                exit;
            }
            
            if (WeeklySummary::existsForWeek($pdo, $_SESSION['user_id'], $weekNumber)) {
                echo json_encode(['success' => false, 'message' => 'Weekly summary already exists for this week']);
    exit;
}

            $summaryData = [
        'student_id' => $_SESSION['user_id'],
                'week_number' => $weekNumber,
                'summary_of_work' => $summaryOfWork,
                'problems_encountered' => $problemsEncountered,
                'suggestions_for_improvement' => $suggestionsForImprovement
            ];
            
            if (WeeklySummary::create($pdo, $summaryData)) {
                echo json_encode(['success' => true, 'message' => 'Weekly summary submitted successfully']);
    } else {
                echo json_encode(['success' => false, 'message' => 'Failed to submit weekly summary']);
            }
            break;
            
        case 'get_weekly_summaries':
            $summaries = WeeklySummary::getByStudentId($pdo, $_SESSION['user_id']);
            echo json_encode(['success' => true, 'data' => $summaries]);
            break;
            
        case 'add_monthly_summary':
            $monthNumber = $input['month_number'] ?? null;
            $learningOutcomes = $input['learning_outcomes'] ?? '';
            $innovationsInitiatives = $input['innovations_initiatives'] ?? '';
            $generalReflections = $input['general_reflections'] ?? '';
            
            if (!$monthNumber || empty($learningOutcomes)) {
                echo json_encode(['success' => false, 'message' => 'Month number and learning outcomes are required']);
    exit;
}

            if (MonthlySummary::existsForMonth($pdo, $_SESSION['user_id'], $monthNumber)) {
                echo json_encode(['success' => false, 'message' => 'Monthly summary already exists for this month']);
    exit;
}

            $summaryData = [
                'student_id' => $_SESSION['user_id'],
                'month_number' => $monthNumber,
                'learning_outcomes' => $learningOutcomes,
                'innovations_initiatives' => $innovationsInitiatives,
                'general_reflections' => $generalReflections
            ];
            
            if (MonthlySummary::create($pdo, $summaryData)) {
                echo json_encode(['success' => true, 'message' => 'Monthly summary submitted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to submit monthly summary']);
            }
            break;
            
        case 'get_monthly_summaries':
            $summaries = MonthlySummary::getByStudentId($pdo, $_SESSION['user_id']);
            echo json_encode(['success' => true, 'data' => $summaries]);
            break;
            
        case 'get_evaluations':
            $evaluations = Evaluation::getByStudentId($pdo, $_SESSION['user_id']);
            echo json_encode(['success' => true, 'data' => $evaluations]);
            break;
            
        case 'get_notifications':
            $notifications = Notification::getByUserId($pdo, $_SESSION['user_id'], 10);
            $unreadCount = Notification::getUnreadCount($pdo, $_SESSION['user_id']);
            
            echo json_encode([
                'success' => true, 
                'data' => [
                    'notifications' => $notifications,
                    'unread_count' => $unreadCount
                ]
            ]);
            break;
            
        case 'mark_notification_read':
            $notificationId = $input['notification_id'] ?? null;
            if ($notificationId) {
                Notification::markAsRead($pdo, $notificationId);
                echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Notification ID required']);
            }
            break;
            
        case 'get_profile':
            $stmt = $pdo->prepare("
                SELECT id, name, email, matric_number, student_id, department, institution, 
                       siwes_start_date, siwes_end_date, workplace_name, created_at
                FROM users WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $profile = $stmt->fetch();
            
            if ($profile) {
                // Calculate SIWES duration
                if ($profile['siwes_start_date'] && $profile['siwes_end_date']) {
                    $start = new DateTime($profile['siwes_start_date']);
                    $end = new DateTime($profile['siwes_end_date']);
                    $duration = $start->diff($end);
                    $profile['siwes_duration'] = $duration->days . ' days';
                }
                
                echo json_encode(['success' => true, 'data' => $profile]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Profile not found']);
            }
            break;
            
        case 'get_workplace_location':
            $stmt = $pdo->prepare("
                SELECT workplace_name, workplace_latitude, workplace_longitude, workplace_address
                FROM users WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $workplace = $stmt->fetch();
            
            if ($workplace) {
                echo json_encode([
                    'success' => true, 
                    'workplace_name' => $workplace['workplace_name'],
                    'workplace_latitude' => $workplace['workplace_latitude'],
                    'workplace_longitude' => $workplace['workplace_longitude'],
                    'workplace_address' => $workplace['workplace_address']
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Workplace location not found']);
            }
            break;
            
        case 'update_profile':
            $name = $input['name'] ?? '';
            $email = $input['email'] ?? '';
            $workplace_name = $input['workplace_name'] ?? '';
            
            if (empty($name) || empty($email)) {
                echo json_encode(['success' => false, 'message' => 'Name and email are required']);
    exit;
}

            $stmt = $pdo->prepare("
                UPDATE users 
                SET name = ?, email = ?, workplace_name = ? 
                WHERE id = ?
            ");
            
            if ($stmt->execute([$name, $email, $workplace_name, $_SESSION['user_id']])) {
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }
            break;
            
        case 'update_workplace_location':
            $latitude = $input['latitude'] ?? null;
            $longitude = $input['longitude'] ?? null;
            $address = $input['address'] ?? '';
            
            if (!$latitude || !$longitude) {
                echo json_encode(['success' => false, 'message' => 'Latitude and longitude are required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                UPDATE users 
                SET workplace_latitude = ?, workplace_longitude = ?, workplace_address = ? 
                WHERE id = ?
            ");
            
            if ($stmt->execute([$latitude, $longitude, $address, $_SESSION['user_id']])) {
                echo json_encode(['success' => true, 'message' => 'Workplace location updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update workplace location']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Student API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?> 