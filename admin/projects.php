<?php
require_once '../config/config.php';
require_admin();

$page_title = 'Manage Projects';
$message = '';
$message_type = '';

$db = Database::getInstance()->getConnection();

// Handle create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
    } else {
        $id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        $name = sanitize_input($_POST['name'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $status = sanitize_input($_POST['status'] ?? 'active');
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        
        if (empty($name) || empty($description)) {
            $message = 'Please fill in all required fields.';
            $message_type = 'error';
        } else {
            try {
                if ($id > 0) {
                    $stmt = $db->prepare("
                        UPDATE projects SET name = ?, description = ?, status = ?, is_featured = ? WHERE id = ?
                    ");
                    $stmt->execute([$name, $description, $status, $is_featured, $id]);
                    $message = 'Project updated successfully!';
                } else {
                    $stmt = $db->prepare("
                        INSERT INTO projects (name, description, status, is_featured) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $description, $status, $is_featured]);
                    $message = 'Project created successfully!';
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
        $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Project deleted successfully!';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = 'Error deleting project.';
        $message_type = 'error';
    }
}

// Get project to edit
$edit_project = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $edit_project = $stmt->fetch();
}

// Get all projects
$stmt = $db->query("SELECT * FROM projects ORDER BY created_at DESC");
$projects = $stmt->fetchAll();

include '../includes/admin_header.php';
?>

<div class="admin-content">
    <div class="admin-container">
        <div class="admin-page-header">
            <h1 class="admin-page-title">Manage Projects</h1>
            <p class="admin-page-subtitle">Edit organization projects and features</p>
            <div class="admin-breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/index.php">Home</a> / Projects
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: var(--spacing-md);">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-section">
            <h2 class="admin-section-title"><?php echo $edit_project ? 'Edit Project' : 'Create New Project'; ?></h2>
            <form method="POST" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <?php if ($edit_project): ?>
                    <input type="hidden" name="project_id" value="<?php echo $edit_project['id']; ?>">
                <?php endif; ?>
                
                <div class="admin-form-group">
                    <label class="admin-form-label">Project Name *</label>
                    <input type="text" name="name" class="admin-form-input" 
                           value="<?php echo $edit_project ? htmlspecialchars($edit_project['name']) : ''; ?>" required>
                </div>
                
                <div class="admin-form-group">
                    <label class="admin-form-label">Description *</label>
                    <textarea name="description" class="admin-form-textarea" required><?php echo $edit_project ? htmlspecialchars($edit_project['description']) : ''; ?></textarea>
                </div>
                
                <div class="admin-form-group">
                    <label class="admin-form-label">Status *</label>
                    <select name="status" class="admin-form-select" required>
                        <option value="active" <?php echo ($edit_project && $edit_project['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="completed" <?php echo ($edit_project && $edit_project['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="archived" <?php echo ($edit_project && $edit_project['status'] === 'archived') ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>
                
                <div class="admin-form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="is_featured" 
                               <?php echo ($edit_project && $edit_project['is_featured']) ? 'checked' : ''; ?>>
                        <span>Feature on Homepage</span>
                    </label>
                </div>
                
                <button type="submit" class="admin-btn admin-btn-primary">
                    <?php echo $edit_project ? 'Update Project' : 'Create Project'; ?>
                </button>
                <?php if ($edit_project): ?>
                    <a href="projects.php" class="admin-btn admin-btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="admin-section">
            <h2 class="admin-section-title">All Projects</h2>
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Featured</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($projects)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: var(--spacing-lg); color: var(--medium-gray);">
                                    No projects found. Create your first project above.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($projects as $project): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($project['name']); ?></td>
                                <td>
                                    <span class="badge badge-info"><?php echo ucfirst($project['status']); ?></span>
                                </td>
                                <td>
                                    <?php if ($project['is_featured']): ?>
                                        <span class="badge badge-success">Yes</span>
                                    <?php else: ?>
                                        <span class="badge">No</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($project['created_at'])); ?></td>
                                <td>
                                    <a href="?edit=<?php echo $project['id']; ?>" class="admin-btn admin-btn-secondary admin-btn-sm">Edit</a>
                                    <a href="?delete=<?php echo $project['id']; ?>" 
                                       class="admin-btn admin-btn-danger admin-btn-sm"
                                       onclick="return confirm('Are you sure you want to delete this project?');">Delete</a>
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
