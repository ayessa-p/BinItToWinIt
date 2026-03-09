<?php
require_once '../config/config.php';
require_login();

$page_title = 'Rewards Marketplace';
$user_id = get_user_id();

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT eco_tokens FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$user_balance = $user['eco_tokens'];

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

                $stmt = $db->prepare("SELECT * FROM rewards WHERE id = ? AND is_active = 1");
                $stmt->execute([$reward_id]);
                $reward = $stmt->fetch();

                if (!$reward) throw new Exception('Reward not found or unavailable.');
                if ($reward['stock_quantity'] != -1 && $reward['stock_quantity'] <= 0) throw new Exception('This reward is out of stock.');

                $stmt = $db->prepare("SELECT eco_tokens FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $current_balance = $stmt->fetchColumn();

                if ($current_balance < $reward['token_cost']) {
                    throw new Exception('Insufficient Eco-Tokens. You need ' . format_tokens($reward['token_cost'] - $current_balance) . ' more tokens.');
                }

                $redemption_code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

                $stmt = $db->prepare("INSERT INTO redemptions (user_id, reward_id, tokens_spent, redemption_code, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->execute([$user_id, $reward_id, $reward['token_cost'], $redemption_code]);

                if ($reward['stock_quantity'] != -1) {
                    $stmt = $db->prepare("UPDATE rewards SET stock_quantity = stock_quantity - 1 WHERE id = ?");
                    $stmt->execute([$reward_id]);
                }

                $db->commit();

                $message = "Redemption request submitted! Your code is: <strong>{$redemption_code}</strong>. Tokens will be deducted after admin approval.";
                $message_type = 'success';

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

$stmt = $db->prepare("SELECT * FROM rewards WHERE is_active = 1 ORDER BY category, token_cost ASC");
$stmt->execute();
$rewards = $stmt->fetchAll();

$rewards_by_category = [];
foreach ($rewards as $reward) {
    $category = $reward['category'] ?: 'Other';
    $rewards_by_category[$category][] = $reward;
}

$has_service_rewards = isset($rewards_by_category['Services']) && !empty($rewards_by_category['Services']);
if (isset($rewards_by_category['Services'])) unset($rewards_by_category['Services']);

include '../includes/header.php';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=DM+Mono:wght@400;500&display=swap');

/* ── Design tokens ───────────────────────────────────────────── */
.rewards-page,
.rewards-page * {
    font-family: 'DM Sans', system-ui, sans-serif;
    box-sizing: border-box;
}

.rewards-page {
    --text-xs:    0.70rem;
    --text-sm:    0.8125rem;
    --text-base:  0.9375rem;
    --text-lg:    1.0625rem;
    --text-xl:    1.25rem;
    --text-2xl:   1.5rem;
    --text-3xl:   1.875rem;

    --fw-normal:  400;
    --fw-medium:  500;
    --fw-semibold:600;
    --fw-bold:    700;

    --lh-tight:  1.25;
    --lh-snug:   1.4;
    --lh-normal: 1.6;

    --space-1: 0.25rem;
    --space-2: 0.5rem;
    --space-3: 0.75rem;
    --space-4: 1rem;
    --space-5: 1.25rem;
    --space-6: 1.5rem;
    --space-8: 2rem;
    --space-10: 2.5rem;
    --space-12: 3rem;

    --radius-sm:  4px;
    --radius-md:  8px;
    --radius-lg:  12px;
    --radius-xl:  16px;
    --radius-full:9999px;

    --shadow-sm: 0 1px 2px rgba(0,0,0,.06), 0 1px 3px rgba(0,0,0,.08);
    --shadow-md: 0 4px 6px -1px rgba(0,0,0,.07), 0 2px 4px -1px rgba(0,0,0,.05);
    --shadow-lg: 0 10px 15px -3px rgba(0,0,0,.08), 0 4px 6px -2px rgba(0,0,0,.04);

    --transition: 150ms cubic-bezier(.4,0,.2,1);

    --color-text:        #111827;
    --color-text-muted:  #6b7280;
    --color-text-faint:  #9ca3af;
    --color-border:      #e5e7eb;
    --color-surface:     #fff;
    --color-bg:          #f7f8fa;
    --color-primary:     #4f46e5;
    --color-primary-hover: #4338ca;
    --color-accent:      #0ea5e9;
    --color-accent-bg:   #f0f9ff;
    --color-accent-border: #bae6fd;
}

/* ── Layout ──────────────────────────────────────────────────── */
.rewards-page .section {
    padding: var(--space-10) var(--space-6);
    background: var(--color-bg);
    min-height: 100vh;
}

.rewards-page .container {
    max-width: 1100px;
    margin: 0 auto;
}

/* ── Page title ──────────────────────────────────────────────── */
.rewards-page .section-title {
    font-size: var(--text-3xl);
    font-weight: var(--fw-bold);
    color: var(--color-text);
    letter-spacing: -0.5px;
    line-height: var(--lh-tight);
    text-align: center;
    margin: 0 0 var(--space-8);
}

/* ── Alert ───────────────────────────────────────────────────── */
.rewards-page .alert {
    display: flex;
    align-items: flex-start;
    gap: var(--space-3);
    padding: var(--space-4) var(--space-5);
    border-radius: var(--radius-md);
    font-size: var(--text-sm);
    font-weight: var(--fw-medium);
    line-height: var(--lh-snug);
    max-width: 760px;
    margin: 0 auto var(--space-8);
    border-left: 3px solid transparent;
}
.rewards-page .alert-success { background:#f0fdf4; color:#166534; border-color:#22c55e; }
.rewards-page .alert-error   { background:#fef2f2; color:#991b1b; border-color:#ef4444; }
.rewards-page .alert-warning { background:#fffbeb; color:#92400e; border-color:#f59e0b; }

/* ── Balance card ────────────────────────────────────────────── */
.balance-card {
    max-width: 420px;
    margin: 0 auto var(--space-10);
    background: var(--color-surface);
    border: 1.5px solid var(--color-accent-border);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-md);
    padding: var(--space-8) var(--space-10);
    text-align: center;
}

.balance-label {
    font-size: var(--text-sm);
    font-weight: var(--fw-medium);
    color: var(--color-text-muted);
    letter-spacing: 0.04em;
    text-transform: uppercase;
    margin: 0 0 var(--space-3);
}

.balance-amount {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    font-size: var(--text-3xl);
    font-weight: var(--fw-bold);
    color: var(--color-text);
    line-height: var(--lh-tight);
    letter-spacing: -0.5px;
}

.balance-amount .token-icon {
    font-size: 1.6rem;
    line-height: 1;
}

/* ── Services CTA ────────────────────────────────────────────── */
.services-cta {
    text-align: center;
    margin-bottom: var(--space-10);
}

/* ── Category heading ────────────────────────────────────────── */
.category-heading {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    background: var(--color-accent-bg);
    border-left: 4px solid var(--color-accent);
    border-radius: var(--radius-md);
    padding: var(--space-4) var(--space-6);
    margin: var(--space-10) 0 var(--space-6);
}

.category-heading:first-of-type {
    margin-top: 0;
}

.category-heading h2 {
    font-size: var(--text-xl);
    font-weight: var(--fw-bold);
    color: #0369a1;
    line-height: var(--lh-tight);
    letter-spacing: -0.2px;
    margin: 0;
}

/* ── Reward grid ─────────────────────────────────────────────── */
.rewards-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--space-5);
    margin-bottom: var(--space-10);
    align-items: stretch;
}

@media (max-width: 900px) { .rewards-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 560px) { .rewards-grid { grid-template-columns: 1fr; } }

/* ── Reward card ─────────────────────────────────────────────── */
.reward-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: box-shadow var(--transition), border-color var(--transition), transform var(--transition);
}
.reward-card:hover {
    box-shadow: var(--shadow-lg);
    border-color: #d1d5db;
    transform: translateY(-2px);
}

.reward-card-image {
    width: 100%;
    height: 180px;
    object-fit: cover;
    display: block;
    flex-shrink: 0;
}

.reward-card-inner {
    padding: var(--space-5);
    display: flex;
    flex-direction: column;
    flex: 1;
}

.reward-card-name {
    font-size: var(--text-lg);
    font-weight: var(--fw-semibold);
    color: var(--color-text);
    line-height: var(--lh-snug);
    letter-spacing: -0.1px;
    margin: 0 0 var(--space-3);
}

.reward-card-desc {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    line-height: var(--lh-normal);
    margin: 0 0 var(--space-3);
    flex: 1;
}

.reward-card-stock {
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
    font-size: var(--text-xs);
    font-weight: var(--fw-medium);
    color: #0369a1;
    background: var(--color-accent-bg);
    border: 1px solid var(--color-accent-border);
    border-radius: var(--radius-full);
    padding: var(--space-1) var(--space-3);
    margin-bottom: var(--space-4);
    width: fit-content;
}

.reward-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
    padding-top: var(--space-4);
    border-top: 1px solid var(--color-border);
    margin-top: auto;
}

.reward-token-cost {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    font-size: var(--text-lg);
    font-weight: var(--fw-bold);
    color: var(--color-text);
    line-height: 1;
    white-space: nowrap;
}

.reward-token-cost .token-icon {
    font-size: 1.1rem;
    line-height: 1;
}

/* ── Buttons ─────────────────────────────────────────────────── */
.rewards-page .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-family: 'DM Sans', system-ui, sans-serif;
    font-size: var(--text-sm);
    font-weight: var(--fw-medium);
    line-height: 1;
    height: 36px;
    padding: 0 var(--space-5);
    border: 1px solid transparent;
    border-radius: var(--radius-md);
    cursor: pointer;
    text-decoration: none;
    white-space: nowrap;
    transition: background var(--transition), border-color var(--transition),
                color var(--transition), box-shadow var(--transition);
}
.rewards-page .btn:active { opacity: .85; }

.rewards-page .btn-primary {
    background: var(--color-primary);
    color: #fff;
    border-color: var(--color-primary);
}
.rewards-page .btn-primary:hover {
    background: var(--color-primary-hover);
    border-color: var(--color-primary-hover);
}

.rewards-page .btn-secondary {
    background: var(--color-surface);
    color: #374151;
    border-color: #d1d5db;
    box-shadow: var(--shadow-sm);
}
.rewards-page .btn-secondary:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
}

.rewards-page .btn-secondary[disabled] {
    opacity: .55;
    cursor: not-allowed;
    pointer-events: none;
}

.rewards-page .btn-large {
    height: 44px;
    font-size: var(--text-base);
    padding: 0 var(--space-8);
}

/* ── Empty state ─────────────────────────────────────────────── */
.empty-state {
    max-width: 480px;
    margin: 0 auto;
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-xl);
    padding: var(--space-12) var(--space-8);
    text-align: center;
    box-shadow: var(--shadow-sm);
}

.empty-state p {
    font-size: var(--text-base);
    color: var(--color-text-muted);
    line-height: var(--lh-normal);
    margin: 0;
}

/* ── Footer actions ──────────────────────────────────────────── */
.page-footer-actions {
    text-align: center;
    margin-top: var(--space-10);
    padding-top: var(--space-8);
    border-top: 1px solid var(--color-border);
}
</style>

<section class="section rewards-page">
    <div class="container">
        <h1 class="section-title">Rewards Marketplace</h1>

        <!-- ── Balance card ── -->
        <div class="balance-card">
            <p class="balance-label">Your Available Balance</p>
            <div class="balance-amount">
                <span class="token-icon">🪙</span>
                <span><?php echo format_tokens($user_balance); ?></span>
            </div>
        </div>

        <!-- ── Alert ── -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- ── Empty state ── -->
        <?php if (empty($rewards_by_category) && !$has_service_rewards): ?>
            <div class="empty-state">
                <p>No rewards available at this time. Check back soon!</p>
            </div>

        <?php else: ?>

            <!-- ── Services CTA ── -->
            <?php if ($has_service_rewards): ?>
                <div class="services-cta">
                    <a href="automation.php" class="btn btn-primary btn-large">
                        Convert to Services &rarr;
                    </a>
                </div>
            <?php endif; ?>

            <!-- ── Categories ── -->
            <?php foreach ($rewards_by_category as $category => $category_rewards): ?>

                <div class="category-heading">
                    <h2><?php echo htmlspecialchars($category); ?></h2>
                </div>

                <div class="rewards-grid">
                    <?php foreach ($category_rewards as $reward): ?>
                        <div class="reward-card">

                            <?php if ($reward['image_url']): ?>
                                <img class="reward-card-image"
                                     src="<?php echo htmlspecialchars($reward['image_url']); ?>"
                                     alt="<?php echo htmlspecialchars($reward['name']); ?>">
                            <?php endif; ?>

                            <div class="reward-card-inner">
                                <h3 class="reward-card-name"><?php echo htmlspecialchars($reward['name']); ?></h3>

                                <p class="reward-card-desc"><?php echo htmlspecialchars($reward['description']); ?></p>

                                <?php if ($reward['stock_quantity'] != -1): ?>
                                    <span class="reward-card-stock">
                                        <?php echo (int)$reward['stock_quantity']; ?> in stock
                                    </span>
                                <?php endif; ?>

                                <div class="reward-card-footer">
                                    <div class="reward-token-cost">
                                        <span class="token-icon">🪙</span>
                                        <span><?php echo format_tokens($reward['token_cost']); ?></span>
                                    </div>

                                    <?php
                                        $can_afford = $user_balance >= $reward['token_cost'];
                                        $in_stock   = $reward['stock_quantity'] == -1 || $reward['stock_quantity'] > 0;
                                    ?>

                                    <?php if ($can_afford && $in_stock): ?>
                                        <form method="POST" action="" style="flex:1; display:flex; justify-content:flex-end;">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="reward_id" value="<?php echo $reward['id']; ?>">
                                            <button type="submit" name="redeem" class="btn btn-primary"
                                                    onclick="return confirm('Redeem <?php echo htmlspecialchars($reward['name']); ?> for <?php echo format_tokens($reward['token_cost']); ?> tokens?')">
                                                Redeem
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" disabled>
                                            <?php echo $can_afford ? 'Out of Stock' : 'Need More Tokens'; ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endforeach; ?>
        <?php endif; ?>

        <!-- ── Back link ── -->
        <div class="page-footer-actions">
            <a href="index.php" class="btn btn-secondary btn-large">← Back to Dashboard</a>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>