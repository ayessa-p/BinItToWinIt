<?php
require_once '../config/config.php';
require_admin();

header('Content-Type: application/json');

$db       = Database::getInstance()->getConnection();
$event_id = (int)($_GET['event_id'] ?? 0);

if ($event_id <= 0) {
    echo json_encode(['approved' => 0]);
    exit;
}

$stmt = $db->prepare("
    SELECT COUNT(*) as approved
    FROM event_attendance
    WHERE event_id = ? AND attendance_status = 'approved'
");
$stmt->execute([$event_id]);
$row = $stmt->fetch();

echo json_encode(['approved' => (int)($row['approved'] ?? 0)]);