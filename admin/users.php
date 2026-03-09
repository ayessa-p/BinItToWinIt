<?php
require_once '../config/config.php';
require_admin();

$page_title = 'Manage Users';
$message = '';
$message_type = '';

$db = Database::getInstance()->getConnection();

// Handle ESP32 pairing
if (isset($_POST['pair_device'])) {
    $user_id = (int)$_POST['user_id'];
    $device_id = $_POST['device_id'];
    $device_name = $_POST['device_name'] ?? '';
    
    try {
        $active_check = $db->prepare("SELECT id FROM user_devices WHERE device_id = ? AND is_active = TRUE");
        $active_check->execute([$device_id]);
        
        if ($active_check->fetch()) {
            $deactivate = $db->prepare("UPDATE user_devices SET is_active = FALSE, unpaired_at = CURRENT_TIMESTAMP WHERE device_id = ? AND is_active = TRUE");
            $deactivate->execute([$device_id]);
        }
        
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
        
        $message = 'User-Device pairing successful!';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = 'Error pairing device to user: ' . $e->getMessage();
        $message_type = 'error';
    }
}

if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    try {
        $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'User status updated successfully!';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = 'Error updating user status.';
        $message_type = 'error';
    }
}

if (isset($_GET['make_admin'])) {
    $id = (int)$_GET['make_admin'];
    try {
        $stmt = $db->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'User promoted to admin successfully!';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = 'Error promoting user.';
        $message_type = 'error';
    }
}

if (isset($_POST['unpair_device'])) {
    $device_id = $_POST['device_id'];
    $user_id = $_POST['user_id'];
    
    if (empty($device_id) || empty($user_id)) {
        $message = 'Device ID and User ID are required.';
        $message_type = 'error';
    } else {
        try {
            $check = $db->prepare("SELECT ud.id, ud.device_id, ud.user_id FROM user_devices ud WHERE ud.device_id = ? AND ud.user_id = ? AND ud.is_active = TRUE");
            $check->execute([$device_id, $user_id]);
            $device = $check->fetch();
            
            if (!$device) {
                $message = 'Device not found or not paired to this user.';
                $message_type = 'error';
            } else {
                $stmt = $db->prepare("UPDATE user_devices SET is_active = FALSE, unpaired_at = CURRENT_TIMESTAMP WHERE user_id = ? AND device_id = ?");
                $stmt->execute([$user_id, $device_id]);
                $message = 'Device unpaired successfully!';
                $message_type = 'success';
            }
        } catch (PDOException $e) {
            $message = 'Error unpairing device: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

$stmt = $db->query("SELECT id, student_id, email, full_name, course, year_level, eco_tokens, is_active, is_admin, created_at, last_login FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

include '../includes/admin_header.php';
?>

<style>
/* ── Design tokens ───────────────────────────────────────────── */
:root {
    --font-sans: 'DM Sans', system-ui, sans-serif;
    --font-mono: 'DM Mono', 'Fira Mono', monospace;

    --text-xs:   0.70rem;   /* 11.2px */
    --text-sm:   0.8125rem; /* 13px   */
    --text-base: 0.9375rem; /* 15px   */
    --text-lg:   1.0625rem; /* 17px   */
    --text-xl:   1.25rem;   /* 20px   */
    --text-2xl:  1.5rem;    /* 24px   */

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
    --radius-full: 9999px;

    --shadow-sm:  0 1px 2px rgba(0,0,0,.06), 0 1px 3px rgba(0,0,0,.08);
    --shadow-md:  0 4px 6px -1px rgba(0,0,0,.07), 0 2px 4px -1px rgba(0,0,0,.05);
    --shadow-lg:  0 10px 15px -3px rgba(0,0,0,.08), 0 4px 6px -2px rgba(0,0,0,.04);

    --transition: 150ms cubic-bezier(.4,0,.2,1);
}

/* ── Google Fonts import ─────────────────────────────────────── */
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap');

/* ── Global resets scoped to admin-content ───────────────────── */
.admin-content,
.admin-content * {
    font-family: var(--font-sans);
    box-sizing: border-box;
}

/* ── Page layout ─────────────────────────────────────────────── */
.admin-content {
    padding: var(--space-8) var(--space-6);
    background: #f7f8fa;
    min-height: 100vh;
}

.admin-container {
    max-width: 1280px;
    margin: 0 auto;
}

/* ── Page header ─────────────────────────────────────────────── */
.admin-page-header {
    margin-bottom: var(--space-8);
}

.admin-page-title {
    font-size: var(--text-2xl);
    font-weight: var(--fw-bold);
    line-height: var(--lh-tight);
    color: #111827;
    margin: 0 0 var(--space-2);
    letter-spacing: -0.4px;
}

.admin-page-subtitle {
    font-size: var(--text-sm);
    font-weight: var(--fw-normal);
    color: #6b7280;
    line-height: var(--lh-normal);
    margin: 0 0 var(--space-3);
}

.admin-breadcrumb {
    font-size: var(--text-xs);
    font-weight: var(--fw-medium);
    color: #9ca3af;
    letter-spacing: 0.02em;
    text-transform: uppercase;
}

.admin-breadcrumb a {
    color: inherit;
    text-decoration: none;
    transition: color var(--transition);
}
.admin-breadcrumb a:hover { color: #374151; }

/* ── Alert ───────────────────────────────────────────────────── */
.alert {
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
.alert-success {
    background: #f0fdf4;
    color: #166534;
    border-color: #22c55e;
}
.alert-error {
    background: #fef2f2;
    color: #991b1b;
    border-color: #ef4444;
}

/* ── Section ─────────────────────────────────────────────────── */
.admin-section {
    margin-bottom: var(--space-10);
}

.admin-section-title {
    font-size: var(--text-lg);
    font-weight: var(--fw-semibold);
    color: #111827;
    line-height: var(--lh-tight);
    letter-spacing: -0.2px;
    margin: 0 0 var(--space-5);
    padding-bottom: var(--space-3);
    border-bottom: 1px solid #e5e7eb;
}

/* ── Stats grid ──────────────────────────────────────────────── */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: var(--space-4);
}

.stat-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: box-shadow var(--transition), border-color var(--transition);
}
.stat-card:hover {
    box-shadow: var(--shadow-md);
    border-color: #d1d5db;
}

.stat-header {
    padding: var(--space-4) var(--space-5);
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.stat-header h3 {
    font-size: var(--text-base);
    font-weight: var(--fw-semibold);
    color: #111827;
    line-height: var(--lh-snug);
    margin: 0 0 var(--space-1);
}

.stat-header small {
    font-size: var(--text-xs);
    font-weight: var(--fw-medium);
    color: #9ca3af;
    font-family: var(--font-mono);
    letter-spacing: 0.04em;
}

.stat-body {
    padding: var(--space-4) var(--space-5);
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: var(--space-3);
}

.stat-label {
    font-size: var(--text-sm);
    color: #6b7280;
    font-weight: var(--fw-normal);
}

.stat-value {
    font-size: var(--text-sm);
    font-weight: var(--fw-semibold);
    color: #111827;
    text-align: right;
}

/* ── Card (pair form) ────────────────────────────────────────── */
.grid.grid-2 {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: var(--space-5);
}

.card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.card-header {
    padding: var(--space-4) var(--space-6);
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.card-title {
    font-size: var(--text-base);
    font-weight: var(--fw-semibold);
    color: #111827;
    line-height: var(--lh-snug);
    margin: 0;
}

.card-body {
    padding: var(--space-6);
}

/* ── Form elements ───────────────────────────────────────────── */
.form-group {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    margin-bottom: var(--space-5);
}

.form-group:last-of-type {
    margin-bottom: var(--space-6);
}

.form-group label {
    font-size: var(--text-sm);
    font-weight: var(--fw-medium);
    color: #374151;
    line-height: var(--lh-tight);
}

.form-group input,
.form-group select {
    font-family: var(--font-sans);
    font-size: var(--text-sm);
    font-weight: var(--fw-normal);
    color: #111827;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: var(--radius-md);
    padding: var(--space-2) var(--space-3);
    height: 38px;
    outline: none;
    transition: border-color var(--transition), box-shadow var(--transition);
    width: 100%;
    appearance: none;
    -webkit-appearance: none;
}

.form-group input::placeholder { color: #9ca3af; }

.form-group input:focus,
.form-group select:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,.12);
}

/* ── Buttons ─────────────────────────────────────────────────── */
.admin-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    font-family: var(--font-sans);
    font-size: var(--text-sm);
    font-weight: var(--fw-medium);
    line-height: 1;
    padding: var(--space-2) var(--space-4);
    height: 36px;
    border: 1px solid transparent;
    border-radius: var(--radius-md);
    cursor: pointer;
    text-decoration: none;
    white-space: nowrap;
    transition: background var(--transition), border-color var(--transition),
                color var(--transition), box-shadow var(--transition), opacity var(--transition);
    -webkit-user-select: none;
    user-select: none;
}
.admin-btn:active { opacity: .85; }

.admin-btn-primary {
    background: #4f46e5;
    color: #fff;
    border-color: #4f46e5;
}
.admin-btn-primary:hover {
    background: #4338ca;
    border-color: #4338ca;
}

.admin-btn-secondary {
    background: #fff;
    color: #374151;
    border-color: #d1d5db;
    box-shadow: var(--shadow-sm);
}
.admin-btn-secondary:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
}

.admin-btn-danger {
    background: #fff;
    color: #dc2626;
    border-color: #fca5a5;
}
.admin-btn-danger:hover {
    background: #fef2f2;
    border-color: #ef4444;
}

/* Small variant */
.admin-btn-sm {
    font-size: var(--text-xs);
    height: 30px;
    padding: 0 var(--space-3);
}

/* ── Table ───────────────────────────────────────────────────── */
.admin-table-container {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    overflow-x: auto;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    font-size: var(--text-sm);
    color: #374151;
}

.admin-table thead {
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.admin-table th {
    padding: var(--space-3) var(--space-5);
    text-align: left;
    font-size: var(--text-xs);
    font-weight: var(--fw-semibold);
    color: #6b7280;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    white-space: nowrap;
}

.admin-table tbody tr {
    border-bottom: 1px solid #f3f4f6;
    transition: background var(--transition);
}
.admin-table tbody tr:last-child { border-bottom: none; }
.admin-table tbody tr:hover { background: #fafafa; }

.admin-table td {
    padding: var(--space-4) var(--space-5);
    vertical-align: middle;
    line-height: var(--lh-snug);
    color: #374151;
}

/* Monospace for IDs */
.admin-table td:first-child {
    font-family: var(--font-mono);
    font-size: var(--text-xs);
    font-weight: var(--fw-medium);
    color: #6b7280;
    letter-spacing: 0.04em;
}

/* Strong token value */
.admin-table td strong {
    font-weight: var(--fw-semibold);
    color: #111827;
}

/* ── Badges ──────────────────────────────────────────────────── */
.badge {
    display: inline-flex;
    align-items: center;
    padding: var(--space-1) var(--space-3);
    font-size: var(--text-xs);
    font-weight: var(--fw-semibold);
    line-height: 1;
    border-radius: var(--radius-full);
    letter-spacing: 0.03em;
    white-space: nowrap;
    background: #f3f4f6;
    color: #6b7280;
    border: 1px solid #e5e7eb;
}

.badge-success {
    background: #f0fdf4;
    color: #15803d;
    border-color: #bbf7d0;
}

.badge-danger {
    background: #fef2f2;
    color: #dc2626;
    border-color: #fecaca;
}

.badge-info {
    background: #eff6ff;
    color: #1d4ed8;
    border-color: #bfdbfe;
}

/* ── Action cell layout ──────────────────────────────────────── */
.action-stack {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    min-width: 120px;
}

.device-cell {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.device-name {
    font-size: var(--text-sm);
    font-weight: var(--fw-medium);
    color: #374151;
}
</style>

<div class="admin-content">
    <div class="admin-container">

        <!-- ── Page header ── -->
        <div class="admin-page-header">
            <h1 class="admin-page-title">Manage Users</h1>
            <p class="admin-page-subtitle">View users, activate/deactivate accounts, and promote to admin.</p>
            <div class="admin-breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/index.php">Home</a> &rsaquo; Users
            </div>
        </div>

        <!-- ── Alert ── -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- ── Recycling stats ── -->
        <div class="admin-section">
            <h2 class="admin-section-title">📊 User Recycling Statistics</h2>
            <div class="stats-grid">
                <?php foreach ($users as $user): ?>
                    <div class="stat-card">
                        <div class="stat-header">
                            <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                            <small><?php echo htmlspecialchars($user['student_id']); ?></small>
                        </div>
                        <div class="stat-body">
                            <div class="stat-item">
                                <span class="stat-label">Total Tokens</span>
                                <span class="stat-value"><?php echo format_tokens($user['eco_tokens']); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Bottles Recycled</span>
                                <span class="stat-value"><?php
                                    $s = $db->prepare("SELECT COUNT(*) as t FROM recycling_activities WHERE user_id = ?");
                                    $s->execute([$user['id']]);
                                    $r = $s->fetch();
                                    echo ($r && $r['t']) ? (int)$r['t'] : '0';
                                ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Paired Device</span>
                                <span class="stat-value"><?php
                                    $s = $db->prepare("SELECT device_name FROM user_devices WHERE user_id = ? AND is_active = TRUE");
                                    $s->execute([$user['id']]);
                                    $d = $s->fetch();
                                    echo $d ? htmlspecialchars($d['device_name']) : '—';
                                ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ── Pair device ── -->
        <div class="admin-section">
            <h2 class="admin-section-title">🔗 Pair ESP32 Device to User</h2>
            <div class="grid grid-2">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Pair Device</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="pair_device" value="1">

                            <div class="form-group">
                                <label for="pair_user_id">Select User</label>
                                <select id="pair_user_id" name="user_id" required>
                                    <option value="">Choose a user…</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['full_name'] . ' (' . $user['student_id'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="pair_device_id">ESP32 Device ID</label>
                                <input id="pair_device_id" type="text" name="device_id" placeholder="e.g. ESP32_001" required>
                            </div>

                            <div class="form-group">
                                <label for="pair_device_name">Device Name <span style="color:#9ca3af;font-weight:400;">(optional)</span></label>
                                <input id="pair_device_name" type="text" name="device_name" placeholder="e.g. Main Recycling Station">
                            </div>

                            <button type="submit" class="admin-btn admin-btn-primary" style="width:100%;">
                                Pair Device to User
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── All users table ── -->
        <div class="admin-section">
            <h2 class="admin-section-title">All Users (<?php echo count($users); ?>)</h2>
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Course</th>
                            <th>Tokens</th>
                            <th>Bottles</th>
                            <th>Paired Device</th>
                            <th>Status</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['student_id']); ?></td>
                            <td style="font-weight:500;color:#111827;"><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td style="color:#6b7280;"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['course'] ?: '—'); ?></td>
                            <td><strong><?php echo format_tokens($user['eco_tokens']); ?></strong></td>
                            <td style="text-align:center;"><?php
                                $s = $db->prepare("SELECT COUNT(*) as t FROM recycling_activities WHERE user_id = ?");
                                $s->execute([$user['id']]);
                                $r = $s->fetch();
                                echo ($r && $r['t']) ? (int)$r['t'] : 0;
                            ?></td>
                            <td>
                                <?php
                                    $ds = $db->prepare("SELECT device_id, device_name FROM user_devices WHERE user_id = ? AND is_active = TRUE");
                                    $ds->execute([$user['id']]);
                                    $device = $ds->fetch();
                                ?>
                                <?php if ($device): ?>
                                    <div class="device-cell">
                                        <span class="device-name"><?php echo htmlspecialchars($device['device_name'] ?: $device['device_id']); ?></span>
                                        <form method="POST" action="" style="margin:0;">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="unpair_device" value="1">
                                            <input type="hidden" name="device_id" value="<?php echo htmlspecialchars($device['device_id']); ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm" style="width:100%;"
                                                    onclick="return confirm('Unpair this device from user?');">
                                                Unpair
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span style="color:#9ca3af;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $user['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['is_admin']): ?>
                                    <span class="badge badge-info">Admin</span>
                                <?php else: ?>
                                    <span class="badge">User</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-stack">
                                    <a href="tokens.php?user_id=<?php echo $user['id']; ?>"
                                       class="admin-btn admin-btn-secondary admin-btn-sm">
                                        Manage Tokens
                                    </a>
                                    <?php if (!$user['is_admin']): ?>
                                        <a href="?make_admin=<?php echo $user['id']; ?>"
                                           class="admin-btn admin-btn-primary admin-btn-sm"
                                           onclick="return confirm('Promote this user to admin?');">
                                            Make Admin
                                        </a>
                                    <?php endif; ?>
                                    <a href="?toggle_status=<?php echo $user['id']; ?>"
                                       class="admin-btn admin-btn-<?php echo $user['is_active'] ? 'danger' : 'primary'; ?> admin-btn-sm">
                                        <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /.admin-container -->
</div><!-- /.admin-content -->

<?php include '../includes/admin_footer.php'; ?>