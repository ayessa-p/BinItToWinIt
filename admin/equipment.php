<?php
require_once '../config/config.php';
require_admin();

$page_title = 'Equipment Management';
$admin_user_id = get_user_id();
$db = Database::getInstance()->getConnection();

// Initialize message variables
$message = '';
$message_type = '';

// Handle equipment creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_equipment'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
    } else {
        $id = isset($_POST['equipment_id']) ? (int)$_POST['equipment_id'] : 0;
        $name = sanitize_input($_POST['name'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $category = sanitize_input($_POST['category'] ?? 'equipment');
        $type = sanitize_input($_POST['type'] ?? '');
        $total_quantity = (int)($_POST['total_quantity'] ?? 0);
        $location = sanitize_input($_POST['location'] ?? '');
        $condition_status = sanitize_input($_POST['condition_status'] ?? 'good');
        $requires_approval = isset($_POST['requires_approval']);
        $min_user_level = sanitize_input($_POST['min_user_level'] ?? 'student');
        $is_active = isset($_POST['is_active']);
        
        try {
            if ($id > 0) {
                // Update existing equipment
                $stmt = $db->prepare("SELECT total_quantity, available_quantity FROM resources WHERE id = ?");
                $stmt->execute([$id]);
                $current = $stmt->fetch();
                if (!$current) {
                    throw new Exception('Equipment not found.');
                }

                $old_total = (int)($current['total_quantity'] ?? 0);
                $old_available = (int)($current['available_quantity'] ?? 0);
                $delta = $total_quantity - $old_total;
                $new_available = $old_available + $delta;
                if ($new_available < 0) $new_available = 0;
                if ($new_available > $total_quantity) $new_available = $total_quantity;

                $stmt = $db->prepare("
                    UPDATE resources SET 
                    name = ?, description = ?, category = ?, type = ?, total_quantity = ?, available_quantity = ?,
                    location = ?, condition_status = ?, requires_approval = ?,
                    min_user_level = ?, is_active = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$name, $description, $category, $type, $total_quantity, $new_available,
                               $location, $condition_status, $requires_approval, $min_user_level, $is_active, $id]);
                $message = 'Equipment updated successfully!';
            } else {
                // Create new equipment
                $stmt = $db->prepare("
                    INSERT INTO resources 
                    (name, description, category, type, total_quantity, available_quantity, location, 
                     condition_status, requires_approval, min_user_level, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $description, $category, $type, $total_quantity, 
                               $total_quantity, $location, $condition_status, $requires_approval, 
                               $min_user_level, $is_active]);
                $message = 'Equipment added successfully!';
            }
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Database error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Handle equipment deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_equipment'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
    } else {
        $equipment_id = (int)($_POST['equipment_id'] ?? 0);
        try {
            $stmt = $db->prepare("SELECT name FROM resources WHERE id = ?");
            $stmt->execute([$equipment_id]);
            $equipment = $stmt->fetch();

            if (!$equipment) {
                $message = 'Equipment not found.';
                $message_type = 'error';
            } else {
                $stmt = $db->prepare("DELETE FROM resources WHERE id = ?");
                $stmt->execute([$equipment_id]);
                $message = "Equipment '{$equipment['name']}' deleted successfully!";
                $message_type = 'success';
            }
        } catch (Exception $e) {
            $message = 'Database error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Editing context
$editing_equipment = null;
if (isset($_GET['edit_equipment'])) {
    $edit_id = (int)$_GET['edit_equipment'];
    $stmt = $db->prepare("SELECT * FROM resources WHERE id = ? AND category = 'equipment'");
    $stmt->execute([$edit_id]);
    $editing_equipment = $stmt->fetch();
}

// Get all equipment
$equipment = $db->query("SELECT * FROM resources WHERE category = 'equipment' ORDER BY name ASC")->fetchAll();

// Get equipment requests
$equipment_requests = [];
try {
    $stmt = $db->query("
        SELECT sr.*, u.full_name, u.student_id, r.name as equipment_name
        FROM service_requests sr
        JOIN users u ON sr.user_id = u.id
        JOIN resources r ON sr.resource_id = r.id
        WHERE sr.service_type = 'equipment'
        ORDER BY sr.created_at DESC
    ");
    $equipment_requests = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Equipment requests query error: " . $e->getMessage());
}

include '../includes/admin_header.php';
?>

<div class="admin-content">
    <div class="admin-container">
        <div class="admin-page-header">
            <h1 class="admin-page-title">Equipment Management (Deprecated)</h1>
            <p class="admin-page-subtitle">Equipment is now managed from Automation Management &gt; Equipment Management.</p>
            <div class="admin-breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/index.php">Home</a> / Equipment Management
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: var(--spacing-md);">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Equipment Inventory Management (read-only notice) -->
        <div class="admin-section" id="equipment-management">
            <h2 class="admin-section-title">🔧 Equipment Inventory</h2>
            <div class="card">
                <div class="card-body">
                    <p style="margin:0 0 1rem; color: var(--medium-gray);">
                        Equipment inventory is now managed in <strong>Automation Management &gt; Equipment Management</strong>.
                        This page is kept only for viewing existing equipment requests.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Equipment Requests -->
        <div class="admin-section">
            <h2 class="admin-section-title">📋 Equipment Requests</h2>
            
            <div class="card">
                <div class="card-body">
                    <div class="admin-table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>User</th>
                                    <th>Equipment</th>
                                    <th>Quantity</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($equipment_requests)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align:center; padding: var(--spacing-md); color: var(--medium-gray);">
                                            No equipment requests found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($equipment_requests as $request): ?>
                                        <tr>
                                            <td><strong>#<?php echo (int)$request['id']; ?></strong></td>
                                            <td>
                                                <div><?php echo htmlspecialchars($request['full_name']); ?></div>
                                                <small style="color: var(--medium-gray);"><?php echo htmlspecialchars($request['student_id']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($request['equipment_name']); ?></td>
                                            <td><?php echo (int)$request['quantity']; ?></td>
                                            <td><?php echo htmlspecialchars($request['purpose'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo match($request['status']) {
                                                        'pending' => 'warning',
                                                        'approved' => 'info',
                                                        'in_progress' => 'primary',
                                                        'completed' => 'success',
                                                        'cancelled' => 'danger',
                                                        default => 'secondary'
                                                    }; 
                                                ?>">
                                                    <?php echo ucfirst($request['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($request['created_at'])); ?></td>
                                            <td>
                                                <!-- Actions for equipment requests would go here -->
                                                <span style="color: var(--medium-gray); font-size: 0.9rem;">
                                                    View details
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
        
        <div style="margin-top: 2rem; text-align: center;">
            <a href="index.php" class="admin-btn admin-btn-secondary">← Back to Dashboard</a>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>
