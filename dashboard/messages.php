<?php
require_once '../config/config.php';
require_login();

$page_title = 'Messages';
$user_id = get_user_id();
$message = '';
$message_type = '';

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token. Please try again.';
        $message_type = 'error';
    } else {
        $subject = sanitize_input($_POST['subject'] ?? '');
        $message_text = sanitize_input($_POST['message'] ?? '');
        
        if (empty($subject) || empty($message_text)) {
            $message = 'Please fill in all required fields.';
            $message_type = 'error';
        } else {
            try {
                $db = Database::getInstance()->getConnection();
                $db->beginTransaction();

                // Find existing open thread for same user/subject, otherwise create new
                $thread_id = null;
                $stmt = $db->prepare("SELECT id FROM contact_threads WHERE user_id = ? AND subject = ? AND status IN ('open','answered') ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$user_id, $subject]);
                $existing = $stmt->fetchColumn();
                if ($existing) {
                    $thread_id = (int)$existing;
                } else {
                    // Get user info for new thread
                    $stmt = $db->prepare("SELECT full_name, email FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user_info = $stmt->fetch();
                    
                    $stmt = $db->prepare("INSERT INTO contact_threads (user_id, name, email, subject, status) VALUES (?, ?, ?, ?, 'open')");
                    $stmt->execute([$user_id, $user_info['full_name'], $user_info['email'], $subject]);
                    $thread_id = (int)$db->lastInsertId();
                }

                $stmt = $db->prepare("INSERT INTO contact_messages (thread_id, sender_type, user_id, message_text) VALUES (?, 'user', ?, ?)");
                $stmt->execute([$thread_id, $user_id, $message_text]);

                $stmt = $db->prepare("UPDATE contact_threads SET status = 'open', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$thread_id]);

                $db->commit();
                $message = 'Message sent successfully! We will respond as soon as possible.';
                $message_type = 'success';
                
            } catch (PDOException $e) {
                $db->rollBack();
                $message = 'Error sending message. Please try again.';
                $message_type = 'error';
            }
        }
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get thread ID from URL for detailed view
$selected_thread_id = isset($_GET['thread_id']) ? (int)$_GET['thread_id'] : 0;

// Load user's threads
$db = Database::getInstance()->getConnection();

// Get total count for pagination
$stmt = $db->prepare("SELECT COUNT(*) FROM contact_threads WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_threads = $stmt->fetchColumn();
$total_pages = ceil($total_threads / $per_page);

// Get threads with pagination
$stmt = $db->prepare("SELECT * FROM contact_threads WHERE user_id = ? ORDER BY status ASC, updated_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$user_id, $per_page, $offset]);
$threads = $stmt->fetchAll();

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
    $stmt = $db->prepare("SELECT * FROM contact_threads WHERE id = ? AND user_id = ?");
    $stmt->execute([$selected_thread_id, $user_id]);
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

include '../includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">Messages</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: var(--spacing-md);">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($selected_thread_id > 0 && $selected_thread): ?>
            <!-- Detailed Thread View -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><?php echo htmlspecialchars($selected_thread['subject']); ?></div>
                    <div style="margin-top: 0.5rem; color: var(--medium-gray);">
                        Thread ID: #<?php echo $selected_thread['id']; ?> | 
                        Started: <?php echo date('F j, Y g:i A', strtotime($selected_thread['created_at'])); ?>
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
                                            <?php echo $msg['sender_type'] === 'admin' ? 'Admin' : 'You'; ?>
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
                            <input type="hidden" name="send_message" value="1">
                            <input type="hidden" name="subject" value="<?php echo htmlspecialchars($selected_thread['subject']); ?>">
                            <div class="form-group">
                                <label class="form-label">Your Reply</label>
                                <textarea name="message" class="form-textarea" rows="4" required 
                                          placeholder="Type your reply..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-paper-plane"></i> Send Reply
                            </button>
                        </form>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: var(--medium-gray);">
                            <i class="fa-solid fa-lock" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p>This thread is closed. No further replies can be sent.</p>
                        </div>
                    <?php endif; ?>
                    
                    <div style="text-align: center; margin-top: 2rem;">
                        <a href="messages.php" class="btn btn-secondary btn-sm">← Back to Inbox</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Inbox View -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Message Inbox</h2>
                    <div style="margin-bottom: 1rem; display: flex; gap: 1rem; align-items: center;">
                        <div style="color: var(--medium-gray);">
                            <strong>Total:</strong> <?php echo count($threads); ?> messages
                        </div>
                        <div style="color: var(--medium-gray);">
                            <strong>Open:</strong> <?php echo count(array_filter($threads, fn($t) => $t['status'] === 'open')); ?> threads
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($threads)): ?>
                        <div style="text-align: center; padding: 3rem;">
                            <i class="fa-solid fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: var(--medium-gray);"></i>
                            <p style="color: var(--medium-gray); font-size: 1.1rem;">
                                No messages yet. Start a conversation below!
                            </p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 2px solid var(--azure-blue);">
                                        <th style="padding: 1rem; text-align: left; color: var(--light-blue);">Subject</th>
                                        <th style="padding: 1rem; text-align: left; color: var(--light-blue);">Status</th>
                                        <th style="padding: 1rem; text-align: left; color: var(--light-blue);">Last Updated</th>
                                        <th style="padding: 1rem; text-align: center; color: var(--light-blue);">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($threads as $t): 
                                        $message_count = count($thread_messages[$t['id']] ?? []);
                                        $unread_class = $t['status'] === 'open' ? 'style="font-weight: bold; background: #f0f9ff;"' : '';
                                    ?>
                                        <tr <?php echo $unread_class; ?> style="cursor: pointer;" 
                                            onmouseover="this.style.backgroundColor='#e5e7eb'" 
                                            onmouseout="this.style.backgroundColor='transparent'"
                                            onclick="window.location.href='messages.php?thread_id=<?php echo (int)$t['id']; ?>'">
                                            <td style="padding: 1rem;">
                                                <div style="font-weight: <?php echo $t['status'] === 'open' ? 'bold' : 'normal'; ?>;">
                                                    <?php echo htmlspecialchars($t['subject']); ?>
                                                </div>
                                                <div style="font-size: 0.8rem; color: var(--medium-gray);">
                                                    <?php echo $message_count; ?> message<?php echo $message_count !== 1 ? 's' : ''; ?>
                                                </div>
                                            </td>
                                            <td style="padding: 1rem;">
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
                                            <td style="padding: 1rem; color: var(--medium-gray);">
                                                <?php 
                                                $time = strtotime($t['updated_at']);
                                                if (time() - $time < 86400) { // Less than 24 hours
                                                    echo date('g:i A', $time);
                                                } else {
                                                    echo date('M j, Y', $time);
                                                }
                                                ?>
                                            </td>
                                            <td style="padding: 1rem; text-align: center;">
                                                <a href="messages.php?thread_id=<?php echo (int)$t['id']; ?>" 
                                                   class="btn btn-xs btn-primary">
                                                    <i class="fa-solid fa-envelope-open"></i> Open
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div style="display: flex; justify-content: center; align-items: center; gap: 1rem; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--azure-blue);">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary">Previous</a>
                                <?php endif; ?>
                                
                                <span style="color: var(--medium-gray);">
                                    Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                                </span>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary">Next</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 2rem;">
                <a href="index.php" class="btn btn-primary btn-large">← Back to Dashboard</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
