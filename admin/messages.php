<?php
require_once '../config/config.php';
require_admin();

$page_title = 'Messages';
$db = Database::getInstance()->getConnection();
$message = '';
$message_type = '';

// Get the thread ID from URL for detailed view
$selected_thread_id = isset($_GET['thread_id']) ? (int)$_GET['thread_id'] : 0;

// Reply to a thread
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_thread'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
    } else {
        $thread_id = (int)($_POST['thread_id'] ?? 0);
        $reply_text = trim($_POST['reply_text'] ?? '');
        if ($thread_id <= 0 || $reply_text === '') {
            $message = 'Reply message cannot be empty.';
            $message_type = 'error';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO contact_messages (thread_id, sender_type, user_id, message_text) VALUES (?, 'admin', ?, ?)");
                $stmt->execute([$thread_id, get_user_id(), $reply_text]);

                $stmt = $db->prepare("UPDATE contact_threads SET status = 'answered', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$thread_id]);

                $message = 'Reply sent successfully.';
                $message_type = 'success';
                
                // Redirect to the same thread to show the new message
                header("Location: messages.php?thread_id=$thread_id");
                exit;
            } catch (PDOException $e) {
                $message = 'Error sending reply.';
                $message_type = 'error';
            }
        }
    }
}

// Change thread status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $thread_id = (int)($_POST['thread_id'] ?? 0);
        $status = $_POST['status'] ?? 'open';
        if (in_array($status, ['open','answered','closed'], true) && $thread_id > 0) {
            $stmt = $db->prepare("UPDATE contact_threads SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $thread_id]);
            
            $message = 'Thread status updated.';
            $message_type = 'success';
        }
    }
}

// Load threads
$threads = $db->query("SELECT * FROM contact_threads ORDER BY status ASC, updated_at DESC")->fetchAll();

// Load messages per thread
$thread_messages = [];
if (!empty($threads)) {
    $ids = array_column($threads, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("SELECT * FROM contact_messages WHERE thread_id IN ($in) ORDER BY created_at ASC");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $msg) {
        $thread_messages[$msg['thread_id']][] = $msg;
    }
}

// Get selected thread details
$selected_thread = null;
$selected_messages = [];
if ($selected_thread_id > 0) {
    $stmt = $db->prepare("SELECT * FROM contact_threads WHERE id = ?");
    $stmt->execute([$selected_thread_id]);
    $selected_thread = $stmt->fetch();
    
    if ($selected_thread) {
        $selected_messages = $thread_messages[$selected_thread_id] ?? [];
        
        // Mark thread as read (update status if it was open)
        if ($selected_thread['status'] === 'open') {
            $stmt = $db->prepare("UPDATE contact_threads SET status = 'answered' WHERE id = ?");
            $stmt->execute([$selected_thread_id]);
        }
    }
}

include '../includes/admin_header.php';
?>

<div class="admin-content">
    <div class="admin-container">
        <div class="admin-page-header">
            <h1 class="admin-page-title">Messages</h1>
            <p class="admin-page-subtitle">View and reply to messages sent from the Contact page</p>
            <div class="admin-breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/index.php">Home</a> / Messages
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: var(--spacing-md);">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($selected_thread_id > 0 && $selected_thread): ?>
            <!-- Detailed Thread View -->
            <div class="admin-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <div>
                        <a href="messages.php" class="admin-btn admin-btn-secondary">
                            ← Back to Inbox
                        </a>
                    </div>
                    <div>
                        <form method="POST" style="display: inline-flex; gap: 0.5rem; align-items: center;">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="thread_id" value="<?php echo (int)$selected_thread['id']; ?>">
                            <input type="hidden" name="update_status" value="1">
                            <label style="font-size: 0.9rem; color: var(--medium-gray);">Status:</label>
                            <select name="status" class="admin-form-select" style="padding: 0.25rem 0.5rem; font-size: 0.9rem;">
                                <option value="open" <?php echo $selected_thread['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                <option value="answered" <?php echo $selected_thread['status'] === 'answered' ? 'selected' : ''; ?>>Answered</option>
                                <option value="closed" <?php echo $selected_thread['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                            </select>
                            <button type="submit" class="admin-btn admin-btn-secondary admin-btn-sm">Update</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><?php echo htmlspecialchars($selected_thread['subject']); ?></h2>
                        <div style="margin-top: 0.5rem; color: var(--medium-gray);">
                            From: <strong><?php echo htmlspecialchars($selected_thread['name']); ?></strong> 
                            &lt;<?php echo htmlspecialchars($selected_thread['email']); ?>&gt;
                            <span style="margin-left: 1rem;">Created: <?php echo date('M j, Y g:i A', strtotime($selected_thread['created_at'])); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Messages Thread -->
                        <div style="max-height: 500px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; background: #fafafa;">
                            <?php if (empty($selected_messages)): ?>
                                <p style="color: var(--medium-gray); text-align: center; padding: 2rem;">No messages in this thread yet.</p>
                            <?php else: ?>
                                <?php foreach ($selected_messages as $msg): ?>
                                    <div style="margin-bottom: 1.5rem; <?php echo $msg['sender_type'] === 'admin' ? 'margin-left: 2rem;' : ''; ?>">
                                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                            <div style="font-weight: bold; color: <?php echo $msg['sender_type'] === 'admin' ? '#2563eb' : '#059669'; ?>;">
                                                <?php echo $msg['sender_type'] === 'admin' ? 'Admin' : htmlspecialchars($selected_thread['name']); ?>
                                            </div>
                                            <div style="font-size: 0.8rem; color: var(--medium-gray);">
                                                <?php echo date('M j, Y g:i A', strtotime($msg['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div style="
                                            background: <?php echo $msg['sender_type'] === 'admin' ? '#dbeafe' : '#f0fdf4'; ?>; 
                                            border-left: 4px solid <?php echo $msg['sender_type'] === 'admin' ? '#2563eb' : '#059669'; ?>; 
                                            border-radius: 8px; 
                                            padding: 1rem; 
                                            margin-left: <?php echo $msg['sender_type'] === 'admin' ? '1rem' : '0'; ?>;
                                        ">
                                            <?php echo nl2br(htmlspecialchars($msg['message_text'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Reply Form -->
                        <?php if ($selected_thread['status'] !== 'closed'): ?>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="thread_id" value="<?php echo (int)$selected_thread['id']; ?>">
                                <input type="hidden" name="reply_thread" value="1">
                                <div class="admin-form-group">
                                    <label class="admin-form-label">Your Reply</label>
                                    <textarea name="reply_text" class="admin-form-textarea" rows="4" required 
                                              placeholder="Type your reply..."></textarea>
                                </div>
                                <button type="submit" class="admin-btn admin-btn-primary">
                                    <i class="fa-solid fa-paper-plane"></i> Send Reply
                                </button>
                            </form>
                        <?php else: ?>
                            <div style="text-align: center; padding: 2rem; color: var(--medium-gray);">
                                <i class="fa-solid fa-lock" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                <p>This thread is closed. No further replies can be sent.</p>
                                <form method="POST" style="display: inline; margin-top: 1rem;">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="thread_id" value="<?php echo (int)$selected_thread['id']; ?>">
                                    <input type="hidden" name="update_status" value="1">
                                    <input type="hidden" name="status" value="open">
                                    <button type="submit" class="admin-btn admin-btn-secondary">Reopen Thread</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Inbox View -->
            <div class="admin-section">
                <h2 class="admin-section-title">Inbox</h2>
                
                <div style="margin-bottom: 1rem; display: flex; gap: 1rem; align-items: center;">
                    <div style="color: var(--medium-gray);">
                        <?php 
                        $total_threads = count($threads);
                        $open_threads = count(array_filter($threads, fn($t) => $t['status'] === 'open'));
                        echo "Total: $total_threads messages | ";
                        echo "Open: $open_threads threads";
                        ?>
                    </div>
                </div>
                
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>From</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($threads)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: var(--spacing-lg); color: var(--medium-gray);">
                                        <i class="fa-solid fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                        No messages in your inbox
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($threads as $t): 
                                    $message_count = count($thread_messages[$t['id']] ?? []);
                                    $unread_class = $t['status'] === 'open' ? 'style="font-weight: bold; background: #f0f9ff;"' : '';
                                ?>
                                    <tr <?php echo $unread_class; ?>>
                                        <td>
                                            <a href="messages.php?thread_id=<?php echo (int)$t['id']; ?>" 
                                               style="color: inherit; text-decoration: none; display: block; padding: 0.5rem 0;"
                                               onmouseover="this.style.backgroundColor='#e5e7eb'" 
                                               onmouseout="this.style.backgroundColor='transparent'">
                                                <div style="font-weight: <?php echo $t['status'] === 'open' ? 'bold' : 'normal'; ?>;">
                                                    <?php echo htmlspecialchars($t['subject']); ?>
                                                </div>
                                                <div style="font-size: 0.8rem; color: var(--medium-gray);">
                                                    <?php echo $message_count; ?> message<?php echo $message_count !== 1 ? 's' : ''; ?>
                                                </div>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="messages.php?thread_id=<?php echo (int)$t['id']; ?>" 
                                               style="color: inherit; text-decoration: none;">
                                                <?php echo htmlspecialchars($t['name']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="messages.php?thread_id=<?php echo (int)$t['id']; ?>" 
                                               style="color: inherit; text-decoration: none;">
                                                <?php echo htmlspecialchars($t['email']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo match($t['status']) {
                                                    'open' => 'warning',
                                                    'answered' => 'info',
                                                    'closed' => 'secondary',
                                                    default => 'secondary'
                                                }; 
                                            ?>">
                                                <?php echo ucfirst($t['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="messages.php?thread_id=<?php echo (int)$t['id']; ?>" 
                                               style="color: inherit; text-decoration: none;">
                                                <?php 
                                                $time = strtotime($t['updated_at']);
                                                if (time() - $time < 86400) { // Less than 24 hours
                                                    echo date('g:i A', $time);
                                                } else {
                                                    echo date('M j, Y', $time);
                                                }
                                                ?>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="messages.php?thread_id=<?php echo (int)$t['id']; ?>" 
                                               class="admin-btn admin-btn-primary admin-btn-sm">
                                                <i class="fa-solid fa-envelope-open"></i> Open
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>

