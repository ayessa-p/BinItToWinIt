<?php
require_once '../config/config.php';
require_admin();

$page_title = 'Manage Users';
$message = '';
$message_type = '';

$db = Database::getInstance()->getConnection();

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
                                <a href="tokens.php?user_id=<?php echo $user['id']; ?>" 
                                   class="admin-btn admin-btn-secondary admin-btn-sm">Manage Tokens</a>
                                <?php if (!$user['is_admin']): ?>
                                    <a href="?make_admin=<?php echo $user['id']; ?>" 
                                       class="admin-btn admin-btn-primary admin-btn-sm"
                                       onclick="return confirm('Promote this user to admin?');">Make Admin</a>
                                <?php endif; ?>
                                <a href="?toggle_status=<?php echo $user['id']; ?>" 
                                   class="admin-btn admin-btn-<?php echo $user['is_active'] ? 'danger' : 'primary'; ?> admin-btn-sm">
                                    <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </a>
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
