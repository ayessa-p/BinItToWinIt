<?php
require_once '../config/config.php';
require_login();

$page_title = 'Transaction History';
$user_id = get_user_id();

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

include '../includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">Transaction History</h1>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">All Transactions</h2>
                <p style="color: var(--medium-gray);">
                    Showing <?php echo count($transactions); ?> of <?php echo $total_transactions; ?> transactions
                </p>
            </div>
            <div class="card-body">
                <?php if (empty($transactions)): ?>
                    <div style="text-align: center; padding: 3rem;">
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
                                    <th style="padding: 1rem; text-align: left; color: var(--light-blue);">Date & Time</th>
                                    <th style="padding: 1rem; text-align: left; color: var(--light-blue);">Type</th>
                                    <th style="padding: 1rem; text-align: left; color: var(--light-blue);">Description</th>
                                    <th style="padding: 1rem; text-align: right; color: var(--light-blue);">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                <tr style="border-bottom: 1px solid rgba(61, 127, 199, 0.2);">
                                    <td style="padding: 1rem; color: var(--medium-gray);">
                                        <?php echo date('F j, Y g:i A', strtotime($transaction['created_at'])); ?>
                                    </td>
                                    <td style="padding: 1rem;">
                                        <span style="
                                            padding: 0.5rem 1rem; 
                                            border-radius: var(--radius-sm); 
                                            font-size: 0.875rem;
                                            font-weight: 600;
                                            <?php 
                                            if ($transaction['transaction_type'] === 'earned') {
                                                echo 'background: rgba(0, 255, 0, 0.2); color: #90ee90;';
                                            } elseif ($transaction['transaction_type'] === 'redeemed') {
                                                echo 'background: rgba(255, 215, 0, 0.2); color: var(--bright-gold);';
                                            } else {
                                                echo 'background: rgba(61, 127, 199, 0.2); color: var(--light-blue);';
                                            }
                                            ?>
                                        ">
                                            <?php echo ucfirst(str_replace('_', ' ', $transaction['transaction_type'])); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 1rem; color: var(--medium-gray);">
                                        <?php echo htmlspecialchars($transaction['description']); ?>
                                        <?php if ($transaction['reward_name']): ?>
                                            <br><small style="color: var(--azure-blue);">Reward: <?php echo htmlspecialchars($transaction['reward_name']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 1rem; text-align: right; font-weight: 700; font-size: 1.1rem;
                                        <?php echo $transaction['transaction_type'] === 'earned' ? 'color: #90ee90;' : 'color: var(--gold-yellow);'; ?>">
                                        <?php echo $transaction['transaction_type'] === 'earned' ? '+' : '-'; ?>
                                        <?php echo format_tokens($transaction['amount']); ?>
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
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="index.php" class="btn btn-primary btn-large">Back to Dashboard</a>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
