<?php
require_once '../config/config.php';
require_admin();

$page_title = 'Manage API Keys';
$message = '';
$message_type = '';

$db = Database::getInstance()->getConnection();

// Handle create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_key'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
    } else {
        $device_id = sanitize_input($_POST['device_id'] ?? '');
        $device_name = sanitize_input($_POST['device_name'] ?? '');
        $location = sanitize_input($_POST['location'] ?? '');
        
        if (empty($device_id)) {
            $message = 'Device ID is required.';
            $message_type = 'error';
        } else {
            try {
                $api_key = bin2hex(random_bytes(32));
                $stmt = $db->prepare("
                    INSERT INTO api_keys (device_id, api_key, device_name, location) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$device_id, $api_key, $device_name, $location]);
                $message = 'API key created successfully! Key: <strong>' . $api_key . '</strong> (Save this key - it will not be shown again)';
                $message_type = 'success';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $message = 'Device ID already exists.';
                } else {
                    $message = 'Database error: ' . $e->getMessage();
                }
                $message_type = 'error';
            }
        }
    }
}

// Handle toggle status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    try {
        $stmt = $db->prepare("UPDATE api_keys SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'API key status updated successfully!';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = 'Error updating API key status.';
        $message_type = 'error';
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $db->prepare("DELETE FROM api_keys WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'API key deleted successfully!';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = 'Error deleting API key.';
        $message_type = 'error';
    }
}

// Get all API keys
$stmt = $db->query("SELECT * FROM api_keys ORDER BY created_at DESC");
$api_keys = $stmt->fetchAll();

include '../includes/admin_header.php';
?>

<div class="admin-content">
    <div class="admin-container">
        <div class="admin-page-header">
            <h1 class="admin-page-title">Manage API Keys</h1>
            <p class="admin-page-subtitle">Generate and manage ESP32 device API keys</p>
            <div class="admin-breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/index.php">Home</a> / API Keys
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: var(--spacing-md);">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-section">
            <h2 class="admin-section-title">Create New API Key</h2>
            <form method="POST" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="admin-form-group">
                    <label class="admin-form-label">Device ID *</label>
                    <input type="text" name="device_id" class="admin-form-input" 
                           placeholder="e.g., BIN001" required>
                </div>
                
                <div class="admin-form-group">
                    <label class="admin-form-label">Device Name</label>
                    <input type="text" name="device_name" class="admin-form-input" 
                           placeholder="e.g., Main Entrance Bin">
                </div>
                
                <div class="admin-form-group">
                    <label class="admin-form-label">Location</label>
                    <input type="text" name="location" class="admin-form-input" 
                           placeholder="e.g., Building A, Ground Floor">
                </div>
                
                <button type="submit" name="create_key" class="admin-btn admin-btn-primary">
                    Generate API Key
                </button>
            </form>
        </div>
        
        <div class="admin-section">
            <h2 class="admin-section-title">All API Keys</h2>
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Device ID</th>
                            <th>Device Name</th>
                            <th>Location</th>
                            <th>API Key</th>
                            <th>Status</th>
                            <th>Last Used</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($api_keys)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: var(--spacing-lg); color: var(--medium-gray);">
                                    No API keys found. Create your first API key above.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($api_keys as $key): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($key['device_id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($key['device_name'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($key['location'] ?: 'N/A'); ?></td>
                                <td>
                                    <code style="font-size: 0.85rem; background: var(--light-gray); padding: 0.25rem 0.5rem; border-radius: 4px;">
                                        <?php echo substr($key['api_key'], 0, 16) . '...'; ?>
                                    </code>
                                </td>
                                <td>
                                    <span class="badge <?php echo $key['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $key['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $key['last_used'] ? date('M j, Y g:i A', strtotime($key['last_used'])) : 'Never'; ?>
                                </td>
                                <td>
                                    <a href="?toggle=<?php echo $key['id']; ?>" 
                                       class="admin-btn admin-btn-<?php echo $key['is_active'] ? 'danger' : 'primary'; ?> admin-btn-sm">
                                        <?php echo $key['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </a>
                                    <a href="?delete=<?php echo $key['id']; ?>" 
                                       class="admin-btn admin-btn-danger admin-btn-sm"
                                       onclick="return confirm('Are you sure you want to delete this API key?');">Delete</a>
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

<?php include '../includes/admin_footer.php'; ?>
