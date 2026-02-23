<?php
// Prevent any PHP notice/warning from corrupting JSON response
error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/../config/config.php';

// Keep JSON clean - config may turn display_errors back on
ini_set('display_errors', '0');
error_reporting(0);

// Help ESP32 get a complete response
set_time_limit(15);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Connection: close');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(200);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Single response array - we never throw; we always return 200 + valid JSON
// Include "step" and "debug" (real error) on failure so ESP32 Serial shows what broke
$response = ['success' => false, 'message' => 'Unknown error'];

try {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        $response = ['success' => false, 'error' => 'Empty request body', 'step' => 'input'];
        goto send;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $response = ['success' => false, 'error' => 'Invalid JSON', 'step' => 'parse', 'raw_len' => strlen($raw)];
        goto send;
    }

    $required = ['weight', 'distance', 'is_metal', 'accepted', 'device_id'];
    foreach ($required as $key) {
        if (!array_key_exists($key, $data)) {
            $response = ['success' => false, 'error' => "Missing field: $key", 'step' => 'validate'];
            goto send;
        }
    }

    // Normalize booleans: PHP (bool)"false" is TRUE (non-empty string), so accept true/false/ "true"/"false"/1/0
    $normalize_bool = function ($v) {
        if (is_bool($v)) return $v;
        if (is_int($v) || is_float($v)) return $v != 0;
        if (is_string($v)) return in_array(strtolower(trim($v)), ['1', 'true', 'yes'], true);
        return (bool) $v;
    };

    $device_id = trim((string) $data['device_id']);
    $weight_g = (float) $data['weight'];
    $distance = (float) $data['distance'];
    $is_metal = (int) $normalize_bool($data['is_metal']);
    $accepted = (int) $normalize_bool($data['accepted']);

    if ($device_id === '') {
        $response = ['success' => false, 'error' => 'device_id cannot be empty', 'step' => 'validate'];
        goto send;
    }

    try {
        $db = Database::getInstance()->getConnection();
    } catch (Throwable $e) {
        error_log('recycling_data: DB connection: ' . $e->getMessage());
        $response = ['success' => false, 'error' => 'Database unavailable', 'step' => 'db_connection', 'debug' => $e->getMessage()];
        goto send;
    }

    // Optional: log to sensor_readings (do not fail request if table missing)
    try {
        $stmt = $db->prepare("
            INSERT INTO sensor_readings (device_id, weight, distance, is_metal, accepted, reading_time, created_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$device_id, $weight_g, $distance, $is_metal, $accepted]);
    } catch (Throwable $e) {
        error_log('recycling_data: sensor_readings: ' . $e->getMessage());
    }

    // Find paired user (is_active: 1 or TRUE, both work in MySQL)
    $paired_user = null;
    try {
        $stmt = $db->prepare("
            SELECT ud.user_id, u.full_name
            FROM user_devices ud
            JOIN users u ON u.id = ud.user_id
            WHERE ud.device_id = ? AND (ud.is_active = 1 OR ud.is_active = TRUE)
        ");
        $stmt->execute([$device_id]);
        $paired_user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('recycling_data: user_devices select: ' . $e->getMessage());
        $response = ['success' => false, 'error' => 'Pairing lookup failed', 'step' => 'paired_user', 'debug' => $e->getMessage()];
        goto send;
    }

    if ($accepted) {
        if (!$paired_user) {
            $response = [
                'success' => false,
                'message' => 'No user paired to this device. Pair first at dashboard.',
                'device_id' => $device_id,
                'pairing_url' => '/dashboard/pair_device.php',
                'step' => 'no_paired_user',
            ];
            goto send;
        }

        $tokens_earned = tokens_for_bottle_weight_grams($weight_g);
        $bottle_type = 'Accepted: ' . round($weight_g, 1) . 'g PET';

        try {
            $stmt = $db->prepare("
                INSERT INTO recycling_activities (user_id, sensor_id, bottle_type, tokens_earned, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$paired_user['user_id'], $device_id, $bottle_type, $tokens_earned]);
        } catch (Throwable $e) {
            error_log('recycling_data: recycling_activities insert: ' . $e->getMessage());
            $response = ['success' => false, 'error' => 'Save recycling failed', 'step' => 'insert_accepted', 'debug' => $e->getMessage()];
            goto send;
        }

        try {
            $stmt = $db->prepare("UPDATE users SET eco_tokens = eco_tokens + ? WHERE id = ?");
            $stmt->execute([$tokens_earned, $paired_user['user_id']]);
        } catch (Throwable $e) {
            error_log('recycling_data: users update: ' . $e->getMessage());
        }

        try {
            $stmt = $db->prepare("
                UPDATE user_devices
                SET total_recycled_weight = total_recycled_weight + ?,
                    total_tokens_earned = total_tokens_earned + ?,
                    last_activity_at = CURRENT_TIMESTAMP
                WHERE user_id = ? AND device_id = ?
            ");
            $stmt->execute([$weight_g, $tokens_earned, $paired_user['user_id'], $device_id]);
        } catch (Throwable $e) {
            error_log('recycling_data: user_devices update: ' . $e->getMessage());
        }

        $response = [
            'success' => true,
            'message' => 'Recycling data received successfully',
            'tokens_earned' => $tokens_earned,
            'paired_user' => $paired_user['full_name'],
            'weight' => $weight_g,
        ];
        goto send;
    }

    // Rejected item
    if ($paired_user) {
        $reject_reason = $is_metal ? 'metal detected' : 'not in range';
        $reject_desc = 'Rejected: ' . round($weight_g, 1) . 'g (' . $reject_reason . ')';
        try {
            $stmt = $db->prepare("
                INSERT INTO recycling_activities (user_id, sensor_id, bottle_type, tokens_earned, created_at)
                VALUES (?, ?, ?, 0, NOW())
            ");
            $stmt->execute([$paired_user['user_id'], $device_id, $reject_desc]);
        } catch (Throwable $e) {
            error_log('recycling_data: recycling_activities reject: ' . $e->getMessage());
            $response = ['success' => false, 'error' => 'Save rejected failed', 'step' => 'insert_rejected', 'debug' => $e->getMessage()];
            goto send;
        }
    }

    $response = [
        'success' => true,
        'message' => 'Data recorded (item rejected)',
        'tokens_earned' => 0,
    ];

} catch (Throwable $e) {
    error_log('recycling_data: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    $response = [
        'success' => false,
        'error' => 'Server error',
        'step' => 'exception',
        'debug' => $e->getMessage(),
    ];
}

send:
header('Content-Length: ' . strlen(json_encode($response)));
echo json_encode($response);
if (ob_get_level()) {
    ob_end_flush();
}
flush();
