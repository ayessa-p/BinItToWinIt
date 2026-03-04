<?php
require_once '../config/config.php';
require_login();

$page_title = 'Transaction History';
$user_id = get_user_id();

// Get thread ID from URL for detailed view (for future enhancement)
$selected_transaction_id = isset($_GET['transaction_id']) ? (int)$_GET['transaction_id'] : 0;

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get total count
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_transactions = $stmt->fetchColumn();
$total_pages = ceil($total_transactions / $per_page);

// Get transactions
$stmt = $db->prepare("
    SELECT t.*, r.name as reward_name 
    FROM transactions t
    LEFT JOIN rewards r ON t.related_reward_id = r.id
    WHERE t.user_id = ? 
    ORDER BY t.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$user_id, $per_page, $offset]);
$transactions = $stmt->fetchAll();

// Get selected transaction details
$selected_transaction = null;
if ($selected_transaction_id > 0) {
    $stmt = $db->prepare("SELECT t.*, r.name as reward_name FROM transactions t LEFT JOIN rewards r ON t.related_reward_id = r.id WHERE t.id = ? AND t.user_id = ?");
    $stmt->execute([$selected_transaction_id, $user_id]);
    $selected_transaction = $stmt->fetch();
}

include '../includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">Transaction History</h1>
        
        <?php if ($selected_transaction_id > 0 && $selected_transaction): ?>
            <!-- Detailed Transaction View -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Transaction Details</h2>
                    <div style="margin-top: 0.5rem; color: var(--medium-gray);">
                        Transaction ID: #<?php echo $selected_transaction['id']; ?> | 
                        Date: <?php echo date('F j, Y g:i A', strtotime($selected_transaction['created_at'])); ?>
                    </div>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: 200px 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                        <div>
                            <label style="font-weight: 600; color: var(--text-dark);">Type:</label>
                            <div style="margin-top: 0.5rem;">
                                <span style="
                                    padding: 0.5rem 1rem; 
                                    border-radius: var(--radius-sm); 
                                    font-size: 0.875rem;
                                    font-weight: 600;
                                    <?php 
                                    if ($selected_transaction['transaction_type'] === 'earned') {
                                        echo 'background: rgba(0, 255, 0, 0.2); color: #90ee90;';
                                    } elseif ($selected_transaction['transaction_type'] === 'redeemed') {
                                        echo 'background: rgba(255, 215, 0, 0.2); color: var(--bright-gold);';
                                    } elseif ($selected_transaction['transaction_type'] === 'admin_adjustment') {
                                        echo 'background: rgba(61, 127, 199, 0.2); color: var(--light-blue);';
                                    } else {
                                        echo 'background: rgba(108, 117, 125, 0.2); color: var(--text-dark);';
                                    }
                                    ?>
                                ">
                                    <?php 
                                    echo match($selected_transaction['transaction_type']) {
                                        'earned' => 'Earned',
                                        'redeemed' => 'Redeemed', 
                                        'admin_adjustment' => 'Admin Adjustment',
                                        'refund' => 'Refund',
                                        default => ucfirst(str_replace('_', ' ', $selected_transaction['transaction_type']))
                                    }; 
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div>
                            <label style="font-weight: 600; color: var(--text-dark);">Amount:</label>
                            <div style="margin-top: 0.5rem; font-size: 1.2rem; font-weight: bold; color: <?php 
                                echo $selected_transaction['transaction_type'] === 'earned' ? '#28a745' : '#dc3545'; ?>;">
                                <?php echo $selected_transaction['transaction_type'] === 'earned' ? '+' : '-'; ?>
                                <?php echo format_tokens($selected_transaction['amount']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 2rem;">
                        <label style="font-weight: 600; color: var(--text-dark);">Description:</label>
                        <div style="margin-top: 0.5rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; border-left: 4px solid <?php 
                            echo match($selected_transaction['transaction_type']) {
                                'earned' => '#28a745',
                                'redeemed' => '#dc3545', 
                                'admin_adjustment' => '#007bff',
                                'refund' => '#6c757d',
                                default => '#6c757d'
                            }; 
                            ?>;">
                            <?php echo htmlspecialchars($selected_transaction['description']); ?>
                            <?php if ($selected_transaction['reward_name']): ?>
                                <div style="margin-top: 0.5rem; font-size: 0.9rem; color: var(--azure-blue);">
                                    Reward: <?php echo htmlspecialchars($selected_transaction['reward_name']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 2rem;">
                        <a href="transactions.php" class="btn btn-secondary">← Back to All Transactions</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Inbox View -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Transaction Inbox</h2>
                    <div style="margin-bottom: 1rem; display: flex; gap: 1rem; align-items: center;">
                        <div style="color: var(--medium-gray);">
                            <strong>Total:</strong> <?php echo $total_transactions; ?> transactions
                        </div>
                        <div style="color: var(--medium-gray);">
                            <strong>Page:</strong> <?php echo $page; ?> of <?php echo $total_pages; ?>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($transactions)): ?>
                        <div style="text-align: center; padding: 3rem;">
                            <i class="fa-solid fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: var(--medium-gray);"></i>
                            <p style="color: var(--medium-gray); font-size: 1.1rem;">
                                No transactions found.
                            </p>
                            <a href="index.php" class="btn btn-primary" style="margin-top: 1rem;">Back to Dashboard</a>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 2px solid var(--azure-blue);">
                                        <th style="padding: 1rem; text-align: left; color: var(--light-blue);">Date</th>
                                        <th style="padding: 1rem; text-align: left; color: var(--light-blue);">Type</th>
                                        <th style="padding: 1rem; text-align: left; color: var(--light-blue);">Description</th>
                                        <th style="padding: 1rem; text-align: right; color: var(--light-blue);">Amount</th>
                                        <th style="padding: 1rem; text-align: center; color: var(--light-blue);">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr style="border-bottom: 1px solid rgba(61, 127, 199, 0.1); cursor: pointer;" 
                                            onmouseover="this.style.backgroundColor='#f0f8ff'" 
                                            onmouseout="this.style.backgroundColor='transparent'"
                                            onclick="window.location.href='transactions.php?transaction_id=<?php echo $transaction['id']; ?>'">
                                            <td style="padding: 1rem; color: var(--medium-gray); white-space: nowrap;">
                                                <?php 
                                                $time = strtotime($transaction['created_at']);
                                                if (time() - $time < 86400) { // Less than 24 hours
                                                    echo date('g:i A', $time);
                                                } else {
                                                    echo date('M j, Y', $time);
                                                }
                                                ?>
                                            </td>
                                            <td style="padding: 1rem;">
                                                <span style="
                                                    padding: 0.5rem 1rem; 
                                                    border-radius: var(--radius-sm); 
                                                    font-size: 0.875rem;
                                                    font-weight: 600;
                                                    <?php 
                                                    if ($transaction['transaction_type'] === 'earned') {
                                                        echo 'background: rgba(0, 255, 0, 0.2); color: #28a745;';
                                                    } elseif ($transaction['transaction_type'] === 'redeemed') {
                                                        echo 'background: rgba(255, 215, 0, 0.2); color: #ffc107;';
                                                    } elseif ($transaction['transaction_type'] === 'admin_adjustment') {
                                                        echo 'background: rgba(61, 127, 199, 0.2); color: #007bff;';
                                                    } elseif ($transaction['transaction_type'] === 'refund') {
                                                        echo 'background: rgba(108, 117, 125, 0.2); color: #6c757d;';
                                                    } else {
                                                        echo 'background: rgba(108, 117, 125, 0.2); color: #6c757d;';
                                                    }
                                                    ?>
                                                ">
                                                    <?php 
                                                    echo match($transaction['transaction_type']) {
                                                        'earned' => 'Earned',
                                                        'redeemed' => 'Redeemed', 
                                                        'admin_adjustment' => 'Admin Adj.',
                                                        'refund' => 'Refund',
                                                        default => ucfirst(str_replace('_', ' ', $transaction['transaction_type']))
                                                    }; 
                                                    ?>
                                                </span>
                                            </td>
                                            <td style="padding: 1rem; color: var(--medium-gray); max-width: 300px;">
                                                <div><?php echo htmlspecialchars($transaction['description']); ?></div>
                                                <?php if ($transaction['reward_name']): ?>
                                                    <div style="font-size: 0.8rem; color: var(--azure-blue); margin-top: 0.25rem;">
                                                        Reward: <?php echo htmlspecialchars($transaction['reward_name']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 1rem; text-align: right; font-weight: 700; font-size: 1.1rem;
                                                <?php 
                                                $color = match($transaction['transaction_type']) {
                                                    'earned' => '#28a745',
                                                    'redeemed' => '#ffc107',
                                                    'admin_adjustment' => '#007bff',
                                                    'refund' => '#6c757d',
                                                    default => '#6c757d'
                                                };
                                                echo "color: $color;";
                                                ?>
                                                <?php echo $transaction['transaction_type'] === 'earned' ? '+' : '-'; ?>
                                                <?php echo format_tokens($transaction['amount']); ?>
                                            </td>
                                            <td style="padding: 1rem; text-align: center;">
                                                <a href="transactions.php?transaction_id=<?php echo $transaction['id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fa-solid fa-envelope-open"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div style="display: flex; justify-content: center; align-items: center; gap: 1rem; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--azure-blue);">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary">Previous</a>
                                <?php endif; ?>
                                
                                <span style="color: var(--medium-gray);">
                                    Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                                </span>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary">Next</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="index.php" class="btn btn-primary btn-large">← Back to Dashboard</a>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
