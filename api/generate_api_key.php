<?php
/**
 * API Key Generator (Admin Tool)
 * Generate API keys for ESP32 devices
 */

require_once '../config/config.php';
require_login();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

// Verify CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    die('Invalid security token');
}

// Get form data
$device_id = sanitize_input($_POST['device_id'] ?? '');
$device_name = sanitize_input($_POST['device_name'] ?? '');
$location = sanitize_input($_POST['location'] ?? '');

if (empty($device_id)) {
    die('Device ID is required');
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Generate secure API key
    $api_key = bin2hex(random_bytes(32));
    
    // Insert API key
    $stmt = $db->prepare("
        INSERT INTO api_keys (device_id, api_key, device_name, location) 
        VALUES (?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$device_id, $api_key, $device_name, $location])) {
        echo json_encode([
            'success' => true,
            'api_key' => $api_key,
            'device_id' => $device_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to generate API key']);
    }
    
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Duplicate entry
        echo json_encode(['success' => false, 'message' => 'Device ID already exists']);
    } else {
        error_log("API Key Generation Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}
