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

// Get recent recycling activities (accepted and rejected)
$stmt = $db->prepare("
    SELECT * FROM recycling_activities 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 20
");
$stmt->execute([$user_id]);
$recent_activities = $stmt->fetchAll();

// Get statistics (accepted only for "bottles recycled", all for token sum)
$stmt = $db->prepare("
    SELECT 
        SUM(CASE WHEN tokens_earned > 0 THEN 1 ELSE 0 END) as total_recycled,
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
        <div class="card" style="max-width: 600px; margin: 0 auto 3rem; text-align: center; background: white; border: 2px solid #007bff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 123, 255, 0.1);">
            <div class="card-body" style="padding: 3rem 2rem;">
                <h2 style="color: var(--medium-gray); font-size: 1.2rem; margin-bottom: 1rem;">Your Eco-Token Balance</h2>
                <div class="token-display" style="font-size: 3rem; justify-content: center;">
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
                <div style="font-size: 3rem; color: #007bff; margin-bottom: 1rem;">
                    <i class="fas fa-recycle"></i>
                </div>
                <h3 style="color: var(--light-blue); margin-bottom: 0.5rem;">
                    <?php echo $stats['total_recycled'] ?? 0; ?>
                </h3>
                <p style="color: var(--medium-gray);">Bottles Recycled</p>
            </div>
            
            <div class="card" style="text-align: center;">
                <div style="font-size: 3rem; color: #007bff; margin-bottom: 1rem;">
                    <i class="fas fa-coins"></i>
                </div>
                <h3 style="color: var(--light-blue); margin-bottom: 0.5rem;">
                    <?php echo format_tokens($stats['total_tokens_earned'] ?? 0); ?>
                </h3>
                <p style="color: var(--medium-gray);">Total Tokens Earned</p>
            </div>
            
            <div class="card" style="text-align: center;">
                <div style="font-size: 3rem; color: #007bff; margin-bottom: 1rem;">
                    <i class="fas fa-gift"></i>
                </div>
                <h3 style="color: var(--light-blue); margin-bottom: 0.5rem;">
                    <a href="rewards.php" style="color: var(--light-blue); text-decoration: none;">
                        Browse Rewards
                    </a>
                </h3>
                <p style="color: var(--medium-gray);">Redeem Your Tokens</p>
            </div>
        </div>

        <!-- Token Earning Legend -->
        <div class="card" style="margin-bottom: 3rem; background: #f8f9fa; border: 1px solid #dee2e6;">
            <div class="card-header" style="background: white; border-bottom: 1px solid #dee2e6; padding: 1.5rem;">
                <h2 class="card-title" style="margin: 0; display: flex; align-items: center; color: #333;">
                    <i class="fas fa-info-circle" style="margin-right: 0.75rem; color: #007bff;"></i>
                    Eco-Token Earning Guide
                </h2>
                <p style="margin: 0.5rem 0 0; color: #666; font-size: 0.95rem;">
                    Tokens are awarded based on the weight of the recycled PET bottle. Heavier bottles mean more plastic recycled!
                </p>
            </div>
            <div class="card-body" style="padding: 2rem;">
                <div class="grid grid-5" style="gap: 1rem;">
                    <div style="text-align: center; padding: 1rem; background: white; border-radius: 8px; border: 1px solid #eee; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                        <div style="font-size: 0.85rem; color: #888; margin-bottom: 0.5rem;">Below 10g</div>
                        <div style="font-weight: 700; color: #dc3545; font-size: 1.25rem;">0 Tokens</div>
                        <div style="font-size: 0.75rem; color: #666; margin-top: 0.25rem;">(Too small)</div>
                    </div>
                    <div style="text-align: center; padding: 1rem; background: white; border-radius: 8px; border: 1px solid #eee; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                        <div style="font-size: 0.85rem; color: #888; margin-bottom: 0.5rem;">10g - 13g</div>
                        <div style="font-weight: 700; color: #007bff; font-size: 1.25rem;">1 Token</div>
                        <div style="font-size: 0.75rem; color: #666; margin-top: 0.25rem;">(Cap / Tiny)</div>
                    </div>
                    <div style="text-align: center; padding: 1rem; background: white; border-radius: 8px; border: 1px solid #eee; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                        <div style="font-size: 0.85rem; color: #888; margin-bottom: 0.5rem;">14g - 17g</div>
                        <div style="font-weight: 700; color: #007bff; font-size: 1.25rem;">2 Tokens</div>
                        <div style="font-size: 0.75rem; color: #666; margin-top: 0.25rem;">(Small Bottle)</div>
                    </div>
                    <div style="text-align: center; padding: 1rem; background: white; border-radius: 8px; border: 1px solid #eee; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                        <div style="font-size: 0.85rem; color: #888; margin-bottom: 0.5rem;">18g - 22g</div>
                        <div style="font-weight: 700; color: #007bff; font-size: 1.25rem;">3 Tokens</div>
                        <div style="font-size: 0.75rem; color: #666; margin-top: 0.25rem;">(Medium Bottle)</div>
                    </div>
                    <div style="text-align: center; padding: 1rem; background: white; border-radius: 8px; border: 1px solid #eee; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                        <div style="font-size: 0.85rem; color: #888; margin-bottom: 0.5rem;">23g & above</div>
                        <div style="font-weight: 700; color: #28a745; font-size: 1.25rem;">4 Tokens</div>
                        <div style="font-size: 0.75rem; color: #666; margin-top: 0.25rem;">(Large Bottle)</div>
                    </div>
                </div>
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
                                <th style="padding: 1rem; text-align: left; color: var(--light-blue);">Status</th>
                                <th style="padding: 1rem; text-align: left; color: var(--light-blue);">Details</th>
                                <th style="padding: 1rem; text-align: left; color: var(--light-blue);">Device</th>
                                <th style="padding: 1rem; text-align: right; color: var(--light-blue);">Tokens Earned</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_activities as $activity):
                                $is_accepted = (float)($activity['tokens_earned'] ?? 0) > 0;
                                $details = $activity['description'] ?? $activity['bottle_type'] ?? '';
                                $device = $activity['device_id'] ?? $activity['sensor_id'] ?? '';
                            ?>
                            <tr style="border-bottom: 1px solid rgba(61, 127, 199, 0.2);">
                                <td style="padding: 1rem; color: var(--medium-gray);">
                                    <?php echo date('M j, Y g:i A', strtotime($activity['created_at'] ?? '')); ?>
                                </td>
                                <td style="padding: 1rem;">
                                    <span style="
                                        padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.875rem;
                                        <?php echo $is_accepted ? 'background: rgba(0, 255, 0, 0.2); color: #90ee90;' : 'background: rgba(255, 100, 100, 0.2); color: #ffb3b3;'; ?>
                                    ">
                                        <?php echo $is_accepted ? 'Accepted' : 'Rejected'; ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem; color: var(--medium-gray);">
                                    <?php echo htmlspecialchars($details ?: '—'); ?>
                                </td>
                                <td style="padding: 1rem; color: var(--medium-gray);">
                                    <?php echo htmlspecialchars($device ?: '—'); ?>
                                </td>
                                <td style="padding: 1rem; text-align: right; font-weight: 600; color: <?php echo $is_accepted ? 'var(--gold-yellow);' : 'var(--medium-gray);'; ?>">
                                    <?php echo $is_accepted ? '+' : ''; ?><?php echo format_tokens($activity['tokens_earned'] ?? 0); ?>
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
                                            echo 'color: #007bff;';
                                        } else {
                                            echo 'color: #007bff;';
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
                                    <?php 
                                    if ($transaction['transaction_type'] === 'earned') {
                                        echo 'color: #90ee90;';
                                    } elseif ($transaction['transaction_type'] === 'redeemed') {
                                        echo 'color: #007bff;';
                                    } elseif ($transaction['transaction_type'] === 'admin_adjustment') {
                                        echo $transaction['amount'] >= 0 ? 'color: #90ee90;' : 'color: #ff6b6b;';
                                    } else {
                                        echo 'color: #007bff;';
                                    }
                                    ?>">
                                    <?php 
                                    if ($transaction['transaction_type'] === 'earned') {
                                        echo '+';
                                    } elseif ($transaction['transaction_type'] === 'redeemed') {
                                        echo '-';
                                    } elseif ($transaction['transaction_type'] === 'admin_adjustment') {
                                        echo $transaction['amount'] >= 0 ? '+' : '-';
                                    } else {
                                        echo '';
                                    }
                                    ?>
                                    <?php echo format_tokens(abs($transaction['amount'])); ?>
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
            <a href="pair_device.php" class="btn btn-secondary btn-large" style="margin-left: 1rem;">
                <i class="fas fa-link"></i> Pair ESP32 Device
            </a>
            <a href="transactions.php" class="btn btn-secondary btn-large" style="margin-left: 1rem;">
                <i class="fas fa-history"></i> View Full History
            </a>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
