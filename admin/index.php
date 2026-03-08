<?php
require_once '../config/config.php';
require_admin();

$page_title = 'Admin Dashboard';
$user_id = get_user_id();

// Get statistics
$db = Database::getInstance()->getConnection();

$stats = [];

// Total users
$stmt = $db->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $stmt->fetchColumn();

// Active users
$stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
$stats['active_users'] = $stmt->fetchColumn();

// Total tokens distributed
$stmt = $db->query("SELECT SUM(amount) as total FROM transactions WHERE transaction_type = 'earned'");
$stats['total_tokens_distributed'] = $stmt->fetchColumn() ?: 0;

// Total tokens redeemed
$stmt = $db->query("SELECT SUM(amount) as total FROM transactions WHERE transaction_type = 'redeemed'");
$stats['total_tokens_redeemed'] = $stmt->fetchColumn() ?: 0;

// Total recycling activities
$stmt = $db->query("SELECT COUNT(*) as count FROM recycling_activities");
$stats['total_recycling'] = $stmt->fetchColumn();

// Total rewards
$stmt = $db->query("SELECT COUNT(*) as count FROM rewards");
$stats['total_rewards'] = $stmt->fetchColumn();

// Total redemptions
$stmt = $db->query("SELECT COUNT(*) as count FROM redemptions");
$stats['total_redemptions'] = $stmt->fetchColumn();

// Service requests statistics
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM service_requests WHERE status = 'pending'");
    $stats['pending_service_requests'] = $stmt->fetchColumn();
} catch (Exception $e) {
    $stats['pending_service_requests'] = 0;
    error_log("Service requests query error: " . $e->getMessage());
}

try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM service_requests WHERE status = 'in_progress'");
    $stats['in_progress_service_requests'] = $stmt->fetchColumn();
} catch (Exception $e) {
    $stats['in_progress_service_requests'] = 0;
    error_log("Service requests query error: " . $e->getMessage());
}

try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM service_requests WHERE status = 'completed' AND DATE(created_at) = CURDATE()");
    $stats['completed_service_requests_today'] = $stmt->fetchColumn();
} catch (Exception $e) {
    $stats['completed_service_requests_today'] = 0;
    error_log("Service requests query error: " . $e->getMessage());
}

try {
    $stmt = $db->query("SELECT SUM(tokens_charged) as total FROM service_requests WHERE status = 'completed' AND DATE(created_at) = CURDATE()");
    $stats['tokens_earned_today'] = $stmt->fetchColumn() ?: 0;
} catch (Exception $e) {
    $stats['tokens_earned_today'] = 0;
    error_log("Service requests query error: " . $e->getMessage());
}

// Removed Top Recyclers from dashboard; moved to Reports
$top_recyclers = [];

include '../includes/admin_header.php';
?>

<div class="admin-content">
    <div class="admin-container">
        <div class="admin-page-header">
            <h1 class="admin-page-title">Dashboard</h1>
            <p class="admin-page-subtitle">System overview</p>
            <div class="admin-breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/index.php">Home</a> / Dashboard
            </div>
        </div>
        
        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fa-solid fa-users"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_users']); ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fa-solid fa-coins"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_tokens_distributed']); ?></h3>
                    <p>Tokens Distributed</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fa-solid fa-recycle"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_recycling']); ?></h3>
                    <p>Recycling Activities</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon yellow"><i class="fa-solid fa-gift"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_rewards']); ?></h3>
                    <p>Available Rewards</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fa-solid fa-cogs"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['pending_service_requests']); ?></h3>
                    <p>Pending Services</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fa-solid fa-clock"></i></div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['in_progress_service_requests']); ?></h3>
                    <p>In Progress</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon green"><i class="fa-solid fa-coins"></i></div>
                <div class="stat-info">
                    <h3><?php echo format_tokens($stats['tokens_earned_today']); ?></h3>
                    <p>Tokens Earned Today</p>
                </div>
            </div>
        </div>
        
        <!-- Management Sections -->
        <div class="admin-section">
            <h2 class="admin-section-title">Quick Actions</h2>
            
            <div class="action-grid">
                <a href="rewards.php" class="action-card">
                    <div class="action-icon"><i class="fa-solid fa-gift"></i></div>
                    <h3>Rewards & Stocks</h3>
                    <p>Edit rewards and update stock quantities</p>
                </a>
                
                <a href="messages.php" class="action-card">
                    <div class="action-icon"><i class="fa-solid fa-envelope"></i></div>
                    <h3>Messages</h3>
                    <p>View and reply to messages</p>
                </a>
                
                <a href="automation.php" class="action-card">
                    <div class="action-icon"><i class="fa-solid fa-cogs"></i></div>
                    <h3>Services</h3>
                    <p>Manage printing, internet & room requests</p>
                </a>
                
                <a href="events.php" class="action-card">
                    <div class="action-icon"><i class="fa-solid fa-calendar-days"></i></div>
                    <h3>Events</h3>
                    <p>Create and manage events</p>
                </a>
                
                <a href="projects.php" class="action-card">
                    <div class="action-icon"><i class="fa-solid fa-diagram-project"></i></div>
                    <h3>Projects</h3>
                    <p>Manage organization projects</p>
                </a>
                
                <a href="news.php" class="action-card">
                    <div class="action-icon"><i class="fa-solid fa-newspaper"></i></div>
                    <h3>News</h3>
                    <p>Create and publish news</p>
                </a>
                
                <a href="users.php" class="action-card">
                    <div class="action-icon"><i class="fa-solid fa-users"></i></div>
                    <h3>Users</h3>
                    <p>Manage user accounts</p>
                </a>
                
                <a href="tokens.php" class="action-card">
                    <div class="action-icon"><i class="fa-solid fa-coins"></i></div>
                    <h3>Tokens</h3>
                    <p>Adjust token balances</p>
                </a>
                
                <a href="redemptions.php" class="action-card">
                    <div class="action-icon"><i class="fa-solid fa-circle-check"></i></div>
                    <h3>Redemptions</h3>
                    <p>Manage reward redemptions</p>
                </a>
                
                <a href="sensor_monitor.php" class="action-card">
                    <div class="action-icon"><i class="fa-solid fa-microchip"></i></div>
                    <h3>Sensor Monitor</h3>
                    <p>View live bin & sensor status</p>
                </a>
                
                <a href="api_keys.php" class="action-card">
                    <div class="action-icon"><i class="fa-solid fa-key"></i></div>
                    <h3>API Keys</h3>
                    <p>Manage ESP32 API keys</p>
                </a>
                
                <a href="reports.php" class="action-card">
                    <div class="action-icon"><i class="fa-solid fa-file-lines"></i></div>
                    <h3>Reports</h3>
                    <p>Generate system reports</p>
                </a>
            </div>
        </div>
        
        <!-- Top Recyclers moved to Reports -->
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>
