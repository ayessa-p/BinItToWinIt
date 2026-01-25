<?php
require_once '../config/config.php';
require_admin();

$page_title = 'Manage Tokens';
$message = '';
$message_type = '';

$db = Database::getInstance()->getConnection();

// Handle token adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_tokens'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
    } else {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $type = $_POST['type'] ?? 'add';
        $description = sanitize_input($_POST['description'] ?? '');
        
        if ($user_id <= 0 || $amount <= 0 || empty($description)) {
            $message = 'Please fill in all fields with valid values.';
            $message_type = 'error';
        } else {
            try {
                $db->beginTransaction();
                
                // Get current balance
                $stmt = $db->prepare("SELECT eco_tokens, full_name FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    throw new Exception('User not found.');
                }
                
                // Calculate new amount
                $adjustment = $type === 'add' ? $amount : -$amount;
                $new_balance = $user['eco_tokens'] + $adjustment;
                
                if ($new_balance < 0) {
                    throw new Exception('Insufficient tokens. Cannot reduce below zero.');
                }
                
                // Update balance
                $stmt = $db->prepare("UPDATE users SET eco_tokens = ? WHERE id = ?");
                $stmt->execute([$new_balance, $user_id]);
                
                // Record transaction
                $stmt = $db->prepare("
                    INSERT INTO transactions (user_id, transaction_type, amount, description) 
                    VALUES (?, 'admin_adjustment', ?, ?)
                ");
                $stmt->execute([$user_id, abs($adjustment), $description]);
                
                $db->commit();
                $message = "Successfully " . ($type === 'add' ? 'added' : 'deducted') . " " . format_tokens($amount) . " tokens. New balance: " . format_tokens($new_balance);
                $message_type = 'success';
            } catch (Exception $e) {
                $db->rollBack();
                $message = $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// Get all users for dropdown
$stmt = $db->query("SELECT id, student_id, full_name, eco_tokens FROM users ORDER BY full_name");
$users = $stmt->fetchAll();

include '../includes/admin_header.php';
?>

<div class="admin-content">
    <div class="admin-container">
        <div class="admin-page-header">
            <h1 class="admin-page-title">Manage Tokens</h1>
            <p class="admin-page-subtitle">Adjust user token balances manually</p>
            <div class="admin-breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/index.php">Home</a> / Tokens
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: var(--spacing-md);">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-section">
            <h2 class="admin-section-title">Adjust User Tokens</h2>
            <form method="POST" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="admin-form-group">
                    <label class="admin-form-label">Select User *</label>
                    <select name="user_id" class="admin-form-select" required>
                        <option value="">Choose a user...</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo htmlspecialchars($user['full_name']); ?> 
                                (<?php echo htmlspecialchars($user['student_id']); ?>) 
                                - Current: <?php echo format_tokens($user['eco_tokens']); ?> tokens
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="admin-form-group">
                    <label class="admin-form-label">Action *</label>
                    <select name="type" class="admin-form-select" required>
                        <option value="add">Add Tokens</option>
                        <option value="subtract">Subtract Tokens</option>
                    </select>
                </div>
                
                <div class="admin-form-group">
                    <label class="admin-form-label">Amount *</label>
                    <input type="number" name="amount" class="admin-form-input" 
                           step="0.01" min="0.01" required placeholder="Enter token amount">
                </div>
                
                <div class="admin-form-group">
                    <label class="admin-form-label">Description *</label>
                    <textarea name="description" class="admin-form-textarea" required 
                              placeholder="Reason for adjustment"></textarea>
                </div>
                
                <button type="submit" name="adjust_tokens" class="admin-btn admin-btn-primary">
                    Adjust Tokens
                </button>
            </form>
        </div>
        
        <div class="admin-section">
            <h2 class="admin-section-title">All Users Token Balances</h2>
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Token Balance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td>
                                <?php 
                                $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
                                $stmt->execute([$user['id']]);
                                echo htmlspecialchars($stmt->fetchColumn());
                                ?>
                            </td>
                            <td><strong><?php echo format_tokens($user['eco_tokens']); ?></strong></td>
                            <td>
                                <a href="user_transactions.php?id=<?php echo $user['id']; ?>" 
                                   class="admin-btn admin-btn-secondary admin-btn-sm">View History</a>
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
