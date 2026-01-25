<?php
require_once '../config/config.php';
require_admin();

$page_title = 'Manage Redemptions';
$message = '';
$message_type = '';

$db = Database::getInstance()->getConnection();

// Handle status update
if (isset($_GET['update_status'])) {
    $id = (int)$_GET['update_status'];
    $status = $_GET['status'] ?? 'pending';
    
    if (in_array($status, ['pending', 'approved', 'fulfilled', 'cancelled'])) {
        try {
            $stmt = $db->prepare("UPDATE redemptions SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            
            if ($status === 'fulfilled') {
                $stmt = $db->prepare("UPDATE redemptions SET fulfilled_at = NOW() WHERE id = ?");
                $stmt->execute([$id]);
            }
            
            $message = 'Redemption status updated successfully!';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = 'Error updating redemption status.';
            $message_type = 'error';
        }
    }
}

// Get all redemptions
$stmt = $db->query("
    SELECT r.*, u.full_name, u.student_id, u.email, rw.name as reward_name
    FROM redemptions r
    JOIN users u ON r.user_id = u.id
    JOIN rewards rw ON r.reward_id = rw.id
    ORDER BY r.created_at DESC
");
$redemptions = $stmt->fetchAll();

include '../includes/admin_header.php';
?>

<div class="admin-content">
    <div class="admin-container">
        <div class="admin-page-header">
            <h1 class="admin-page-title">Manage Redemptions</h1>
            <p class="admin-page-subtitle">Approve and fulfill reward redemptions</p>
            <div class="admin-breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/index.php">Home</a> / Redemptions
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: var(--spacing-md);">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-section">
            <h2 class="admin-section-title">All Redemptions</h2>
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Redemption Code</th>
                            <th>User</th>
                            <th>Reward</th>
                            <th>Tokens Spent</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($redemptions)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: var(--spacing-lg); color: var(--medium-gray);">
                                    No redemptions found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($redemptions as $redemption): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($redemption['redemption_code']); ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($redemption['full_name']); ?><br>
                                    <small style="color: var(--medium-gray);"><?php echo htmlspecialchars($redemption['student_id']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($redemption['reward_name']); ?></td>
                                <td><strong><?php echo format_tokens($redemption['tokens_spent']); ?></strong></td>
                                <td>
                                    <?php
                                    $status_class = [
                                        'pending' => 'badge-warning',
                                        'approved' => 'badge-info',
                                        'fulfilled' => 'badge-success',
                                        'cancelled' => 'badge-danger'
                                    ];
                                    $class = $status_class[$redemption['status']] ?? 'badge';
                                    ?>
                                    <span class="badge <?php echo $class; ?>">
                                        <?php echo ucfirst($redemption['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($redemption['created_at'])); ?></td>
                                <td>
                                    <?php if ($redemption['status'] === 'pending'): ?>
                                        <a href="?update_status=<?php echo $redemption['id']; ?>&status=approved" 
                                           class="admin-btn admin-btn-primary admin-btn-sm">Approve</a>
                                        <a href="?update_status=<?php echo $redemption['id']; ?>&status=cancelled" 
                                           class="admin-btn admin-btn-danger admin-btn-sm">Cancel</a>
                                    <?php elseif ($redemption['status'] === 'approved'): ?>
                                        <a href="?update_status=<?php echo $redemption['id']; ?>&status=fulfilled" 
                                           class="admin-btn admin-btn-primary admin-btn-sm">Mark Fulfilled</a>
                                    <?php endif; ?>
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
