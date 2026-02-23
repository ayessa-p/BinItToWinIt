<?php
require_once '../config/config.php';

$page_title = 'Pair ESP32 Device';
$message = '';
$message_type = '';

$db = Database::getInstance()->getConnection();

// Handle device pairing
if (isset($_POST['pair_device'])) {
    $device_id = $_POST['device_id'];
    $device_name = $_POST['device_name'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    try {
        // First, check if there's an active pairing for this device
        $active_check = $db->prepare("SELECT id, user_id, device_name FROM user_devices WHERE device_id = ? AND is_active = TRUE");
        $active_check->execute([$device_id]);
        $active_pairing = $active_check->fetch();
        
        if ($active_pairing) {
            // Device is actively paired
            if ($active_pairing['user_id'] == $user_id) {
                $message = 'This device is already paired to your account!';
                $message_type = 'warning';
            } else {
                // Device is paired to a different user
                $message = 'This device is currently paired to another user. Please contact an administrator.';
                $message_type = 'error';
            }
        } else {
            // No active pairing exists - check if there's an inactive pairing record
            $inactive_check = $db->prepare("SELECT id FROM user_devices WHERE device_id = ? AND is_active = FALSE");
            $inactive_check->execute([$device_id]);
            $inactive_pairing = $inactive_check->fetch();
            
            if ($inactive_pairing) {
                // Device was unpaired - reactivate it for this user
                $stmt = $db->prepare("
                    UPDATE user_devices 
                    SET user_id = ?, device_name = ?, is_active = TRUE, paired_at = CURRENT_TIMESTAMP, unpaired_at = NULL 
                    WHERE device_id = ?
                ");
                $stmt->execute([$user_id, $device_name, $device_id]);
                
                $message = 'Device paired successfully! You can now start recycling.';
                $message_type = 'success';
            } else {
                // Brand new device - create new pairing
                $stmt = $db->prepare("
                    INSERT INTO user_devices (user_id, device_id, device_name, is_active, paired_at) 
                    VALUES (?, ?, ?, TRUE, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$user_id, $device_id, $device_name]);
                
                $message = 'Device paired successfully! You can now start recycling.';
                $message_type = 'success';
            }
        }
    } catch (PDOException $e) {
        $message = 'Error pairing device: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Handle device unpairing
if (isset($_POST['unpair_device'])) {
    $device_id = $_POST['device_id'];
    $user_id = $_SESSION['user_id'];
    
    try {
        $stmt = $db->prepare("
            UPDATE user_devices 
            SET is_active = FALSE, unpaired_at = CURRENT_TIMESTAMP 
            WHERE user_id = ? AND device_id = ?
        ");
        
        $stmt->execute([$user_id, $device_id]);
        
        $message = 'Device unpaired successfully!';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = 'Error unpairing device.';
        $message_type = 'error';
    }
}

// Get user's paired devices
$paired_devices = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("
        SELECT ud.*, u.full_name as user_name 
        FROM user_devices ud 
        JOIN users u ON ud.user_id = u.id 
        WHERE ud.user_id = ? AND ud.is_active = TRUE
        ORDER BY ud.paired_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $paired_devices = $stmt->fetchAll();
}

include '../includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">🔗 Pair ESP32 Device</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-2" style="margin-bottom: 3rem;">
            <!-- Pair New Device -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">🔗 Pair New Device</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="pair_device" value="1">
                        
                        <div class="form-group">
                            <label>ESP32 Device ID:</label>
                            <input type="text" name="device_id" placeholder="Enter device ID from ESP32 display" required>
                            <small style="color: var(--medium-gray);">Look at the ESP32 device screen or ask the administrator for the device ID</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Device Name (Optional):</label>
                            <input type="text" name="device_name" placeholder="e.g., My Home Recycler">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Pair Device</button>
                    </form>
                </div>
            </div>
            
            <!-- My Paired Devices -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📱 My Paired Devices</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($paired_devices)): ?>
                        <p style="color: var(--medium-gray); text-align: center; padding: 2rem;">
                            You haven't paired any devices yet. Pair your first ESP32 device to start recycling!
                        </p>
                    <?php else: ?>
                        <div class="device-list">
                            <?php foreach ($paired_devices as $device): ?>
                                <div class="device-item">
                                    <div class="device-info">
                                        <h4><?php echo htmlspecialchars($device['device_name'] ?: $device['device_id']); ?></h4>
                                        <p><strong>Device ID:</strong> <?php echo htmlspecialchars($device['device_id']); ?></p>
                                        <p><strong>Paired:</strong> <?php echo date('M j, Y g:i A', strtotime($device['paired_at'])); ?></p>
                                        <p><strong>Last Activity:</strong> <?php echo $device['last_activity_at'] ? date('M j, Y g:i A', strtotime($device['last_activity_at'])) : 'No activity yet'; ?></p>
                                        <p><strong>Total Recycled:</strong> <?php echo number_format($device['total_recycled_weight'], 1) . 'g'; ?></p>
                                        <p><strong>Tokens Earned:</strong> <?php echo format_tokens($device['total_tokens_earned']); ?></p>
                                    </div>
                                    <div class="device-actions">
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="unpair_device" value="1">
                                            <input type="hidden" name="device_id" value="<?php echo $device['device_id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Unpair this device?')">Unpair</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.device-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.device-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.device-info {
    flex: 1;
}

.device-info h4 {
    margin: 0 0 0.5rem 0;
    color: var(--primary-color);
}

.device-info p {
    margin: 0.25rem 0;
    font-size: 0.9rem;
}

.device-actions {
    flex: 0;
    display: flex;
    align-items: center;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.btn-danger {
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.btn-danger:hover {
    background: #c82333;
}
</style>

<?php include '../includes/footer.php'; ?>
