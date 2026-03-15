<?php
require_once '../config/config.php';
require_admin();

$page_title = 'Manage Events';
$message = '';
$message_type = '';

$db = Database::getInstance()->getConnection();

// Handle create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
    } else {
        $id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
        $title = sanitize_input($_POST['title'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $event_date = $_POST['event_date'] ?? '';
        $location = sanitize_input($_POST['location'] ?? '');
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        $attendance_enabled = isset($_POST['attendance_enabled']) ? 1 : 0;

        $has_thumbnail = false;
        $has_gallery = false;
        try {
            $colStmt = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND COLUMN_NAME IN ('thumbnail_url','gallery_json')");
            $colStmt->execute();
            $cols = $colStmt->fetchAll(PDO::FETCH_COLUMN);
            $has_thumbnail = in_array('thumbnail_url', $cols);
            $has_gallery = in_array('gallery_json', $cols);
        } catch (Exception $e) {}

        $image_url = '';
        $thumbnail_url = '';
        $gallery_array = [];

        if ($id > 0) {
            $stmt = $db->prepare("SELECT image_url" . ($has_thumbnail ? ", thumbnail_url" : "") . ($has_gallery ? ", gallery_json" : "") . " FROM events WHERE id = ?");
            $stmt->execute([$id]);
            $existing = $stmt->fetch();
            if ($existing) {
                $image_url = $existing['image_url'] ?? '';
                if ($has_thumbnail) $thumbnail_url = $existing['thumbnail_url'] ?? '';
                if ($has_gallery && !empty($existing['gallery_json'])) {
                    $decoded = json_decode($existing['gallery_json'], true);
                    if (is_array($decoded)) $gallery_array = $decoded;
                }
            }
        }

        if ($id > 0) {
            if (!empty($_POST['delete_thumbnail']) && !empty($thumbnail_url)) {
                $file_path = __DIR__ . '/../' . $thumbnail_url;
                if (file_exists($file_path)) @unlink($file_path);
                $thumbnail_url = '';
            }
            if (!empty($_POST['delete_gallery']) && is_array($_POST['delete_gallery']) && count($gallery_array) > 0) {
                foreach ($_POST['delete_gallery'] as $del) {
                    $file_path = __DIR__ . '/../' . $del;
                    if (file_exists($file_path)) @unlink($file_path);
                    $idx = array_search($del, $gallery_array);
                    if ($idx !== false) unset($gallery_array[$idx]);
                }
                $gallery_array = array_values($gallery_array);
            }
        }

        $upload_dir = '../uploads/events/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        if ($has_thumbnail && isset($_FILES['thumbnail_image']) && $_FILES['thumbnail_image']['error'] == UPLOAD_ERR_OK) {
            $file_name = time() . '_thumb_' . basename($_FILES['thumbnail_image']['name']);
            if (move_uploaded_file($_FILES['thumbnail_image']['tmp_name'], $upload_dir . $file_name)) {
                $thumbnail_url = 'uploads/events/' . $file_name;
            }
        }

        if ($has_gallery && isset($_FILES['gallery_images'])) {
            $files = $_FILES['gallery_images'];
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $unique_id = microtime(true) * 10000;
                    $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                    $file_name = 'gallery_' . $unique_id . '_' . $i . '.' . $ext;
                    if (move_uploaded_file($files['tmp_name'][$i], $upload_dir . $file_name)) {
                        $gallery_array[] = 'uploads/events/' . $file_name;
                    }
                }
            }
        }

        if (empty($title) || empty($description) || empty($event_date)) {
            $message = 'Please fill in all required fields.';
            $message_type = 'error';
        } else {
            try {
                if ($has_thumbnail && $has_gallery) {
                    $gallery_json = json_encode($gallery_array);
                    if ($id > 0) {
                        $stmt = $db->prepare("UPDATE events SET title=?, description=?, event_date=?, location=?, image_url=?, thumbnail_url=?, gallery_json=?, is_published=?, attendance_enabled=? WHERE id=?");
                        $stmt->execute([$title, $description, $event_date, $location, $image_url, $thumbnail_url, $gallery_json, $is_published, $attendance_enabled, $id]);
                        $message = 'Event updated successfully!';
                    } else {
                        $stmt = $db->prepare("INSERT INTO events (title, description, event_date, location, image_url, thumbnail_url, gallery_json, is_published, attendance_enabled, attendance_closed) VALUES (?,?,?,?,?,?,?,?,?,0)");
                        $stmt->execute([$title, $description, $event_date, $location, $image_url, $thumbnail_url, $gallery_json, $is_published, $attendance_enabled]);
                        $message = 'Event created successfully!';
                    }
                } else {
                    if ($id > 0) {
                        $stmt = $db->prepare("UPDATE events SET title=?, description=?, event_date=?, location=?, image_url=?, is_published=?, attendance_enabled=? WHERE id=?");
                        $stmt->execute([$title, $description, $event_date, $location, $image_url, $is_published, $attendance_enabled, $id]);
                        $message = 'Event updated successfully!';
                    } else {
                        $stmt = $db->prepare("INSERT INTO events (title, description, event_date, location, image_url, is_published, attendance_enabled, attendance_closed) VALUES (?,?,?,?,?,?,?,0)");
                        $stmt->execute([$title, $description, $event_date, $location, $image_url, $is_published, $attendance_enabled]);
                        $message = 'Event created successfully!';
                    }
                }
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = 'Database error: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Event deleted successfully!';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = 'Error deleting event.';
        $message_type = 'error';
    }
}

// Handle close/reopen attendance
if (isset($_GET['toggle_attendance_closed'])) {
    $id = (int)$_GET['toggle_attendance_closed'];
    try {
        $stmt = $db->prepare("UPDATE events SET attendance_closed = NOT attendance_closed WHERE id = ?");
        $stmt->execute([$id]);
        $stmt2 = $db->prepare("SELECT attendance_closed FROM events WHERE id = ?");
        $stmt2->execute([$id]);
        $row = $stmt2->fetch();
        $message = $row['attendance_closed']
            ? 'Attendance closed. Users can no longer submit.'
            : 'Attendance reopened. Users can now submit again.';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = 'Error updating attendance status.';
        $message_type = 'error';
    }
}

// Handle manage attendance redirect
if (isset($_GET['manage_attendance'])) {
    header('Location: manage_attendance.php?event_id=' . (int)$_GET['manage_attendance']);
    exit;
}

$edit_event = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_event = $stmt->fetch();
}

$stmt = $db->query("SELECT * FROM events ORDER BY event_date DESC");
$all_events = $stmt->fetchAll();

include '../includes/admin_header.php';
?>

<div class="admin-content">
    <div class="admin-container">
        <div class="admin-page-header">
            <h1 class="admin-page-title">Manage Events</h1>
            <p class="admin-page-subtitle">Create, edit, and publish events</p>
            <div class="admin-breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/index.php">Home</a> / Events
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: var(--spacing-md);">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="admin-section">
            <h2 class="admin-section-title"><?php echo $edit_event ? 'Edit Event' : 'Create New Event'; ?></h2>
            <form method="POST" class="admin-form" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <?php if ($edit_event): ?>
                    <input type="hidden" name="event_id" value="<?php echo $edit_event['id']; ?>">
                <?php endif; ?>

                <div class="admin-form-group">
                    <label class="admin-form-label">Title *</label>
                    <input type="text" name="title" class="admin-form-input"
                           value="<?php echo $edit_event ? htmlspecialchars($edit_event['title']) : ''; ?>" required>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">Description *</label>
                    <textarea name="description" class="admin-form-textarea" required><?php echo $edit_event ? htmlspecialchars($edit_event['description']) : ''; ?></textarea>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">Event Date & Time *</label>
                    <input type="datetime-local" name="event_date" class="admin-form-input"
                           value="<?php echo $edit_event ? date('Y-m-d\TH:i', strtotime($edit_event['event_date'])) : ''; ?>" required>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">Location</label>
                    <input type="text" name="location" class="admin-form-input"
                           value="<?php echo $edit_event ? htmlspecialchars($edit_event['location']) : ''; ?>">
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">Thumbnail Image</label>
                    <input type="file" name="thumbnail_image" class="admin-form-input" accept="image/*">
                    <?php if ($edit_event && !empty($edit_event['thumbnail_url'])): ?>
                        <div style="margin-top:0.6rem; display:flex; align-items:center; gap:0.6rem;">
                            <img src="<?php echo SITE_URL . '/' . htmlspecialchars($edit_event['thumbnail_url']); ?>" style="width:80px; height:60px; object-fit:cover; border-radius:6px;">
                            <label style="font-size:0.9rem; color:var(--medium-gray);">
                                <input type="checkbox" name="delete_thumbnail" value="1"> Delete thumbnail
                            </label>
                        </div>
                    <?php endif; ?>
                    <small style="color:var(--medium-gray); display:block; margin-top:0.5rem;">Upload a small thumbnail (recommended 4:3)</small>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">Gallery Images</label>
                    <input type="file" name="gallery_images[]" class="admin-form-input" accept="image/*" multiple>
                    <small style="color:var(--medium-gray); display:block; margin-top:0.5rem;">Upload multiple images (optional) — 4:3 recommended</small>
                    <?php if ($edit_event && !empty($edit_event['gallery_json'])): ?>
                        <?php $existing_gallery = json_decode($edit_event['gallery_json'], true) ?: []; ?>
                        <?php if (!empty($existing_gallery)): ?>
                            <div style="margin-top:0.6rem; display:flex; gap:0.6rem; flex-wrap:wrap;">
                                <?php foreach ($existing_gallery as $gimg): ?>
                                    <div style="width:90px;">
                                        <img src="<?php echo SITE_URL . '/' . htmlspecialchars($gimg); ?>" style="width:90px; height:70px; object-fit:cover; border-radius:6px; display:block;">
                                        <label style="font-size:0.8rem; display:block; margin-top:0.25rem;">
                                            <input type="checkbox" name="delete_gallery[]" value="<?php echo htmlspecialchars($gimg); ?>"> Remove
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="admin-form-group">
                    <label style="display:flex; align-items:center; gap:0.5rem;">
                        <input type="checkbox" name="is_published" <?php echo ($edit_event && $edit_event['is_published']) ? 'checked' : ''; ?>>
                        <span>Publish Event</span>
                    </label>
                </div>

                <div class="admin-form-group">
                    <label style="display:flex; align-items:center; gap:0.5rem;">
                        <input type="checkbox" name="attendance_enabled" <?php echo ($edit_event && $edit_event['attendance_enabled']) ? 'checked' : ''; ?>>
                        <span>Enable Attendance Tracking</span>
                    </label>
                    <small style="color:var(--medium-gray); display:block; margin-top:0.5rem;">Allow users to submit attendance with proof photos and earn tokens</small>
                </div>

                <button type="submit" class="admin-btn admin-btn-primary">
                    <?php echo $edit_event ? 'Update Event' : 'Create Event'; ?>
                </button>
                <?php if ($edit_event): ?>
                    <a href="events.php" class="admin-btn admin-btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="admin-section">
            <h2 class="admin-section-title">All Events</h2>
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Participants</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_events)): ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:var(--spacing-lg); color:var(--medium-gray);">
                                    No events found. Create your first event above.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_events as $event): ?>
                            <tr>
                                <td>
                                    <?php
                                        $thumb = '';
                                        if (!empty($event['thumbnail_url']))      $thumb = $event['thumbnail_url'];
                                        elseif (!empty($event['image_url']))      $thumb = $event['image_url'];
                                        elseif (!empty($event['gallery_json'])) {
                                            $g = json_decode($event['gallery_json'], true);
                                            if (is_array($g) && count($g) > 0) $thumb = $g[0];
                                        }
                                    ?>
                                    <?php if (!empty($thumb)): ?>
                                        <img src="<?php echo SITE_URL . '/' . htmlspecialchars($thumb); ?>"
                                             alt="<?php echo htmlspecialchars($event['title']); ?>"
                                             style="width:48px; height:36px; object-fit:cover; border-radius:var(--radius-sm);">
                                    <?php else: ?>
                                        <span style="color:var(--medium-gray); font-size:0.9rem;">No image</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($event['title']); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($event['event_date'])); ?></td>
                                <td><?php echo htmlspecialchars($event['location'] ?: 'N/A'); ?></td>
                                <td>
                                    <?php
                                        $stmt_att = $db->prepare("SELECT COUNT(*) as total, COUNT(CASE WHEN attendance_status='approved' THEN 1 END) as approved, COUNT(CASE WHEN attendance_status='pending' THEN 1 END) as pending FROM event_attendance WHERE event_id=?");
                                        $stmt_att->execute([$event['id']]);
                                        $att = $stmt_att->fetch();
                                        echo "<span style='color:var(--primary-blue);font-weight:bold;'>" . $att['approved'] . "</span>";
                                        if ($att['pending'] > 0) echo " <small style='color:orange;'>(" . $att['pending'] . " pending)</small>";
                                    ?>
                                </td>
                                <td>
                                    <div style="display:flex; flex-direction:column; gap:0.25rem;">
                                        <span class="badge <?php echo $event['is_published'] ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo $event['is_published'] ? 'Published' : 'Draft'; ?>
                                        </span>
                                        <?php if ($event['attendance_enabled']): ?>
                                            <span class="badge badge-info">Attendance ON</span>
                                        <?php endif; ?>
                                        <?php if (!empty($event['attendance_closed'])): ?>
                                            <span class="badge" style="background:#dc3545;color:#fff;padding:2px 8px;border-radius:4px;font-size:0.75rem;">
                                                🔒 Attendance Closed
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="display:flex; flex-direction:column; gap:0.5rem;">
                                        <a href="?edit=<?php echo $event['id']; ?>" class="admin-btn admin-btn-secondary admin-btn-sm" style="width:100%;text-align:center;">Edit</a>
                                        <a href="?delete=<?php echo $event['id']; ?>"
                                           class="admin-btn admin-btn-danger admin-btn-sm"
                                           style="width:100%;text-align:center;"
                                           onclick="return confirm('Are you sure you want to delete this event?');">Delete</a>

                                        <?php if ($event['attendance_enabled']): ?>

                                            <!-- ── Live Check-in button ── -->
                                            <a href="event_checkin.php?event_id=<?php echo $event['id']; ?>"
                                               class="admin-btn admin-btn-sm"
                                               style="width:100%;text-align:center;background:#7c3aed;color:#fff;border-color:#7c3aed;"
                                               target="_blank">
                                                🔍 Live Check-in
                                            </a>

                                            <a href="?manage_attendance=<?php echo $event['id']; ?>" class="admin-btn admin-btn-primary admin-btn-sm" style="width:100%;text-align:center;">Manage Attendance</a>

                                            <?php if (empty($event['attendance_closed'])): ?>
                                                <a href="?toggle_attendance_closed=<?php echo $event['id']; ?>"
                                                   class="admin-btn admin-btn-warning admin-btn-sm"
                                                   style="width:100%;text-align:center;background:#f59e0b;color:#fff;border-color:#f59e0b;"
                                                   onclick="return confirm('Close attendance? Users will no longer be able to submit.');">
                                                    🔒 Close Attendance
                                                </a>
                                            <?php else: ?>
                                                <a href="?toggle_attendance_closed=<?php echo $event['id']; ?>"
                                                   class="admin-btn admin-btn-success admin-btn-sm"
                                                   style="width:100%;text-align:center;"
                                                   onclick="return confirm('Reopen attendance? Users will be able to submit again.');">
                                                    🔓 Reopen Attendance
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
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