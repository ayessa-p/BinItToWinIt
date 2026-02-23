<?php
require_once '../config/config.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;

if ($event_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if event exists and attendance is enabled
    $stmt = $db->prepare("SELECT id, title, attendance_enabled FROM events WHERE id = ? AND is_published = TRUE");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    
    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Event not found or not published']);
        exit;
    }
    
    if (!$event['attendance_enabled']) {
        echo json_encode(['success' => false, 'message' => 'Attendance tracking is disabled for this event']);
        exit;
    }
    
    // Check if user already submitted attendance
    $stmt = $db->prepare("SELECT id, attendance_status FROM event_attendance WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$event_id, $user_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'You have already submitted attendance for this event']);
        exit;
    }
    
    // Handle file upload
    $proof_image = '';
    if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['proof_image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, and GIF images are allowed']);
            exit;
        }
        
        if ($file['size'] > $max_size) {
            echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB']);
            exit;
        }
        
        // Create upload directory if it doesn't exist
        $upload_dir = UPLOAD_DIR . 'attendance/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $filename = 'event_' . $event_id . '_user_' . $user_id . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $upload_path = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $proof_image = 'uploads/attendance/' . $filename;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload proof image']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Proof image is required']);
        exit;
    }
    
    // Insert attendance record
    $stmt = $db->prepare("INSERT INTO event_attendance (event_id, user_id, proof_image, attendance_status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$event_id, $user_id, $proof_image]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Attendance submitted successfully! Your submission is now pending approval.',
        'data' => [
            'event_title' => $event['title'],
            'proof_image' => $proof_image
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Event attendance error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
