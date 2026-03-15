<?php
/**
 * Activity Poll Endpoint
 * Called every few seconds by the dashboard JS to check for new recycling activities.
 * Returns any recycling_activities rows created after the given Unix timestamp.
 */

require_once '../config/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// Must be logged in
if (!is_logged_in()) {
    echo json_encode(['authenticated' => false, 'activities' => []]);
    exit();
}

$user_id = get_user_id();

// `since` must be a Unix timestamp (integer seconds).
// If missing or invalid, just return empty — JS will retry with a proper value next tick.
$since_raw = $_GET['since'] ?? null;

if ($since_raw === null || !ctype_digit((string)(int)abs((float)$since_raw))) {
    echo json_encode([
        'authenticated' => true,
        'activities'    => [],
        'server_time'   => time(),
    ]);
    exit();
}

// Convert Unix timestamp → MySQL DATETIME in server timezone
$since_unix = (int) $since_raw;
$since_dt   = date('Y-m-d H:i:s', $since_unix);

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("
        SELECT
            id,
            bottle_type,
            tokens_earned,
            created_at
        FROM recycling_activities
        WHERE user_id   = ?
          AND created_at > ?
        ORDER BY created_at ASC
        LIMIT 20
    ");
    $stmt->execute([$user_id, $since_dt]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'authenticated' => true,
        'activities'    => $activities,
        'server_time'   => time(),
    ]);

} catch (PDOException $e) {
    error_log('activity_poll.php PDO error: ' . $e->getMessage());
    echo json_encode([
        'authenticated' => true,
        'activities'    => [],
        'server_time'   => time(),
        'error'         => 'database_error',
    ]);
}
