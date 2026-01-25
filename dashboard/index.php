<?php
require_once '../config/config.php';
require_login();

$page_title = 'Dashboard';
$user_id = get_user_id();

// Get user data
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get recent transactions
$stmt = $db->prepare("
    SELECT * FROM transactions 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$user_id]);
$recent_transactions = $stmt->fetchAll();

// Get recent recycling activities
$stmt = $db->prepare("
    SELECT * FROM recycling_activities 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_activities = $stmt->fetchAll();

// Get statistics
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_recycled,
        SUM(tokens_earned) as total_tokens_earned
    FROM recycling_activities 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

include '../includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
        
        <!-- Token Balance Card -->
        <div class="card" style="max-width: 600px; margin: 0 auto 3rem; text-align: center; background: linear-gradient(135deg, rgba(61, 127, 199, 0.3) 0%, rgba(22, 36, 71, 0.8) 100%); border: 2px solid var(--gold-yellow);">
            <div class="card-body" style="padding: 3rem 2rem;">
                <h2 style="color: var(--medium-gray); font-size: 1.2rem; margin-bottom: 1rem;">Your Eco-Token Balance</h2>
                <div class="token-display" style="font-size: 3rem; justify-content: center;">
                    <span class="token-icon"></span>
                    <span><?php echo format_tokens($user['eco_tokens']); ?></span>
                </div>
                <p style="color: var(--medium-gray); margin-top: 1rem;">
                    Keep recycling to earn more tokens!
                </p>
            </div>
        </div>
        
        <!-- Statistics Grid -->
        <div class="grid grid-3" style="margin-bottom: 3rem;">
            <div class="card" style="text-align: center;">
                <div style="font-size: 3rem; color: var(--gold-yellow); margin-bottom: 1rem;">♻️</div>
                <h3 style="color: var(--light-blue); margin-bottom: 0.5rem;">
                    <?php echo $stats['total_recycled'] ?? 0; ?>
                </h3>
                <p style="color: var(--medium-gray);">Bottles Recycled</p>
            </div>
            
            <div class="card" style="text-align: center;">
                <div style="font-size: 3rem; color: var(--gold-yellow); margin-bottom: 1rem;">💰</div>
                <h3 style="color: var(--light-blue); margin-bottom: 0.5rem;">
                    <?php echo format_tokens($stats['total_tokens_earned'] ?? 0); ?>
                </h3>
                <p style="color: var(--medium-gray);">Total Tokens Earned</p>
            </div>
            
            <div class="card" style="text-align: center;">
                <div style="font-size: 3rem; color: var(--gold-yellow); margin-bottom: 1rem;">🎁</div>
                <h3 style="color: var(--light-blue); margin-bottom: 0.5rem;">
                    <a href="rewards.php" style="color: var(--light-blue); text-decoration: none;">
                        Browse Rewards
                    </a>
                </h3>
                <p style="color: var(--medium-gray);">Redeem Your Tokens</p>
            </div>
        </div>
        
        <!-- Recent Activities -->
        <?php if (!empty($recent_activities)): ?>
        <div class="card" style="margin-bottom: 3rem;">
            <div class="card-header">
                <h2 class="card-title">Recent Recycling Activities</h2>
            </div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--azure-blue);">
                                <th style="padding: 1rem; text-align: left; color: var(--light-blue);">Date</th>
                                <th style="padding: 1rem; text-align: left; color: var(--light-blue);">Bottle Type</th>
                                <th style="padding: 1rem; text-align: left; color: var(--light-blue);">Sensor ID</th>
                                <th style="padding: 1rem; text-align: right; color: var(--light-blue);">Tokens Earned</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_activities as $activity): ?>
                            <tr style="border-bottom: 1px solid rgba(61, 127, 199, 0.2);">
                                <td style="padding: 1rem; color: var(--medium-gray);">
                                    <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                </td>
                                <td style="padding: 1rem; color: var(--medium-gray);">
                                    <?php echo ucfirst(htmlspecialchars($activity['bottle_type'])); ?>
                                </td>
                                <td style="padding: 1rem; color: var(--medium-gray);">
                                    <?php echo htmlspecialchars($activity['sensor_id']); ?>
                                </td>
                                <td style="padding: 1rem; text-align: right; color: var(--gold-yellow); font-weight: 600;">
                                    +<?php echo format_tokens($activity['tokens_earned']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Recent Transactions -->
        <?php if (!empty($recent_transactions)): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Recent Transactions</h2>
            </div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--azure-blue);">
                                <th style="padding: 1rem; text-align: left; color: var(--light-blue);">Date</th>
                                <th style="padding: 1rem; text-align: left; color: var(--light-blue);">Type</th>
                                <th style="padding: 1rem; text-align: left; color: var(--light-blue);">Description</th>
                                <th style="padding: 1rem; text-align: right; color: var(--light-blue);">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_transactions as $transaction): ?>
                            <tr style="border-bottom: 1px solid rgba(61, 127, 199, 0.2);">
                                <td style="padding: 1rem; color: var(--medium-gray);">
                                    <?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?>
                                </td>
                                <td style="padding: 1rem;">
                                    <span style="
                                        padding: 0.25rem 0.75rem; 
                                        border-radius: var(--radius-sm); 
                                        font-size: 0.875rem;
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
                                        <?php echo ucfirst($transaction['transaction_type']); ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem; color: var(--medium-gray);">
                                    <?php echo htmlspecialchars($transaction['description']); ?>
                                </td>
                                <td style="padding: 1rem; text-align: right; font-weight: 600; 
                                    <?php echo $transaction['transaction_type'] === 'earned' ? 'color: #90ee90;' : 'color: var(--gold-yellow);'; ?>">
                                    <?php echo $transaction['transaction_type'] === 'earned' ? '+' : '-'; ?>
                                    <?php echo format_tokens($transaction['amount']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="text-align: center; margin-top: 1.5rem;">
                    <a href="transactions.php" class="btn btn-secondary">View All Transactions</a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 3rem;">
                <p style="color: var(--medium-gray); font-size: 1.1rem; margin-bottom: 1.5rem;">
                    No transactions yet. Start recycling to earn your first Eco-Tokens!
                </p>
                <a href="<?php echo SITE_URL; ?>/projects.php#bin-it-to-win-it" class="btn btn-primary">
                    Learn How to Get Started
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <div style="text-align: center; margin-top: 3rem;">
            <a href="rewards.php" class="btn btn-primary btn-large">Browse Rewards Marketplace</a>
            <a href="transactions.php" class="btn btn-secondary btn-large" style="margin-left: 1rem;">View Full History</a>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
