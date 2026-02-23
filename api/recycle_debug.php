<?php
/**
 * Debug script for recycling API.
 * Open in browser: http://YOUR_SERVER/BinItToWinIt/api/recycle_debug.php
 * Shows DB connection, tables, columns, and a test POST to recycling_data.php.
 */
header('Content-Type: application/json; charset=utf-8');

$checks = [];
$all_ok = true;

try {
    require_once __DIR__ . '/../config/config.php';
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'Config failed', 'debug' => $e->getMessage()], JSON_PRETTY_PRINT);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $checks['db_connection'] = 'OK';
} catch (Throwable $e) {
    $checks['db_connection'] = 'FAIL: ' . $e->getMessage();
    $all_ok = false;
    echo json_encode(['ok' => false, 'checks' => $checks], JSON_PRETTY_PRINT);
    exit;
}

$tables = ['users', 'sensor_readings', 'user_devices', 'recycling_activities'];
foreach ($tables as $t) {
    try {
        $stmt = $db->query("SELECT 1 FROM `$t` LIMIT 1");
        $checks["table_$t"] = 'OK';
    } catch (Throwable $e) {
        $checks["table_$t"] = 'MISSING or error: ' . $e->getMessage();
        $all_ok = false;
    }
}

// Required columns for recycling_activities (accepted + rejected inserts)
$cols_ra = ['user_id', 'sensor_id', 'bottle_type', 'tokens_earned', 'created_at'];
try {
    $stmt = $db->query("SHOW COLUMNS FROM recycling_activities");
    $existing = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $missing = array_diff($cols_ra, $existing);
    $checks['recycling_activities_columns'] = $missing ? 'Missing: ' . implode(', ', $missing) : 'OK';
    if ($missing) $all_ok = false;
} catch (Throwable $e) {
    $checks['recycling_activities_columns'] = 'Error: ' . $e->getMessage();
    $all_ok = false;
}

// user_devices: need user_id, device_id, is_active
$cols_ud = ['user_id', 'device_id', 'is_active'];
try {
    $stmt = $db->query("SHOW COLUMNS FROM user_devices");
    $existing = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $missing = array_diff($cols_ud, $existing);
    $checks['user_devices_columns'] = $missing ? 'Missing: ' . implode(', ', $missing) : 'OK';
    if ($missing) $all_ok = false;
} catch (Throwable $e) {
    $checks['user_devices_columns'] = 'Error: ' . $e->getMessage();
    $all_ok = false;
}

// Any active pairing for ESP32_001?
try {
    $stmt = $db->prepare("SELECT ud.user_id, u.full_name FROM user_devices ud JOIN users u ON u.id = ud.user_id WHERE ud.device_id = ? AND (ud.is_active = 1 OR ud.is_active = TRUE)");
    $stmt->execute(['ESP32_001']);
    $pair = $stmt->fetch(PDO::FETCH_ASSOC);
    $checks['pairing_ESP32_001'] = $pair ? 'Paired to: ' . $pair['full_name'] : 'No active pairing (pair at dashboard)';
} catch (Throwable $e) {
    $checks['pairing_ESP32_001'] = 'Error: ' . $e->getMessage();
}

// Test POST to recycling_data.php (accepted then rejected)
$base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['SCRIPT_NAME']);
$url = $base . '/recycling_data.php';

$test_accepted = ['weight' => 15, 'distance' => 5, 'is_metal' => false, 'accepted' => true, 'device_id' => 'ESP32_001', 'timestamp' => time() * 1000];
$test_rejected = ['weight' => 8, 'distance' => 5, 'is_metal' => false, 'accepted' => false, 'device_id' => 'ESP32_001', 'timestamp' => time() * 1000];

$ch = curl_init($url);
curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($test_accepted), CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
$res_accepted = curl_exec($ch);
$code_accepted = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$ch = curl_init($url);
curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($test_rejected), CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
$res_rejected = curl_exec($ch);
$code_rejected = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$ja = $res_accepted ? json_decode($res_accepted, true) : [];
$jr = $res_rejected ? json_decode($res_rejected, true) : [];
$checks['test_post_accepted'] = ($code_accepted === 200 && $res_accepted) ? ('HTTP 200 - ' . (!empty($ja['success']) ? 'success' : ($ja['error'] ?? $ja['step'] ?? '') . (!empty($ja['debug']) ? ' | ' . $ja['debug'] : ''))) : "HTTP $code_accepted - " . substr($res_accepted ?: 'empty', 0, 200);
$checks['test_post_rejected'] = ($code_rejected === 200 && $res_rejected) ? ('HTTP 200 - ' . (!empty($jr['success']) ? 'success' : ($jr['error'] ?? $jr['step'] ?? '') . (!empty($jr['debug']) ? ' | ' . $jr['debug'] : ''))) : "HTTP $code_rejected - " . substr($res_rejected ?: 'empty', 0, 200);

if (!(!empty($ja['success']))) $all_ok = false;
if (!(!empty($jr['success']))) $all_ok = false;

$out = ['ok' => $all_ok, 'checks' => $checks, 'test_url' => $url];
if (!$all_ok && $res_accepted) $out['response_accepted'] = $ja;
if (!$all_ok && $res_rejected) $out['response_rejected'] = $jr;
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
