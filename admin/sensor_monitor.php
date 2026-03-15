<?php
require_once '../config/config.php';
require_admin();

$page_title = 'Sensor Monitor';
$db = Database::getInstance()->getConnection();

// Get recent sensor readings
$stmt = $db->prepare("
    SELECT * FROM sensor_readings 
    ORDER BY created_at DESC 
    LIMIT 50
");
$stmt->execute();
$readings = $stmt->fetchAll();

// Get statistics
$stmt = $db->query("SELECT COUNT(*) FROM sensor_readings WHERE DATE(created_at) = CURDATE()");
$today_readings = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM sensor_readings WHERE accepted = TRUE AND DATE(created_at) = CURDATE()");
$today_accepted = $stmt->fetchColumn();

$stmt = $db->query("SELECT AVG(weight) as avg_weight FROM sensor_readings WHERE accepted = TRUE AND DATE(created_at) = CURDATE()");
$avg_weight = $stmt->fetchColumn();

include '../includes/admin_header.php';
?>

<div class="admin-content">
    <div class="admin-container">
        <div class="admin-page-header">
            <h1 class="admin-page-title">🔬 Sensor Monitor</h1>
            <p class="admin-page-subtitle">Real-time Arduino/ESP32 sensor data</p>
            <div class="admin-breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/index.php">Home</a> / Sensor Monitor
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fa-solid fa-microchip"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($today_readings); ?></h3>
                    <p>Today's Readings</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon green"><i class="fa-solid fa-check-circle"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($today_accepted); ?></h3>
                    <p>Items Accepted</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fa-solid fa-weight"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($avg_weight ?? 0, 1); ?>g</h3>
                    <p>Average Weight</p>
                </div>
            </div>
        </div>
        
        <!-- Sensor Readings Table -->
        <div class="admin-section">
            <h2 class="admin-section-title">📊 Recent Sensor Readings</h2>
            
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Device ID</th>
                            <th>Weight</th>
                            <th>Metal</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($readings)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: var(--spacing-lg); color: var(--medium-gray);">
                                    No sensor readings found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($readings as $reading): ?>
                                <tr>
                                    <td>
                                        <?php echo date('M j, Y g:i A', strtotime($reading['created_at'])); ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?php echo htmlspecialchars($reading['device_id']); ?></span>
                                    </td>
                                    <td><?php echo number_format($reading['weight'], 1); ?>g</td>
                                    <td>
                                        <span class="badge badge-<?php echo $reading['is_metal'] ? 'warning' : 'success'; ?>">
                                            <?php echo $reading['is_metal'] ? 'Yes' : 'No'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $reading['accepted'] ? 'success' : 'danger'; ?>">
                                            <?php echo $reading['accepted'] ? 'Accepted' : 'Rejected'; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh every 5 seconds
setTimeout(function() {
    location.reload();
}, 5000);
</script>

<?php include '../includes/admin_footer.php'; ?>
