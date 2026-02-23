<?php
/**
 * Test script: simulates ESP32 POST to recycling_data.php
 * Open in browser: http://192.168.100.11/BinItToWinIt/api/test_recycling_post.php
 * Or run: php test_recycling_post.php
 */
$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['SCRIPT_NAME']) . '/recycling_data.php';
if (php_sapi_name() === 'cli') {
    $url = 'http://localhost/BinItToWinIt/api/recycling_data.php';
}

$payload = [
    'weight' => 15.5,
    'distance' => 5.2,
    'is_metal' => false,
    'accepted' => true,
    'device_id' => 'ESP32_001',
    'timestamp' => (int)(microtime(true) * 1000)
];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if (php_sapi_name() === 'cli') {
    echo "URL: $url\n";
    echo "HTTP Code: $httpCode\n";
    if ($error) echo "CURL Error: $error\n";
    echo "Response: " . ($response ?: '(empty)') . "\n";
    exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'test_url' => $url,
    'http_code' => (int)$httpCode,
    'curl_error' => $error ?: null,
    'response_body' => $response ? json_decode($response) : null,
    'raw_response' => $response
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
