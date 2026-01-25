<?php
require_once '../config/config.php';
require_admin();

$page_title = 'Manage Rewards';
$message = '';
$message_type = '';

$db = Database::getInstance()->getConnection();

// Handle create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
    } else {
        $id = isset($_POST['reward_id']) ? (int)$_POST['reward_id'] : 0;
        $name = sanitize_input($_POST['name'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $token_cost = (float)($_POST['token_cost'] ?? 0);
        $category = sanitize_input($_POST['category'] ?? '');
        $stock_quantity = isset($_POST['stock_quantity']) && $_POST['stock_quantity'] !== '' ? (int)$_POST['stock_quantity'] : -1;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name) || empty($description) || $token_cost <= 0) {
            $message = 'Please fill in all required fields with valid values.';
            $message_type = 'error';
        } else {
            try {
                if ($id > 0) {
                    $stmt = $db->prepare("
                        UPDATE rewards SET name = ?, description = ?, token_cost = ?, 
                        category = ?, stock_quantity = ?, is_active = ? WHERE id = ?
                    ");
                    $stmt->execute([$name, $description, $token_cost, $category, $stock_quantity, $is_active, $id]);
                    $message = 'Reward updated successfully!';
                } else {
                    $stmt = $db->prepare("
                        INSERT INTO rewards (name, description, token_cost, category, stock_quantity, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $description, $token_cost, $category, $stock_quantity, $is_active]);
                    $message = 'Reward created successfully!';
                }
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = 'Database error: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $db->prepare("DELETE FROM rewards WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Reward deleted successfully!';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = 'Error deleting reward.';
        $message_type = 'error';
    }
}

// Get reward to edit
$edit_reward = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM rewards WHERE id = ?");
    $stmt->execute([$id]);
    $edit_reward = $stmt->fetch();
}

// Get all rewards
$stmt = $db->query("SELECT * FROM rewards ORDER BY category, token_cost ASC");
$rewards = $stmt->fetchAll();

include '../includes/admin_header.php';
?>

<div class="admin-content">
    <div class="admin-container">
        <div class="admin-page-header">
            <h1 class="admin-page-title">Manage Rewards & Stock Inventory</h1>
            <p class="admin-page-subtitle">Edit rewards, update stock quantities, and manage inventory levels</p>
            <div class="admin-breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/index.php">Home</a> / Rewards
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: var(--spacing-md);">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-section" style="background: var(--card-bg); border-radius: 8px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);">
            <h2 class="admin-section-title" style="font-size: 1.3rem; font-weight: 600; color: var(--text-primary); margin: 0 0 20px 0; padding-bottom: 15px; border-bottom: 2px solid var(--border-color);"><?php echo $edit_reward ? 'Edit Reward' : 'Create New Reward'; ?></h2>
            <form method="POST" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <?php if ($edit_reward): ?>
                    <input type="hidden" name="reward_id" value="<?php echo $edit_reward['id']; ?>">
                <?php endif; ?>
                
                <div class="admin-form-group">
                    <label class="admin-form-label">Reward Name *</label>
                    <input type="text" name="name" class="admin-form-input" 
                           value="<?php echo $edit_reward ? htmlspecialchars($edit_reward['name']) : ''; ?>" required>
                </div>
                
                <div class="admin-form-group">
                    <label class="admin-form-label">Description *</label>
                    <textarea name="description" class="admin-form-textarea" required><?php echo $edit_reward ? htmlspecialchars($edit_reward['description']) : ''; ?></textarea>
                </div>
                
                <div class="admin-form-group">
                    <label class="admin-form-label">Token Cost *</label>
                    <input type="number" name="token_cost" class="admin-form-input" 
                           step="0.01" min="0.01" 
                           value="<?php echo $edit_reward ? $edit_reward['token_cost'] : ''; ?>" required>
                </div>
                
                <div class="admin-form-group">
                    <label class="admin-form-label">Category</label>
                    <input type="text" name="category" class="admin-form-input" 
                           value="<?php echo $edit_reward ? htmlspecialchars($edit_reward['category']) : ''; ?>" 
                           placeholder="e.g., Services, Merchandise, Electronics">
                </div>
                
                <div class="admin-form-group">
                    <label class="admin-form-label">Stock Quantity *</label>
                    <input type="number" name="stock_quantity" class="admin-form-input" 
                           value="<?php echo $edit_reward && $edit_reward['stock_quantity'] != -1 ? $edit_reward['stock_quantity'] : ''; ?>" 
                           placeholder="Enter quantity or leave empty for unlimited"
                           min="-1">
                    <small style="color: var(--medium-gray);">
                        Enter a number for limited stock, or leave empty for unlimited stock (-1)
                    </small>
                </div>
                
                <div class="admin-form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="is_active" 
                               <?php echo (!$edit_reward || $edit_reward['is_active']) ? 'checked' : ''; ?>>
                        <span>Active</span>
                    </label>
                </div>
                
                <button type="submit" class="admin-btn admin-btn-primary">
                    <?php echo $edit_reward ? 'Update Reward' : 'Create Reward'; ?>
                </button>
                <?php if ($edit_reward): ?>
                    <a href="rewards.php" class="admin-btn admin-btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="admin-section">
            <h2 class="admin-section-title">All Rewards & Stock Inventory</h2>
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Token Cost</th>
                            <th>Stock Quantity</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rewards)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 30px; color: #7f8c8d;">
                                    No rewards found. Create your first reward above.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rewards as $reward): ?>
                            <tr>
                                <td><strong style="color: #2c3e50;"><?php echo htmlspecialchars($reward['name']); ?></strong></td>
                                <td style="color: #7f8c8d;"><?php echo htmlspecialchars($reward['category'] ?: 'N/A'); ?></td>
                                <td><strong style="color: #2c3e50;"><?php echo format_tokens($reward['token_cost']); ?></strong></td>
                                <td>
                                    <?php if ($reward['stock_quantity'] == -1): ?>
                                        <span class="badge badge-info">Unlimited</span>
                                    <?php else: ?>
                                        <span class="badge <?php echo $reward['stock_quantity'] > 10 ? 'badge-success' : ($reward['stock_quantity'] > 0 ? 'badge-warning' : 'badge-danger'); ?>">
                                            <?php echo $reward['stock_quantity']; ?> available
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $reward['is_active'] ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo $reward['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?edit=<?php echo $reward['id']; ?>" class="admin-btn admin-btn-primary admin-btn-sm">Edit Stock</a>
                                    <a href="?delete=<?php echo $reward['id']; ?>" 
                                       class="admin-btn admin-btn-danger admin-btn-sm"
                                       onclick="return confirm('Are you sure you want to delete this reward?');">Delete</a>
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
