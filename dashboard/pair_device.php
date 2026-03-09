<?php
require_once '../config/config.php';

$page_title = 'Pair ESP32 Device';
$message = '';
$message_type = '';

$db = Database::getInstance()->getConnection();

// Handle device pairing
if (isset($_POST['pair_device'])) {
    $device_id = $_POST['device_id'];
    $device_name = $_POST['device_name'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    try {
        $active_check = $db->prepare("SELECT id, user_id, device_name FROM user_devices WHERE device_id = ? AND is_active = TRUE");
        $active_check->execute([$device_id]);
        $active_pairing = $active_check->fetch();
        
        if ($active_pairing) {
            if ($active_pairing['user_id'] == $user_id) {
                $message = 'This device is already paired to your account!';
                $message_type = 'warning';
            } else {
                $message = 'This device is currently paired to another user. Please contact an administrator.';
                $message_type = 'error';
            }
        } else {
            $inactive_check = $db->prepare("SELECT id FROM user_devices WHERE device_id = ? AND is_active = FALSE");
            $inactive_check->execute([$device_id]);
            $inactive_pairing = $inactive_check->fetch();
            
            if ($inactive_pairing) {
                $stmt = $db->prepare("UPDATE user_devices SET user_id = ?, device_name = ?, is_active = TRUE, paired_at = CURRENT_TIMESTAMP, unpaired_at = NULL WHERE device_id = ?");
                $stmt->execute([$user_id, $device_name, $device_id]);
            } else {
                $stmt = $db->prepare("INSERT INTO user_devices (user_id, device_id, device_name, is_active, paired_at) VALUES (?, ?, ?, TRUE, CURRENT_TIMESTAMP)");
                $stmt->execute([$user_id, $device_id, $device_name]);
            }
            
            $message = 'Device paired successfully! You can now start recycling.';
            $message_type = 'success';
        }
    } catch (PDOException $e) {
        $message = 'Error pairing device: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Handle device unpairing
if (isset($_POST['unpair_device'])) {
    $device_id = $_POST['device_id'];
    $user_id = $_SESSION['user_id'];
    
    try {
        $stmt = $db->prepare("UPDATE user_devices SET is_active = FALSE, unpaired_at = CURRENT_TIMESTAMP WHERE user_id = ? AND device_id = ?");
        $stmt->execute([$user_id, $device_id]);
        $message = 'Device unpaired successfully!';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = 'Error unpairing device.';
        $message_type = 'error';
    }
}

// Get user's paired devices
$paired_devices = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("
        SELECT ud.*, u.full_name as user_name 
        FROM user_devices ud 
        JOIN users u ON ud.user_id = u.id 
        WHERE ud.user_id = ? AND ud.is_active = TRUE
        ORDER BY ud.paired_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $paired_devices = $stmt->fetchAll();
}

include '../includes/header.php';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=DM+Mono:wght@400;500&display=swap');

/* ── Design tokens ───────────────────────────────────────────── */
.pair-page,
.pair-page * {
    font-family: 'DM Sans', system-ui, sans-serif;
    box-sizing: border-box;
}

.pair-page {
    --text-xs:    0.70rem;
    --text-sm:    0.8125rem;
    --text-base:  0.9375rem;
    --text-lg:    1.0625rem;
    --text-xl:    1.25rem;
    --text-2xl:   1.5rem;

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

    --radius-sm:  4px;
    --radius-md:  8px;
    --radius-lg:  12px;
    --radius-full:9999px;

    --shadow-sm: 0 1px 2px rgba(0,0,0,.06), 0 1px 3px rgba(0,0,0,.08);
    --shadow-md: 0 4px 6px -1px rgba(0,0,0,.07), 0 2px 4px -1px rgba(0,0,0,.05);

    --transition: 150ms cubic-bezier(.4,0,.2,1);

    --color-text:       #111827;
    --color-text-muted: #6b7280;
    --color-text-faint: #9ca3af;
    --color-border:     #e5e7eb;
    --color-surface:    #fff;
    --color-bg:         #f7f8fa;
    --color-primary:    #4f46e5;
    --color-primary-hover: #4338ca;
}

/* ── Section wrapper ─────────────────────────────────────────── */
.pair-page .section {
    padding: var(--space-10) var(--space-6);
    background: var(--color-bg);
    min-height: 100vh;
}

.pair-page .container {
    max-width: 960px;
    margin: 0 auto;
}

/* ── Page title ──────────────────────────────────────────────── */
.pair-page .section-title {
    font-size: var(--text-2xl);
    font-weight: var(--fw-bold);
    color: var(--color-text);
    letter-spacing: -0.4px;
    line-height: var(--lh-tight);
    margin: 0 0 var(--space-6);
}

/* ── Alert ───────────────────────────────────────────────────── */
.pair-page .alert {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3) var(--space-5);
    border-radius: var(--radius-md);
    font-size: var(--text-sm);
    font-weight: var(--fw-medium);
    line-height: var(--lh-snug);
    margin-bottom: var(--space-6);
    border-left: 3px solid transparent;
}
.pair-page .alert-success { background:#f0fdf4; color:#166534; border-color:#22c55e; }
.pair-page .alert-error   { background:#fef2f2; color:#991b1b; border-color:#ef4444; }
.pair-page .alert-warning { background:#fffbeb; color:#92400e; border-color:#f59e0b; }

/* ── Grid ────────────────────────────────────────────────────── */
.pair-page .grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-6);
    margin-bottom: var(--space-10);
}
@media (max-width: 700px) {
    .pair-page .grid-2 { grid-template-columns: 1fr; }
}

/* ── Card ────────────────────────────────────────────────────── */
.pair-page .card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.pair-page .card-header {
    padding: var(--space-4) var(--space-6);
    background: #f9fafb;
    border-bottom: 1px solid var(--color-border);
}

.pair-page .card-title {
    font-size: var(--text-base);
    font-weight: var(--fw-semibold);
    color: var(--color-text);
    line-height: var(--lh-snug);
    margin: 0;
}

.pair-page .card-body {
    padding: var(--space-6);
    flex: 1;
}

/* ── Form ────────────────────────────────────────────────────── */
.pair-page .form-group {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    margin-bottom: var(--space-5);
}

.pair-page .form-group label {
    font-size: var(--text-sm);
    font-weight: var(--fw-medium);
    color: #374151;
    line-height: var(--lh-tight);
}

.pair-page .form-group input {
    font-family: 'DM Sans', system-ui, sans-serif;
    font-size: var(--text-sm);
    color: var(--color-text);
    background: var(--color-surface);
    border: 1px solid #d1d5db;
    border-radius: var(--radius-md);
    padding: var(--space-2) var(--space-3);
    height: 38px;
    outline: none;
    width: 100%;
    transition: border-color var(--transition), box-shadow var(--transition);
}
.pair-page .form-group input::placeholder { color: var(--color-text-faint); }
.pair-page .form-group input:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,.12);
}

.pair-page .form-hint {
    font-size: var(--text-xs);
    color: var(--color-text-muted);
    line-height: var(--lh-normal);
    margin: 0;
}

/* ── Buttons ─────────────────────────────────────────────────── */
.pair-page .btn {
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
.pair-page .btn:active { opacity: .85; }

.pair-page .btn-primary {
    background: var(--color-primary);
    color: #fff;
    border-color: var(--color-primary);
}
.pair-page .btn-primary:hover {
    background: var(--color-primary-hover);
    border-color: var(--color-primary-hover);
}

.pair-page .btn-danger {
    background: #fff;
    color: #dc2626;
    border-color: #fca5a5;
}
.pair-page .btn-danger:hover {
    background: #fef2f2;
    border-color: #ef4444;
}

.pair-page .btn-sm {
    font-size: var(--text-xs);
    height: 30px;
    padding: 0 var(--space-3);
}

/* ── Device list ─────────────────────────────────────────────── */
.pair-page .device-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.pair-page .device-item {
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: var(--space-4) var(--space-5);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: var(--space-4);
    background: #fafafa;
    transition: border-color var(--transition), box-shadow var(--transition);
}
.pair-page .device-item:hover {
    border-color: #d1d5db;
    box-shadow: var(--shadow-sm);
    background: var(--color-surface);
}

.pair-page .device-info {
    flex: 1;
    min-width: 0;
}

.pair-page .device-info h4 {
    font-size: var(--text-base);
    font-weight: var(--fw-semibold);
    color: var(--color-text);
    line-height: var(--lh-snug);
    margin: 0 0 var(--space-3);
}

.pair-page .device-meta {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.pair-page .device-meta-row {
    display: flex;
    gap: var(--space-2);
    font-size: var(--text-sm);
    line-height: var(--lh-snug);
}

.pair-page .device-meta-label {
    color: var(--color-text-muted);
    font-weight: var(--fw-normal);
    flex-shrink: 0;
}

.pair-page .device-meta-value {
    color: var(--color-text);
    font-weight: var(--fw-medium);
    font-family: 'DM Mono', monospace;
    font-size: var(--text-xs);
    letter-spacing: 0.02em;
}

/* Non-mono values */
.pair-page .device-meta-value.text {
    font-family: 'DM Sans', system-ui, sans-serif;
    font-size: var(--text-sm);
    letter-spacing: 0;
}

.pair-page .device-actions {
    flex-shrink: 0;
    display: flex;
    align-items: flex-start;
    padding-top: var(--space-1);
}

/* ── Empty state ─────────────────────────────────────────────── */
.pair-page .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: var(--space-10) var(--space-6);
    gap: var(--space-3);
}

.pair-page .empty-state-icon {
    font-size: 2rem;
    line-height: 1;
}

.pair-page .empty-state p {
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    line-height: var(--lh-normal);
    max-width: 260px;
    margin: 0;
}
</style>

<section class="section pair-page">
    <div class="container">
        <h1 class="section-title">🔗 Pair ESP32 Device</h1>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="grid-2">
            <!-- ── Pair new device ── -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">🔗 Pair New Device</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="pair_device" value="1">

                        <div class="form-group">
                            <label for="device_id">ESP32 Device ID</label>
                            <input id="device_id" type="text" name="device_id"
                                   placeholder="Enter device ID from ESP32 display" required>
                            <p class="form-hint">Look at the ESP32 device screen or ask an administrator for the device ID.</p>
                        </div>

                        <div class="form-group" style="margin-bottom: var(--space-6);">
                            <label for="device_name">Device Name <span style="color:#9ca3af;font-weight:400;">(optional)</span></label>
                            <input id="device_name" type="text" name="device_name"
                                   placeholder="e.g. My Home Recycler">
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%;">
                            Pair Device
                        </button>
                    </form>
                </div>
            </div>

            <!-- ── My paired devices ── -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📱 My Paired Devices</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($paired_devices)): ?>
                        <div class="empty-state">
                            <span class="empty-state-icon">📭</span>
                            <p>No devices paired yet. Pair your first ESP32 device to start recycling!</p>
                        </div>
                    <?php else: ?>
                        <div class="device-list">
                            <?php foreach ($paired_devices as $device): ?>
                                <div class="device-item">
                                    <div class="device-info">
                                        <h4><?php echo htmlspecialchars($device['device_name'] ?: $device['device_id']); ?></h4>
                                        <div class="device-meta">
                                            <div class="device-meta-row">
                                                <span class="device-meta-label">Device ID</span>
                                                <span class="device-meta-value"><?php echo htmlspecialchars($device['device_id']); ?></span>
                                            </div>
                                            <div class="device-meta-row">
                                                <span class="device-meta-label">Paired</span>
                                                <span class="device-meta-value text"><?php echo date('M j, Y g:i A', strtotime($device['paired_at'])); ?></span>
                                            </div>
                                            <div class="device-meta-row">
                                                <span class="device-meta-label">Last activity</span>
                                                <span class="device-meta-value text"><?php echo $device['last_activity_at'] ? date('M j, Y g:i A', strtotime($device['last_activity_at'])) : '—'; ?></span>
                                            </div>
                                            <div class="device-meta-row">
                                                <span class="device-meta-label">Recycled</span>
                                                <span class="device-meta-value text"><?php echo number_format($device['total_recycled_weight'], 1) . 'g'; ?></span>
                                            </div>
                                            <div class="device-meta-row">
                                                <span class="device-meta-label">Tokens earned</span>
                                                <span class="device-meta-value text"><?php echo format_tokens($device['total_tokens_earned']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="device-actions">
                                        <form method="POST" action="">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="unpair_device" value="1">
                                            <input type="hidden" name="device_id" value="<?php echo htmlspecialchars($device['device_id']); ?>">
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Unpair this device?')">
                                                Unpair
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>