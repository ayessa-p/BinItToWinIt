<?php
require_once '../config/config.php';
require_admin();

$page_title = 'User Transaction History';
$message = '';
$message_type = '';

$db = Database::getInstance()->getConnection();

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    header('Location: tokens.php');
    exit;
}

// Get user details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: tokens.php');
    exit;
}

// Get user transactions
$stmt = $db->prepare("
    SELECT * FROM transactions 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 50
");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll();

include '../includes/admin_header.php';
?>

<div class="admin-content">
    <div class="admin-container">
        <div class="admin-page-header">
            <h1 class="admin-page-title">Transaction History</h1>
            <p class="admin-page-subtitle"><?php echo htmlspecialchars($user['full_name']); ?> (<?php echo htmlspecialchars($user['student_id']); ?>)</p>
            <div class="admin-breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/index.php">Home</a> / 
                <a href="tokens.php">Tokens</a> / 
                Transaction History
            </div>
        </div>
        
        <div class="admin-section">
            <div style="background: var(--light-gray); padding: 1rem; border-radius: var(--radius-md); margin-bottom: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong>Current Balance:</strong> 
                        <span style="font-size: 1.5rem; color: var(--primary-blue); font-weight: bold;">
                            <?php echo format_tokens($user['eco_tokens']); ?>
                        </span>
                    </div>
                    <a href="tokens.php" class="admin-btn admin-btn-secondary">← Back to Token Management</a>
                </div>
            </div>
            
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Balance After</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: var(--spacing-lg); color: var(--medium-gray);">
                                    No transactions found for this user.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $running_balance = $user['eco_tokens'];
                            foreach ($transactions as $transaction): 
                                // Determine the sign based on transaction type
                                $is_positive = in_array($transaction['transaction_type'], ['earned', 'admin_adjustment']) && 
                                              !str_contains($transaction['description'] ?? '', 'deducted');
                                $sign = $is_positive ? '+' : '-';
                                $amount_display = $sign . format_tokens($transaction['amount']);
                                
                                // Update running balance (reverse order since we're going from newest to oldest)
                                if (!$is_positive) {
                                    $running_balance += $transaction['amount'];
                                } else {
                                    $running_balance -= $transaction['amount'];
                                }
                            ?>
                            <tr>
                                <td><?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo match($transaction['transaction_type']) {
                                            'earned' => 'success',
                                            'redeemed' => 'danger',
                                            'admin_adjustment' => 'warning',
                                            default => 'secondary'
                                        }; 
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $transaction['transaction_type'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                <td style="font-weight: bold; color: <?php echo $is_positive ? '#28a745' : '#dc3545'; ?>;">
                                    <?php echo $amount_display; ?>
                                </td>
                                <td style="font-weight: bold;">
                                    <?php echo format_tokens(max(0, $running_balance)); ?>
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
