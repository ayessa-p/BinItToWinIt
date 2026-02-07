<?php
require_once '../config/config.php';
require_login();

$page_title = 'Rewards Marketplace';
$user_id = get_user_id();

// Get user balance
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT eco_tokens FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$user_balance = $user['eco_tokens'];

// Handle redemption
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token. Please try again.';
        $message_type = 'error';
    } else {
        $reward_id = (int)($_POST['reward_id'] ?? 0);
        
        if ($reward_id <= 0) {
            $message = 'Invalid reward selected.';
            $message_type = 'error';
        } else {
            try {
                $db->beginTransaction();
                
                // Get reward details
                $stmt = $db->prepare("SELECT * FROM rewards WHERE id = ? AND is_active = 1");
                $stmt->execute([$reward_id]);
                $reward = $stmt->fetch();
                
                if (!$reward) {
                    throw new Exception('Reward not found or unavailable.');
                }
                
                // Check stock
                if ($reward['stock_quantity'] != -1 && $reward['stock_quantity'] <= 0) {
                    throw new Exception('This reward is out of stock.');
                }
                
                // Check user balance
                $stmt = $db->prepare("SELECT eco_tokens FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $current_balance = $stmt->fetchColumn();
                
                if ($current_balance < $reward['token_cost']) {
                    throw new Exception('Insufficient Eco-Tokens. You need ' . format_tokens($reward['token_cost'] - $current_balance) . ' more tokens.');
                }
                
                // Generate redemption code
                $redemption_code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
                
                // Deduct tokens
                $stmt = $db->prepare("UPDATE users SET eco_tokens = eco_tokens - ? WHERE id = ?");
                $stmt->execute([$reward['token_cost'], $user_id]);
                
                // Record transaction
                $stmt = $db->prepare("
                    INSERT INTO transactions (user_id, transaction_type, amount, description, related_reward_id) 
                    VALUES (?, 'redeemed', ?, ?, ?)
                ");
                $description = "Redeemed: {$reward['name']}";
                $stmt->execute([$user_id, $reward['token_cost'], $description, $reward_id]);
                
                // Create redemption record
                $stmt = $db->prepare("
                    INSERT INTO redemptions (user_id, reward_id, tokens_spent, redemption_code, status) 
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([$user_id, $reward_id, $reward['token_cost'], $redemption_code]);
                
                // Update stock if applicable
                if ($reward['stock_quantity'] != -1) {
                    $stmt = $db->prepare("UPDATE rewards SET stock_quantity = stock_quantity - 1 WHERE id = ?");
                    $stmt->execute([$reward_id]);
                }
                
                $db->commit();
                
                $message = "Successfully redeemed! Your redemption code is: <strong>{$redemption_code}</strong>. Please present this code to claim your reward.";
                $message_type = 'success';
                
                // Refresh balance
                $stmt = $db->prepare("SELECT eco_tokens FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user_balance = $stmt->fetchColumn();
                
            } catch (Exception $e) {
                $db->rollBack();
                $message = $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// Get all active rewards
$stmt = $db->prepare("SELECT * FROM rewards WHERE is_active = 1 ORDER BY category, token_cost ASC");
$stmt->execute();
$rewards = $stmt->fetchAll();

// Group by category
$rewards_by_category = [];
foreach ($rewards as $reward) {
    $category = $reward['category'] ?: 'Other';
    if (!isset($rewards_by_category[$category])) {
        $rewards_by_category[$category] = [];
    }
    $rewards_by_category[$category][] = $reward;
}

include '../includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">Rewards Marketplace</h1>
        
        <!-- Balance Display -->
        <div class="card" style="max-width: 500px; margin: 0 auto 3rem; text-align: center; background: linear-gradient(135deg, rgba(61, 127, 199, 0.3) 0%, rgba(22, 36, 71, 0.8) 100%); border: 2px solid var(--gold-yellow);">
            <div class="card-body" style="padding: 2rem;">
                <h2 style="color: var(--medium-gray); font-size: 1.1rem; margin-bottom: 1rem;">Your Available Balance</h2>
                <div class="token-display" style="font-size: 2.5rem; justify-content: center;">
                    <span class="token-icon"></span>
                    <span><?php echo format_tokens($user_balance); ?></span>
                </div>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" style="max-width: 800px; margin: 0 auto 2rem;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($rewards_by_category)): ?>
            <div class="card" style="max-width: 600px; margin: 0 auto; text-align: center;">
                <div class="card-body" style="padding: 3rem;">
                    <p style="color: var(--medium-gray); font-size: 1.1rem;">
                        No rewards available at this time. Check back soon!
                    </p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($rewards_by_category as $category => $category_rewards): ?>
                <div style="background: var(--light-blue-bg); border-radius: var(--radius-lg); padding: 1.5rem; margin: 3rem 0 1.5rem; border-left: 4px solid var(--azure-blue);">
                    <h2 style="color: var(--medium-blue-text); margin: 0; font-size: 1.8rem;">
                        <?php echo htmlspecialchars($category); ?>
                    </h2>
                </div>
                
                <div class="grid grid-3" style="margin-bottom: 3rem;">
                    <?php foreach ($category_rewards as $reward): ?>
                        <div class="card" style="display: flex; flex-direction: column;">
                            <?php if ($reward['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($reward['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($reward['name']); ?>"
                                     style="width: 100%; height: 200px; object-fit: cover; border-radius: var(--radius-md) var(--radius-md) 0 0; margin: -1rem -1rem 1rem -1rem;">
                            <?php endif; ?>
                            
                            <div class="card-header">
                                <h3 class="card-title" style="font-size: 1.3rem;"><?php echo htmlspecialchars($reward['name']); ?></h3>
                            </div>
                            
                            <div class="card-body" style="flex-grow: 1;">
                                <p style="color: var(--dark-gray); margin-bottom: 1rem;">
                                    <?php echo htmlspecialchars($reward['description']); ?>
                                </p>
                                
                                <?php if ($reward['stock_quantity'] != -1): ?>
                                    <p style="color: var(--medium-blue-text); font-size: 0.9rem; margin-bottom: 1rem;">
                                        <strong>Stock:</strong> <?php echo $reward['stock_quantity']; ?> available
                                    </p>
                                <?php endif; ?>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--azure-blue);">
                                    <div class="token-display" style="font-size: 1.3rem;">
                                        <span class="token-icon"></span>
                                        <span><?php echo format_tokens($reward['token_cost']); ?></span>
                                    </div>
                                    
                                    <?php if ($user_balance >= $reward['token_cost'] && ($reward['stock_quantity'] == -1 || $reward['stock_quantity'] > 0)): ?>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="reward_id" value="<?php echo $reward['id']; ?>">
                                            <button type="submit" name="redeem" class="btn btn-primary" 
                                                    onclick="return confirm('Redeem <?php echo htmlspecialchars($reward['name']); ?> for <?php echo format_tokens($reward['token_cost']); ?> tokens?');">
                                                Redeem
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" disabled>
                                            <?php 
                                            if ($user_balance < $reward['token_cost']) {
                                                echo 'Insufficient Tokens';
                                            } else {
                                                echo 'Out of Stock';
                                            }
                                            ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 3rem;">
            <a href="index.php" class="btn btn-secondary btn-large">Back to Dashboard</a>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
