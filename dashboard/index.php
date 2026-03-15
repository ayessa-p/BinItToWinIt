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

// Pass current server Unix timestamp to JS for polling baseline
$server_now = time();

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
        <div style="margin-bottom: 3rem; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 20px rgba(37,99,235,0.12);">

            <!-- Header -->
            <div style="background: linear-gradient(135deg, #1e40af 0%, #2563eb 55%, #3b82f6 100%); padding: 1.75rem 2rem; display: flex; align-items: center; gap: 1rem;">
                <div style="width: 52px; height: 52px; background: rgba(255,255,255,0.18); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 2px solid rgba(255,255,255,0.3);">
                    <i class="fas fa-leaf" style="color: #fff; font-size: 1.4rem;"></i>
                </div>
                <div>
                    <h2 style="margin: 0; color: #fff; font-size: 1.25rem; font-weight: 700; letter-spacing: 0.01em;">Eco-Token Earning Guide</h2>
                    <p style="margin: 0.35rem 0 0; color: rgba(255,255,255,0.78); font-size: 0.88rem; line-height: 1.4;">
                        Tokens are awarded based on bottle weight — heavier means more plastic recycled!
                    </p>
                </div>
            </div>

            <!-- Tier Cards Body -->
            <div style="background: #eef2ff; border: 1px solid #c7d2fe; border-top: none; border-radius: 0 0 14px 14px; padding: 1.75rem 1.75rem 1.5rem;">
                <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 0.9rem;">

                    <!-- Tier 0 — Too Small -->
                    <div style="background: #fff; border-radius: 12px; border-top: 4px solid #ef4444; box-shadow: 0 2px 10px rgba(0,0,0,0.06); padding: 1.25rem 0.9rem; text-align: center; position: relative; overflow: hidden; transition: transform 0.18s, box-shadow 0.18s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 6px 18px rgba(239,68,68,0.18)';" onmouseout="this.style.transform='';this.style.boxShadow='0 2px 10px rgba(0,0,0,0.06)';">
                        <div style="position: absolute; top: 0; left: 0; right: 0; height: 56px; background: linear-gradient(180deg, rgba(254,226,226,0.55) 0%, transparent 100%); pointer-events: none;"></div>
                        <div style="width: 46px; height: 46px; background: #fee2e2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.8rem;">
                            <i class="fas fa-ban" style="color: #ef4444; font-size: 1.15rem;"></i>
                        </div>
                        <span style="display: inline-block; background: #fee2e2; color: #b91c1c; font-size: 0.65rem; font-weight: 700; padding: 0.18rem 0.55rem; border-radius: 20px; margin-bottom: 0.8rem; letter-spacing: 0.05em; text-transform: uppercase;">Below 10g</span>
                        <div style="font-size: 2.2rem; font-weight: 800; color: #ef4444; line-height: 1;">0</div>
                        <div style="font-size: 0.72rem; font-weight: 700; color: #ef4444; margin-bottom: 0.8rem; text-transform: uppercase; letter-spacing: 0.06em;">Tokens</div>
                        <div style="font-size: 0.7rem; color: #b91c1c; background: #fff5f5; border: 1px solid #fecaca; border-radius: 6px; padding: 0.3rem 0.4rem; display: flex; align-items: center; justify-content: center; gap: 0.25rem; font-weight: 600;">
                            <i class="fas fa-times-circle"></i> Too small
                        </div>
                    </div>

                    <!-- Tier 1 — Cap / Tiny -->
                    <div style="background: #fff; border-radius: 12px; border-top: 4px solid #60a5fa; box-shadow: 0 2px 10px rgba(0,0,0,0.06); padding: 1.25rem 0.9rem; text-align: center; position: relative; overflow: hidden; transition: transform 0.18s, box-shadow 0.18s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 6px 18px rgba(96,165,250,0.22)';" onmouseout="this.style.transform='';this.style.boxShadow='0 2px 10px rgba(0,0,0,0.06)';">
                        <div style="position: absolute; top: 0; left: 0; right: 0; height: 56px; background: linear-gradient(180deg, rgba(219,234,254,0.55) 0%, transparent 100%); pointer-events: none;"></div>
                        <div style="width: 46px; height: 46px; background: #dbeafe; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.8rem;">
                            <i class="fas fa-bottle-water" style="color: #3b82f6; font-size: 1.15rem;"></i>
                        </div>
                        <span style="display: inline-block; background: #dbeafe; color: #1d4ed8; font-size: 0.65rem; font-weight: 700; padding: 0.18rem 0.55rem; border-radius: 20px; margin-bottom: 0.8rem; letter-spacing: 0.05em; text-transform: uppercase;">10g – 13g</span>
                        <div style="font-size: 2.2rem; font-weight: 800; color: #3b82f6; line-height: 1;">1</div>
                        <div style="font-size: 0.72rem; font-weight: 700; color: #3b82f6; margin-bottom: 0.8rem; text-transform: uppercase; letter-spacing: 0.06em;">Token</div>
                        <div style="font-size: 0.7rem; color: #1d4ed8; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 0.3rem 0.4rem; display: flex; align-items: center; justify-content: center; gap: 0.25rem; font-weight: 600;">
                            <i class="fas fa-recycle"></i> Cap / Tiny
                        </div>
                    </div>

                    <!-- Tier 2 — Small Bottle -->
                    <div style="background: #fff; border-radius: 12px; border-top: 4px solid #2563eb; box-shadow: 0 2px 10px rgba(0,0,0,0.06); padding: 1.25rem 0.9rem; text-align: center; position: relative; overflow: hidden; transition: transform 0.18s, box-shadow 0.18s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 6px 18px rgba(37,99,235,0.22)';" onmouseout="this.style.transform='';this.style.boxShadow='0 2px 10px rgba(0,0,0,0.06)';">
                        <div style="position: absolute; top: 0; left: 0; right: 0; height: 56px; background: linear-gradient(180deg, rgba(199,210,254,0.55) 0%, transparent 100%); pointer-events: none;"></div>
                        <div style="width: 46px; height: 46px; background: #e0e7ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.8rem;">
                            <i class="fas fa-bottle-water" style="color: #2563eb; font-size: 1.3rem;"></i>
                        </div>
                        <span style="display: inline-block; background: #e0e7ff; color: #3730a3; font-size: 0.65rem; font-weight: 700; padding: 0.18rem 0.55rem; border-radius: 20px; margin-bottom: 0.8rem; letter-spacing: 0.05em; text-transform: uppercase;">14g – 17g</span>
                        <div style="font-size: 2.2rem; font-weight: 800; color: #2563eb; line-height: 1;">2</div>
                        <div style="font-size: 0.72rem; font-weight: 700; color: #2563eb; margin-bottom: 0.8rem; text-transform: uppercase; letter-spacing: 0.06em;">Tokens</div>
                        <div style="font-size: 0.7rem; color: #3730a3; background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 6px; padding: 0.3rem 0.4rem; display: flex; align-items: center; justify-content: center; gap: 0.25rem; font-weight: 600;">
                            <i class="fas fa-recycle"></i> Small Bottle
                        </div>
                    </div>

                    <!-- Tier 3 — Medium Bottle -->
                    <div style="background: #fff; border-radius: 12px; border-top: 4px solid #0ea5e9; box-shadow: 0 2px 10px rgba(0,0,0,0.06); padding: 1.25rem 0.9rem; text-align: center; position: relative; overflow: hidden; transition: transform 0.18s, box-shadow 0.18s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 6px 18px rgba(14,165,233,0.22)';" onmouseout="this.style.transform='';this.style.boxShadow='0 2px 10px rgba(0,0,0,0.06)';">
                        <div style="position: absolute; top: 0; left: 0; right: 0; height: 56px; background: linear-gradient(180deg, rgba(186,230,253,0.55) 0%, transparent 100%); pointer-events: none;"></div>
                        <div style="width: 46px; height: 46px; background: #e0f2fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.8rem;">
                            <i class="fas fa-bottle-water" style="color: #0ea5e9; font-size: 1.45rem;"></i>
                        </div>
                        <span style="display: inline-block; background: #e0f2fe; color: #075985; font-size: 0.65rem; font-weight: 700; padding: 0.18rem 0.55rem; border-radius: 20px; margin-bottom: 0.8rem; letter-spacing: 0.05em; text-transform: uppercase;">18g – 22g</span>
                        <div style="font-size: 2.2rem; font-weight: 800; color: #0ea5e9; line-height: 1;">3</div>
                        <div style="font-size: 0.72rem; font-weight: 700; color: #0ea5e9; margin-bottom: 0.8rem; text-transform: uppercase; letter-spacing: 0.06em;">Tokens</div>
                        <div style="font-size: 0.7rem; color: #075985; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 6px; padding: 0.3rem 0.4rem; display: flex; align-items: center; justify-content: center; gap: 0.25rem; font-weight: 600;">
                            <i class="fas fa-recycle"></i> Medium Bottle
                        </div>
                    </div>

                    <!-- Tier 4 — Large Bottle (Best) -->
                    <div style="background: #fff; border-radius: 12px; border-top: 4px solid #16a34a; box-shadow: 0 2px 10px rgba(0,0,0,0.06); padding: 1.25rem 0.9rem; text-align: center; position: relative; overflow: hidden; transition: transform 0.18s, box-shadow 0.18s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 6px 18px rgba(22,163,74,0.22)';" onmouseout="this.style.transform='';this.style.boxShadow='0 2px 10px rgba(0,0,0,0.06)';">
                        <div style="position: absolute; top: 0; left: 0; right: 0; height: 56px; background: linear-gradient(180deg, rgba(187,247,208,0.55) 0%, transparent 100%); pointer-events: none;"></div>
                        <!-- Best Value badge -->
                        <div style="position: absolute; top: 10px; right: 10px; background: #16a34a; color: #fff; font-size: 0.55rem; font-weight: 800; padding: 0.15rem 0.45rem; border-radius: 20px; letter-spacing: 0.07em; text-transform: uppercase;">Best</div>
                        <div style="width: 46px; height: 46px; background: #dcfce7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.8rem;">
                            <i class="fas fa-bottle-water" style="color: #16a34a; font-size: 1.6rem;"></i>
                        </div>
                        <span style="display: inline-block; background: #dcfce7; color: #14532d; font-size: 0.65rem; font-weight: 700; padding: 0.18rem 0.55rem; border-radius: 20px; margin-bottom: 0.8rem; letter-spacing: 0.05em; text-transform: uppercase;">23g &amp; above</span>
                        <div style="font-size: 2.2rem; font-weight: 800; color: #16a34a; line-height: 1;">4</div>
                        <div style="font-size: 0.72rem; font-weight: 700; color: #16a34a; margin-bottom: 0.8rem; text-transform: uppercase; letter-spacing: 0.06em;">Tokens</div>
                        <div style="font-size: 0.7rem; color: #14532d; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 0.3rem 0.4rem; display: flex; align-items: center; justify-content: center; gap: 0.25rem; font-weight: 600;">
                            <i class="fas fa-star"></i> Large Bottle
                        </div>
                    </div>

                    <!-- Metal — Always Rejected -->
                    <div style="background: #fff; border-radius: 12px; border-top: 4px solid #78716c; box-shadow: 0 2px 10px rgba(0,0,0,0.06); padding: 1.25rem 0.9rem; text-align: center; position: relative; overflow: hidden; transition: transform 0.18s, box-shadow 0.18s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 6px 18px rgba(120,113,108,0.22)';" onmouseout="this.style.transform='';this.style.boxShadow='0 2px 10px rgba(0,0,0,0.06)';">
                        <div style="position: absolute; top: 0; left: 0; right: 0; height: 56px; background: linear-gradient(180deg, rgba(231,229,228,0.6) 0%, transparent 100%); pointer-events: none;"></div>
                        <!-- Always Rejected badge -->
                        <div style="position: absolute; top: 10px; right: 8px; background: #78716c; color: #fff; font-size: 0.52rem; font-weight: 800; padding: 0.15rem 0.4rem; border-radius: 20px; letter-spacing: 0.06em; text-transform: uppercase; white-space: nowrap;">No Accept</div>
                        <div style="width: 46px; height: 46px; background: #f5f5f4; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.8rem;">
                            <i class="fas fa-magnet" style="color: #78716c; font-size: 1.2rem;"></i>
                        </div>
                        <span style="display: inline-block; background: #f5f5f4; color: #44403c; font-size: 0.65rem; font-weight: 700; padding: 0.18rem 0.55rem; border-radius: 20px; margin-bottom: 0.8rem; letter-spacing: 0.05em; text-transform: uppercase;">Any Weight</span>
                        <div style="font-size: 2.2rem; font-weight: 800; color: #78716c; line-height: 1;">0</div>
                        <div style="font-size: 0.72rem; font-weight: 700; color: #78716c; margin-bottom: 0.8rem; text-transform: uppercase; letter-spacing: 0.06em;">Tokens</div>
                        <div style="font-size: 0.7rem; color: #44403c; background: #fafaf9; border: 1px solid #d6d3d1; border-radius: 6px; padding: 0.3rem 0.4rem; display: flex; align-items: center; justify-content: center; gap: 0.25rem; font-weight: 600;">
                            <i class="fas fa-magnet"></i> Metal
                        </div>
                    </div>

                </div>

                <!-- Footer note -->
                <div style="margin-top: 1.1rem; display: flex; align-items: center; gap: 0.5rem; color: #4338ca; font-size: 0.78rem; font-weight: 500;">
                    <i class="fas fa-circle-info"></i>
                    Only PET plastic bottles are accepted. Metal objects and bottles below 10g are always rejected regardless of weight.
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

    <!-- =========================================================
         RECYCLING RESULT POPUP
         ========================================================= -->
    <div id="recycle-popup-overlay" style="
        display: none; position: fixed; inset: 0; z-index: 99999;
        background: rgba(0,0,0,0.45); backdrop-filter: blur(3px);
        align-items: center; justify-content: center;
    ">
        <div id="recycle-popup-card" style="
            background: #fff; border-radius: 20px; width: 420px; max-width: 92vw;
            box-shadow: 0 24px 60px rgba(0,0,0,0.22); overflow: hidden;
            transform: scale(0.88) translateY(24px); opacity: 0;
            transition: transform 0.32s cubic-bezier(.34,1.56,.64,1), opacity 0.28s ease;
            position: relative;
        ">
            <!-- Coloured header band -->
            <div id="recycle-popup-header" style="padding: 1.6rem 1.5rem 1.25rem; text-align: center; position: relative;">
                <!-- Close button -->
                <button onclick="closeRecyclePopup()" style="
                    position: absolute; top: 0.75rem; right: 0.9rem;
                    background: rgba(255,255,255,0.25); border: none; border-radius: 50%;
                    width: 28px; height: 28px; cursor: pointer; display: flex;
                    align-items: center; justify-content: center; color: #fff; font-size: 0.85rem;
                    transition: background 0.15s;
                " onmouseover="this.style.background='rgba(255,255,255,0.4)'" onmouseout="this.style.background='rgba(255,255,255,0.25)'">
                    <i class="fas fa-times"></i>
                </button>

                <!-- Animated icon ring -->
                <div id="recycle-popup-icon-ring" style="
                    width: 72px; height: 72px; border-radius: 50%;
                    background: rgba(255,255,255,0.22); border: 3px solid rgba(255,255,255,0.45);
                    display: flex; align-items: center; justify-content: center;
                    margin: 0 auto 1rem; font-size: 1.9rem; color: #fff;
                ">
                    <i id="recycle-popup-icon" class="fas fa-check"></i>
                </div>

                <h3 id="recycle-popup-title" style="margin: 0; color: #fff; font-size: 1.3rem; font-weight: 800; letter-spacing: 0.01em;"></h3>
                <p id="recycle-popup-subtitle" style="margin: 0.4rem 0 0; color: rgba(255,255,255,0.82); font-size: 0.88rem;"></p>
            </div>

            <!-- Body -->
            <div style="padding: 1.25rem 1.5rem 1rem;">
                <!-- Main info rows -->
                <div id="recycle-popup-rows" style="display: flex; flex-direction: column; gap: 0.55rem; margin-bottom: 1rem;"></div>

                <!-- Token badge (shown only on accept) -->
                <div id="recycle-popup-token-badge" style="
                    display: none; align-items: center; justify-content: center; gap: 0.6rem;
                    background: linear-gradient(135deg, #fef9c3, #fef08a);
                    border: 1.5px solid #fde047; border-radius: 12px;
                    padding: 0.75rem 1rem; margin-bottom: 1rem;
                ">
                    <i class="fas fa-coins" style="color: #b45309; font-size: 1.3rem;"></i>
                    <span id="recycle-popup-token-text" style="font-size: 1.1rem; font-weight: 800; color: #78350f;"></span>
                </div>

                <!-- Countdown bar -->
                <div style="background: #f1f5f9; border-radius: 8px; overflow: hidden; height: 5px;">
                    <div id="recycle-popup-bar" style="height: 100%; width: 100%; border-radius: 8px; transition: width 0.25s linear;"></div>
                </div>
                <p style="text-align: center; font-size: 0.7rem; color: #94a3b8; margin: 0.35rem 0 0;">
                    Auto-closing in <span id="recycle-popup-countdown">8</span>s &nbsp;·&nbsp;
                    <a href="javascript:void(0)" onclick="closeRecyclePopup()" style="color: #6366f1; text-decoration: none; font-weight: 600;">Dismiss</a>
                </p>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    // ── CONFIG ────────────────────────────────────────────────
    const POLL_INTERVAL_MS = 5000;
    const POPUP_DURATION_S = 8;
    const POLL_URL = '<?php echo SITE_URL; ?>/dashboard/activity_poll.php';

    // Baseline: last activity time already in DB when page loaded.
    // The PHP variable gives us the current server Unix timestamp.
    let lastChecked = <?php echo (int)$server_now; ?>;

    // Queue of activities waiting to be shown
    const queue = [];
    let popupTimer = null;
    let barTimer   = null;
    let cdTimer    = null;
    let isShowing  = false;

    // ── HELPERS ───────────────────────────────────────────────
    function makeRow(icon, label, value, valueColor) {
        return `<div style="display:flex;align-items:center;gap:0.6rem;
                    background:#f8fafc;border-radius:8px;padding:0.55rem 0.8rem;">
                    <i class="fas fa-${icon}" style="color:${valueColor};width:16px;text-align:center;"></i>
                    <span style="color:#64748b;font-size:0.82rem;flex:1;">${label}</span>
                    <span style="font-weight:700;color:${valueColor};font-size:0.88rem;">${value}</span>
                </div>`;
    }

    // ── SHOW POPUP ────────────────────────────────────────────
    function showNext() {
        if (isShowing || queue.length === 0) return;
        isShowing = true;

        const activity = queue.shift();
        const accepted  = parseFloat(activity.tokens_earned) > 0;
        const isMetal   = (activity.bottle_type || '').toLowerCase().includes('metal');
        const isTooSmall = !accepted && !isMetal;

        // Extract weight from bottle_type string e.g. "Accepted: 15.2g PET"
        const weightMatch = (activity.bottle_type || '').match(/([\d.]+)\s*g/i);
        const weightStr   = weightMatch ? weightMatch[1] + 'g' : '—';

        const overlay = document.getElementById('recycle-popup-overlay');
        const card    = document.getElementById('recycle-popup-card');
        const header  = document.getElementById('recycle-popup-header');
        const iconEl  = document.getElementById('recycle-popup-icon');
        const title   = document.getElementById('recycle-popup-title');
        const sub     = document.getElementById('recycle-popup-subtitle');
        const rows    = document.getElementById('recycle-popup-rows');
        const badge   = document.getElementById('recycle-popup-token-badge');
        const tokTxt  = document.getElementById('recycle-popup-token-text');
        const bar     = document.getElementById('recycle-popup-bar');
        const cd      = document.getElementById('recycle-popup-countdown');

        if (accepted) {
            header.style.background  = 'linear-gradient(135deg, #15803d 0%, #16a34a 60%, #22c55e 100%)';
            iconEl.className         = 'fas fa-check-circle';
            title.textContent        = 'Bottle Accepted!';
            sub.textContent          = 'Your recycling was recorded successfully.';
            bar.style.background     = '#22c55e';

            badge.style.display      = 'flex';
            const t = parseFloat(activity.tokens_earned);
            tokTxt.textContent       = `+${t % 1 === 0 ? t.toFixed(0) : t.toFixed(2)} Eco-Token${t !== 1 ? 's' : ''} earned!`;

            rows.innerHTML =
                makeRow('weight-hanging', 'Bottle Weight', weightStr, '#15803d') +
                makeRow('recycle', 'Material', 'PET Plastic', '#0ea5e9') +
                makeRow('circle-check', 'Status', 'Accepted', '#15803d');

        } else if (isMetal) {
            header.style.background  = 'linear-gradient(135deg, #78350f 0%, #b45309 60%, #d97706 100%)';
            iconEl.className         = 'fas fa-magnet';
            title.textContent        = 'Metal Detected — Rejected';
            sub.textContent          = 'Only PET plastic bottles are accepted.';
            bar.style.background     = '#f59e0b';

            badge.style.display      = 'none';

            rows.innerHTML =
                makeRow('magnet', 'Material', 'Metal Object', '#b45309') +
                makeRow('weight-hanging', 'Detected Weight', weightStr, '#78350f') +
                makeRow('times-circle', 'Status', 'Rejected', '#b45309');

        } else {
            // Too small or out of range
            header.style.background  = 'linear-gradient(135deg, #991b1b 0%, #dc2626 60%, #ef4444 100%)';
            iconEl.className         = 'fas fa-circle-xmark';
            title.textContent        = 'Bottle Rejected';
            sub.textContent          = 'The bottle does not meet the minimum weight requirement.';
            bar.style.background     = '#ef4444';

            badge.style.display      = 'none';

            const reason = (activity.bottle_type || '').includes('not in range') ? 'Below 10g (Too Small)' : 'Not Accepted';
            rows.innerHTML =
                makeRow('weight-hanging', 'Detected Weight', weightStr, '#dc2626') +
                makeRow('bottle-water', 'Minimum Required', '10g PET', '#64748b') +
                makeRow('times-circle', 'Status', reason, '#dc2626');
        }

        // Show overlay
        overlay.style.display = 'flex';
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                card.style.transform = 'scale(1) translateY(0)';
                card.style.opacity   = '1';
            });
        });

        // Countdown bar
        let remaining = POPUP_DURATION_S;
        cd.textContent = remaining;
        bar.style.width = '100%';
        bar.style.transition = 'none';

        cdTimer = setInterval(() => {
            remaining--;
            cd.textContent = remaining;
            bar.style.transition = 'width 0.9s linear';
            bar.style.width = ((remaining / POPUP_DURATION_S) * 100) + '%';
            if (remaining <= 0) closeRecyclePopup();
        }, 1000);
    }

    // ── CLOSE POPUP ───────────────────────────────────────────
    window.closeRecyclePopup = function () {
        clearInterval(cdTimer);
        const overlay = document.getElementById('recycle-popup-overlay');
        const card    = document.getElementById('recycle-popup-card');
        card.style.transform = 'scale(0.88) translateY(24px)';
        card.style.opacity   = '0';
        setTimeout(() => {
            overlay.style.display = 'none';
            isShowing = false;
            showNext(); // show next in queue if any
        }, 280);
    };

    // Close on overlay click
    document.getElementById('recycle-popup-overlay').addEventListener('click', function (e) {
        if (e.target === this) closeRecyclePopup();
    });

    // ── POLLING ───────────────────────────────────────────────
    async function pollActivities() {
        try {
            const res  = await fetch(`${POLL_URL}?since=${lastChecked}`, { credentials: 'same-origin' });
            if (!res.ok) return;
            const json = await res.json();

            if (!json.authenticated) return; // session expired

            if (Array.isArray(json.activities) && json.activities.length > 0) {
                json.activities.forEach(a => queue.push(a));
                if (!isShowing) showNext();
            }

            if (json.server_time) lastChecked = json.server_time;

        } catch (e) {
            // Network error — silently skip
        }
    }

    // Start polling after a short delay so the page finishes rendering
    setTimeout(() => {
        setInterval(pollActivities, POLL_INTERVAL_MS);
    }, 2000);
})();
</script>

<?php include '../includes/footer.php'; ?>
