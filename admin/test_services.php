<?php
require_once '../config/config.php';
require_admin();

$db = Database::getInstance()->getConnection();

echo "<h2>Service Management Test</h2>";

// Check if service_requests table exists
$stmt = $db->query("SHOW TABLES LIKE 'service_requests'");
$table_exists = $stmt->rowCount() > 0;

echo "<p><strong>Service Requests Table Exists:</strong> " . ($table_exists ? "YES ✅" : "NO ❌") . "</p>";

if ($table_exists) {
    // Get service counts
    $pending = $db->query("SELECT COUNT(*) FROM service_requests WHERE status = 'pending'")->fetchColumn();
    $in_progress = $db->query("SELECT COUNT(*) FROM service_requests WHERE status = 'in_progress'")->fetchColumn();
    $completed_today = $db->query("SELECT COUNT(*) FROM service_requests WHERE status = 'completed' AND DATE(created_at) = CURDATE()")->fetchColumn();
    
    echo "<p><strong>Pending Requests:</strong> " . $pending . "</p>";
    echo "<p><strong>In Progress:</strong> " . $in_progress . "</p>";
    echo "<p><strong>Completed Today:</strong> " . $completed_today . "</p>";
    
    // Show recent requests
    $stmt = $db->query("SELECT * FROM service_requests ORDER BY created_at DESC LIMIT 5");
    $requests = $stmt->fetchAll();
    
    echo "<h3>Recent Service Requests:</h3>";
    if (empty($requests)) {
        echo "<p>No service requests found.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Type</th><th>Status</th><th>Created</th></tr>";
        foreach ($requests as $req) {
            echo "<tr>";
            echo "<td>" . $req['id'] . "</td>";
            echo "<td>" . htmlspecialchars($req['title']) . "</td>";
            echo "<td>" . $req['service_type'] . "</td>";
            echo "<td>" . $req['status'] . "</td>";
            echo "<td>" . $req['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

echo "<p><a href='index.php'>← Back to Admin Dashboard</a></p>";
?>
