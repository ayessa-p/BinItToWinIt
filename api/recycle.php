<?php
/**
 * ESP32 Recycling API Endpoint
 * Secure API for receiving recycling data from ESP32 devices
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

// Validate required fields
$required_fields = ['api_key', 'student_id', 'sensor_id'];
foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

$api_key = sanitize_input($data['api_key']);
$student_id = sanitize_input($data['student_id']);
$sensor_id = sanitize_input($data['sensor_id']);
$bottle_type = sanitize_input($data['bottle_type'] ?? 'plastic');
$device_timestamp = $data['device_timestamp'] ?? null;

try {
    $db = Database::getInstance()->getConnection();
    
    // Verify API key
    $stmt = $db->prepare("SELECT id, device_id, device_name, location, is_active FROM api_keys WHERE api_key = ? AND is_active = 1");
    $stmt->execute([$api_key]);
    $api_key_data = $stmt->fetch();
    
    if (!$api_key_data) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid or inactive API key']);
        exit();
    }
    
    // Update last used timestamp
    $update_stmt = $db->prepare("UPDATE api_keys SET last_used = NOW() WHERE id = ?");
    $update_stmt->execute([$api_key_data['id']]);
    
    // Verify student exists
    $stmt = $db->prepare("SELECT id, student_id, full_name, eco_tokens FROM users WHERE student_id = ? AND is_active = 1");
    $stmt->execute([$student_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Student not found or account inactive']);
        exit();
    }
    
    // Calculate tokens to award
    $tokens_earned = TOKENS_PER_BOTTLE;
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Update user's token balance
        $update_stmt = $db->prepare("UPDATE users SET eco_tokens = eco_tokens + ? WHERE id = ?");
        $update_stmt->execute([$tokens_earned, $user['id']]);
        
        // Record recycling activity
        $insert_stmt = $db->prepare("
            INSERT INTO recycling_activities (user_id, sensor_id, bottle_type, tokens_earned, device_timestamp) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $device_timestamp_formatted = $device_timestamp ? date('Y-m-d H:i:s', strtotime($device_timestamp)) : null;
        $insert_stmt->execute([$user['id'], $sensor_id, $bottle_type, $tokens_earned, $device_timestamp_formatted]);
        $activity_id = $db->lastInsertId();
        
        // Record transaction
        $transaction_stmt = $db->prepare("
            INSERT INTO transactions (user_id, transaction_type, amount, description) 
            VALUES (?, 'earned', ?, ?)
        ");
        $description = "Recycling activity: {$bottle_type} bottle via sensor {$sensor_id}";
        $transaction_stmt->execute([$user['id'], $tokens_earned, $description]);
        
        // Commit transaction
        $db->commit();
        
        // Get updated balance
        $stmt = $db->prepare("SELECT eco_tokens FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $updated_user = $stmt->fetch();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Recycling activity recorded successfully',
            'data' => [
                'activity_id' => $activity_id,
                'tokens_earned' => $tokens_earned,
                'new_balance' => (float)$updated_user['eco_tokens'],
                'student_id' => $user['student_id'],
                'student_name' => $user['full_name']
            ]
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Recycling API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Recycling API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred processing your request']);
}
