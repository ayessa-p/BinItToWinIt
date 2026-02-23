<?php
require_once '../config/config.php';
require_admin();

$page_title = 'Manage Users';
$message = '';
$message_type = '';

$db = Database::getInstance()->getConnection();

// Handle ESP32 pairing
if (isset($_POST['pair_device'])) {
    $user_id = (int)$_POST['user_id'];
    $device_id = $_POST['device_id'];
    $device_name = $_POST['device_name'] ?? '';
    
    try {
        // Check if device is already actively paired
        $active_check = $db->prepare("SELECT id FROM user_devices WHERE device_id = ? AND is_active = TRUE");
        $active_check->execute([$device_id]);
        
        if ($active_check->fetch()) {
            // Deactivate existing active pairing
            $deactivate = $db->prepare("UPDATE user_devices SET is_active = FALSE, unpaired_at = CURRENT_TIMESTAMP WHERE device_id = ? AND is_active = TRUE");
            $deactivate->execute([$device_id]);
        }
        
        // Check if there's an inactive pairing record for this device
        $inactive_check = $db->prepare("SELECT id FROM user_devices WHERE device_id = ? AND is_active = FALSE");
        $inactive_check->execute([$device_id]);
        $inactive_pairing = $inactive_check->fetch();
        
        if ($inactive_pairing) {
            // Reactivate existing inactive pairing
            $stmt = $db->prepare("
                UPDATE user_devices 
                SET user_id = ?, device_name = ?, is_active = TRUE, paired_at = CURRENT_TIMESTAMP, unpaired_at = NULL 
                WHERE device_id = ?
            ");
            $stmt->execute([$user_id, $device_name, $device_id]);
        } else {
            // Create new pairing
            $stmt = $db->prepare("
                INSERT INTO user_devices (user_id, device_id, device_name, is_active, paired_at) 
                VALUES (?, ?, ?, TRUE, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$user_id, $device_id, $device_name]);
        }
        
        $message = 'User-Device pairing successful!';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = 'Error pairing device to user: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Handle user status change
if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    try {
        $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'User status updated successfully!';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = 'Error updating user status.';
        $message_type = 'error';
    }
}

// Handle make admin
if (isset($_GET['make_admin'])) {
    $id = (int)$_GET['make_admin'];
    try {
        $stmt = $db->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'User promoted to admin successfully!';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = 'Error promoting user.';
        $message_type = 'error';
    }
}

// Handle device unpairing
if (isset($_POST['unpair_device'])) {
    $device_id = $_POST['device_id'];
    $user_id = $_POST['user_id'];
    
    // Validate inputs
    if (empty($device_id) || empty($user_id)) {
        $message = 'Device ID and User ID are required.';
        $message_type = 'error';
    } else {
        try {
            // Check if device exists and belongs to user
            $check = $db->prepare("
                SELECT ud.id, ud.device_id, ud.user_id 
                FROM user_devices ud 
                WHERE ud.device_id = ? AND ud.user_id = ? AND ud.is_active = TRUE
            ");
            $check->execute([$device_id, $user_id]);
            $device = $check->fetch();
            
            if (!$device) {
                $message = 'Device not found or not paired to this user.';
                $message_type = 'error';
            } else {
                // Unpair the device
                $stmt = $db->prepare("
                    UPDATE user_devices 
                    SET is_active = FALSE, unpaired_at = CURRENT_TIMESTAMP 
                    WHERE user_id = ? AND device_id = ?
                ");
                $stmt->execute([$user_id, $device_id]);
                
                $message = 'Device unpaired successfully!';
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = 'Error unpairing device: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get all users
$stmt = $db->query("
    SELECT id, student_id, email, full_name, course, year_level, eco_tokens, 
           is_active, is_admin, created_at, last_login 
    FROM users 
    ORDER BY created_at DESC
");
$users = $stmt->fetchAll();

include '../includes/admin_header.php';
?>

<div class="admin-content">
    <div class="admin-container">
        <div class="admin-page-header">
            <h1 class="admin-page-title">Manage Users</h1>
            <p class="admin-page-subtitle">View users, activate/deactivate accounts, promote to admin</p>
            <div class="admin-breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/index.php">Home</a> / Users
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: var(--spacing-md);">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-section">
            <h2 class="admin-section-title">📊 User Recycling Statistics</h2>
            <div class="stats-grid">
                <?php foreach ($users as $user): ?>
                    <div class="stat-card">
                        <div class="stat-header">
                            <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                            <small><?php echo htmlspecialchars($user['student_id']); ?></small>
                        </div>
                        <div class="stat-body">
                            <div class="stat-item">
                                <span class="stat-label">Total Tokens:</span>
                                <span class="stat-value"><?php echo format_tokens($user['eco_tokens']); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Total Bottles Recycled:</span>
                                <span class="stat-value"><?php 
                                    $stmt = $db->prepare("SELECT COUNT(*) as total_bottles FROM recycling_activities WHERE user_id = ?");
                                    $stmt->execute([$user['id']]);
                                    $result = $stmt->fetch();
                                    echo $result && $result['total_bottles'] ? (int)$result['total_bottles'] . ' bottle(s)' : '0 bottle(s)';
                                ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Paired Device:</span>
                                <span class="stat-value"><?php 
                                    $stmt = $db->prepare("SELECT device_name FROM user_devices WHERE user_id = ? AND is_active = TRUE");
                                    $stmt->execute([$user['id']]);
                                    $device = $stmt->fetch();
                                    echo $device ? htmlspecialchars($device['device_name']) : 'None';
                                ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="admin-section">
            <h2 class="admin-section-title">🔗 Pair ESP32 Device to User</h2>
            <div class="grid grid-2">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Pair Device</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="pair_device" value="1">
                            
                            <div class="form-group">
                                <label>Select User:</label>
                                <select name="user_id" required>
                                    <option value="">Choose User...</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['full_name'] . ' (' . $user['student_id'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>ESP32 Device ID:</label>
                                <input type="text" name="device_id" placeholder="e.g., ESP32_001" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Device Name (Optional):</label>
                                <input type="text" name="device_name" placeholder="e.g., Main Recycling Station">
                            </div>
                            
                            <button type="submit" class="admin-btn admin-btn-primary">Pair Device to User</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-section">
            <h2 class="admin-section-title">All Users (<?php echo count($users); ?>)</h2>
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Course</th>
                            <th>Tokens</th>
                            <th>Total Bottles Recycled</th>
                            <th>Paired Device</th>
                            <th>Status</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['course'] ?: 'N/A'); ?></td>
                            <td><strong><?php echo format_tokens($user['eco_tokens']); ?></strong></td>
                            <td>
                                <?php 
                                    $stmt = $db->prepare("SELECT COUNT(*) as total_bottles FROM recycling_activities WHERE user_id = ?");
                                    $stmt->execute([$user['id']]);
                                    $result = $stmt->fetch();
                                    echo $result && $result['total_bottles'] ? (int)$result['total_bottles'] : 0;
                                ?>
                            </td>
                            <td>
                                <?php 
                                    $stmt = $db->prepare("SELECT device_name FROM user_devices WHERE user_id = ? AND is_active = TRUE");
                                    $stmt->execute([$user['id']]);
                                    $device = $stmt->fetch();
                                    echo $device ? htmlspecialchars($device['device_name']) : 'None';
                                ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $user['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['is_admin']): ?>
                                    <span class="badge badge-info">Admin</span>
                                <?php else: ?>
                                    <span class="badge">User</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex; flex-direction:column; gap:0.35rem;">
                                    <a href="tokens.php?user_id=<?php echo $user['id']; ?>" 
                                       class="admin-btn admin-btn-secondary admin-btn-sm"
                                       style="width:100%; text-align:center;">
                                        Manage Tokens
                                    </a>
                                    <?php if (!$user['is_admin']): ?>
                                        <a href="?make_admin=<?php echo $user['id']; ?>" 
                                           class="admin-btn admin-btn-primary admin-btn-sm"
                                           style="width:100%; text-align:center;"
                                           onclick="return confirm('Promote this user to admin?');">
                                            Make Admin
                                        </a>
                                    <?php endif; ?>
                                    <a href="?toggle_status=<?php echo $user['id']; ?>" 
                                       class="admin-btn admin-btn-<?php echo $user['is_active'] ? 'danger' : 'primary'; ?> admin-btn-sm"
                                       style="width:100%; text-align:center;">
                                        <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>
