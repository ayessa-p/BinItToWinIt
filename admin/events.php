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
        
        if (empty($title) || empty($description) || empty($event_date)) {
            $message = 'Please fill in all required fields.';
            $message_type = 'error';
        } else {
            try {
                if ($id > 0) {
                    // Update
                    $stmt = $db->prepare("
                        UPDATE events SET title = ?, description = ?, event_date = ?, 
                        location = ?, is_published = ? WHERE id = ?
                    ");
                    $stmt->execute([$title, $description, $event_date, $location, $is_published, $id]);
                    $message = 'Event updated successfully!';
                } else {
                    // Create
                    $stmt = $db->prepare("
                        INSERT INTO events (title, description, event_date, location, is_published) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$title, $description, $event_date, $location, $is_published]);
                    $message = 'Event created successfully!';
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

// Get event to edit
$edit_event = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $edit_event = $stmt->fetch();
}

// Get all events
$stmt = $db->query("SELECT * FROM events ORDER BY event_date DESC");
$events = $stmt->fetchAll();

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
            <form method="POST" class="admin-form">
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
                    <label style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="is_published" 
                               <?php echo ($edit_event && $edit_event['is_published']) ? 'checked' : ''; ?>>
                        <span>Publish Event</span>
                    </label>
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
                            <th>Title</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($events)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: var(--spacing-lg); color: var(--medium-gray);">
                                    No events found. Create your first event above.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['title']); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($event['event_date'])); ?></td>
                                <td><?php echo htmlspecialchars($event['location'] ?: 'N/A'); ?></td>
                                <td>
                                    <span class="badge <?php echo $event['is_published'] ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo $event['is_published'] ? 'Published' : 'Draft'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?edit=<?php echo $event['id']; ?>" class="admin-btn admin-btn-secondary admin-btn-sm">Edit</a>
                                    <a href="?delete=<?php echo $event['id']; ?>" 
                                       class="admin-btn admin-btn-danger admin-btn-sm"
                                       onclick="return confirm('Are you sure you want to delete this event?');">Delete</a>
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
