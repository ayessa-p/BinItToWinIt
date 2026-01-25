<?php
require_once '../config/config.php';
require_admin();

$page_title = 'Manage News';
$message = '';
$message_type = '';

$db = Database::getInstance()->getConnection();

// Handle create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
    } else {
        $id = isset($_POST['news_id']) ? (int)$_POST['news_id'] : 0;
        $title = sanitize_input($_POST['title'] ?? '');
        $content = sanitize_input($_POST['content'] ?? '');
        $author = sanitize_input($_POST['author'] ?? 'MTICS Admin');
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        
        if (empty($title) || empty($content)) {
            $message = 'Please fill in all required fields.';
            $message_type = 'error';
        } else {
            try {
                if ($id > 0) {
                    $stmt = $db->prepare("
                        UPDATE news SET title = ?, content = ?, author = ?, is_published = ? WHERE id = ?
                    ");
                    $stmt->execute([$title, $content, $author, $is_published, $id]);
                    $message = 'News updated successfully!';
                } else {
                    $stmt = $db->prepare("
                        INSERT INTO news (title, content, author, is_published) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$title, $content, $author, $is_published]);
                    $message = 'News created successfully!';
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
        $stmt = $db->prepare("DELETE FROM news WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'News deleted successfully!';
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = 'Error deleting news.';
        $message_type = 'error';
    }
}

// Get news to edit
$edit_news = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->execute([$id]);
    $edit_news = $stmt->fetch();
}

// Get all news
$stmt = $db->query("SELECT * FROM news ORDER BY created_at DESC");
$all_news = $stmt->fetchAll();

include '../includes/admin_header.php';
?>

<div class="admin-content">
    <div class="admin-container">
        <div class="admin-page-header">
            <h1 class="admin-page-title">Manage News</h1>
            <p class="admin-page-subtitle">Create and publish news articles</p>
            <div class="admin-breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/index.php">Home</a> / News
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: var(--spacing-md);">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-section">
            <h2 class="admin-section-title"><?php echo $edit_news ? 'Edit News' : 'Create New News'; ?></h2>
            <form method="POST" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <?php if ($edit_news): ?>
                    <input type="hidden" name="news_id" value="<?php echo $edit_news['id']; ?>">
                <?php endif; ?>
                
                <div class="admin-form-group">
                    <label class="admin-form-label">Title *</label>
                    <input type="text" name="title" class="admin-form-input" 
                           value="<?php echo $edit_news ? htmlspecialchars($edit_news['title']) : ''; ?>" required>
                </div>
                
                <div class="admin-form-group">
                    <label class="admin-form-label">Author</label>
                    <input type="text" name="author" class="admin-form-input" 
                           value="<?php echo $edit_news ? htmlspecialchars($edit_news['author']) : 'MTICS Admin'; ?>">
                </div>
                
                <div class="admin-form-group">
                    <label class="admin-form-label">Content *</label>
                    <textarea name="content" class="admin-form-textarea" required style="min-height: 200px;"><?php echo $edit_news ? htmlspecialchars($edit_news['content']) : ''; ?></textarea>
                </div>
                
                <div class="admin-form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="is_published" 
                               <?php echo ($edit_news && $edit_news['is_published']) ? 'checked' : ''; ?>>
                        <span>Publish News</span>
                    </label>
                </div>
                
                <button type="submit" class="admin-btn admin-btn-primary">
                    <?php echo $edit_news ? 'Update News' : 'Create News'; ?>
                </button>
                <?php if ($edit_news): ?>
                    <a href="news.php" class="admin-btn admin-btn-secondary">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="admin-section">
            <h2 class="admin-section-title">All News Articles</h2>
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_news)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: var(--spacing-lg); color: var(--medium-gray);">
                                    No news articles found. Create your first article above.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_news as $news): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($news['title']); ?></td>
                                <td><?php echo htmlspecialchars($news['author']); ?></td>
                                <td>
                                    <span class="badge <?php echo $news['is_published'] ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo $news['is_published'] ? 'Published' : 'Draft'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($news['created_at'])); ?></td>
                                <td>
                                    <a href="?edit=<?php echo $news['id']; ?>" class="admin-btn admin-btn-secondary admin-btn-sm">Edit</a>
                                    <a href="?delete=<?php echo $news['id']; ?>" 
                                       class="admin-btn admin-btn-danger admin-btn-sm"
                                       onclick="return confirm('Are you sure you want to delete this news article?');">Delete</a>
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
