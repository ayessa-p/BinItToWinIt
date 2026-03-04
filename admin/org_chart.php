<?php
require_once '../config/config.php';
require_admin();

$page_title = 'Organization Chart';
$db = Database::getInstance()->getConnection();
$message = '';
$message_type = '';

// Handle create/update officer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_officer'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
    } else {
        $id = (int)($_POST['officer_id'] ?? 0);
        $full_name = sanitize_input($_POST['full_name'] ?? '');
        $position = sanitize_input($_POST['position'] ?? '');
        $display_order = (int)($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $profile_image = null;

        if ($full_name === '' || $position === '') {
            $message = 'Full name and position are required.';
            $message_type = 'error';
        } else {
            // Handle image upload
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = UPLOAD_DIR . 'officers/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png'];
                if (in_array($ext, $allowed, true)) {
                    $filename = 'officer_' . time() . '_' . preg_replace('/[^a-z0-9]+/i', '-', $full_name) . '.' . $ext;
                    $target = $upload_dir . $filename;
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target)) {
                        $profile_image = '/uploads/officers/' . $filename;
                    }
                }
            }

            try {
                if ($id > 0) {
                    if ($profile_image !== null) {
                        $stmt = $db->prepare("UPDATE org_officers SET full_name = ?, position = ?, profile_image = ?, display_order = ?, is_active = ? WHERE id = ?");
                        $stmt->execute([$full_name, $position, $profile_image, $display_order, $is_active, $id]);
                    } else {
                        $stmt = $db->prepare("UPDATE org_officers SET full_name = ?, position = ?, display_order = ?, is_active = ? WHERE id = ?");
                        $stmt->execute([$full_name, $position, $display_order, $is_active, $id]);
                    }
                    $message = 'Officer updated successfully.';
                } else {
                    $stmt = $db->prepare("INSERT INTO org_officers (full_name, position, profile_image, display_order, is_active) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$full_name, $position, $profile_image, $display_order, $is_active]);
                    $message = 'Officer added successfully.';
                }
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = 'Database error while saving officer.';
                $message_type = 'error';
            }
        }
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_officer'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $id = (int)($_POST['officer_id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM org_officers WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Officer removed.';
            $message_type = 'success';
        }
    }
}

// Officer to edit
$editing = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    if ($id > 0) {
        $stmt = $db->prepare("SELECT * FROM org_officers WHERE id = ?");
        $stmt->execute([$id]);
        $editing = $stmt->fetch();
    }
}

// Fetch officers
$stmt = $db->query("SELECT * FROM org_officers ORDER BY display_order ASC, full_name ASC");
$officers = $stmt->fetchAll();

include '../includes/admin_header.php';
?>

<div class="admin-content">
    <div class="admin-container">
        <div class="admin-page-header">
            <h1 class="admin-page-title">Organization Chart</h1>
            <p class="admin-page-subtitle">Manage MTICS officers (name, position, and picture)</p>
            <div class="admin-breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/index.php">Home</a> / Org Chart
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: var(--spacing-md);">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="admin-section">
            <h2 class="admin-section-title"><?php echo $editing ? 'Edit Officer' : 'Add Officer'; ?></h2>
            <form method="POST" enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="officer_id" value="<?php echo $editing ? (int)$editing['id'] : 0; ?>">
                <input type="hidden" name="save_officer" value="1">

                <div class="admin-form-group">
                    <label class="admin-form-label">Full Name *</label>
                    <input type="text" name="full_name" class="admin-form-input" required
                           value="<?php echo $editing ? htmlspecialchars($editing['full_name']) : ''; ?>">
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">Position *</label>
                    <input type="text" name="position" class="admin-form-input" required
                           value="<?php echo $editing ? htmlspecialchars($editing['position']) : ''; ?>">
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">Profile Picture</label>
                    <input type="file" name="profile_image" accept=".jpg,.jpeg,.png" class="admin-form-input">
                    <?php if ($editing && !empty($editing['profile_image'])): ?>
                        <div style="margin-top:0.5rem;">
                            <img src="<?php echo htmlspecialchars($editing['profile_image']); ?>" alt="Officer" style="width:72px; height:72px; border-radius:999px; object-fit:cover;">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">Display Order</label>
                    <input type="number" name="display_order" class="admin-form-input" min="0"
                           value="<?php echo $editing ? (int)$editing['display_order'] : 0; ?>">
                </div>

                <div class="admin-form-group">
                    <label style="display:flex; align-items:center; gap:0.5rem;">
                        <input type="checkbox" name="is_active" <?php echo !$editing || !empty($editing['is_active']) ? 'checked' : ''; ?>>
                        <span>Active</span>
                    </label>
                </div>

                <button type="submit" class="admin-btn admin-btn-primary">
                    <?php echo $editing ? 'Update Officer' : 'Add Officer'; ?>
                </button>
                <?php if ($editing): ?>
                    <a href="org_chart.php" class="admin-btn admin-btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="admin-section">
            <h2 class="admin-section-title">Current Officers</h2>
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Picture</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($officers)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding: var(--spacing-md); color: var(--medium-gray);">
                                    No officers added yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($officers as $officer): ?>
                                <tr>
                                    <td>
                                        <div style="width:48px; height:48px; border-radius:999px; overflow:hidden; background:#e0e7ff; display:flex; align-items:center; justify-content:center;">
                                            <?php if (!empty($officer['profile_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($officer['profile_image']); ?>" alt="Officer" style="width:100%; height:100%; object-fit:cover;">
                                            <?php else: ?>
                                                <span style="font-weight:600; color:#4f46e5;">
                                                    <?php echo strtoupper(substr($officer['full_name'], 0, 1)); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($officer['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($officer['position']); ?></td>
                                    <td><?php echo (int)$officer['display_order']; ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $officer['is_active'] ? 'success' : 'warning'; ?>">
                                            <?php echo $officer['is_active'] ? 'Active' : 'Hidden'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="org_chart.php?edit=<?php echo (int)$officer['id']; ?>" class="admin-btn admin-btn-secondary admin-btn-sm">Edit</a>
                                        <form method="POST" action="org_chart.php" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="officer_id" value="<?php echo (int)$officer['id']; ?>">
                                            <input type="hidden" name="delete_officer" value="1">
                                            <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm" onclick="return confirm('Remove this officer?');">
                                                Delete
                                            </button>
                                        </form>
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

