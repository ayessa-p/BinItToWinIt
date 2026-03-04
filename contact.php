<?php
require_once 'config/config.php';

$page_title = 'Contact Us';
$message = '';
$message_type = '';

$db = Database::getInstance()->getConnection();
$user_id = is_logged_in() ? get_user_id() : null;

// Get thread ID from URL for detailed view
$selected_thread_id = isset($_GET['thread_id']) ? (int)$_GET['thread_id'] : 0;

// Handle new message submission (creates or appends to a thread)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token. Please try again.';
        $message_type = 'error';
    } else {
        $name = sanitize_input($_POST['name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $subject = sanitize_input($_POST['subject'] ?? '');
        $message_text = sanitize_input($_POST['message'] ?? '');
        
        if (empty($name) || empty($email) || empty($subject) || empty($message_text)) {
            $message = 'Please fill in all required fields.';
            $message_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $message_type = 'error';
        } else {
            try {
                $db->beginTransaction();

                // Find existing open thread for same user/subject, otherwise create new
                $thread_id = null;
                $stmt = $db->prepare("SELECT id FROM contact_threads WHERE user_id <=> ? AND subject = ? AND status IN ('open','answered') ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$user_id, $subject]);
                $existing = $stmt->fetchColumn();
                if ($existing) {
                    $thread_id = (int)$existing;
                } else {
                    $stmt = $db->prepare("INSERT INTO contact_threads (user_id, name, email, subject, status) VALUES (?, ?, ?, ?, 'open')");
                    $stmt->execute([$user_id, $name, $email, $subject]);
                    $thread_id = (int)$db->lastInsertId();
                }

                $sender_type = $user_id ? 'user' : 'guest';
                $stmt = $db->prepare("INSERT INTO contact_messages (thread_id, sender_type, user_id, message_text) VALUES (?, ?, ?, ?)");
                $stmt->execute([$thread_id, $sender_type, $user_id, $message_text]);

                $db->commit();
                $message = 'Thank you for contacting us! Your message has been sent.';
                $message_type = 'success';
                
                // Clear form
                $name = $email = $subject = $message_text = '';
            } catch (PDOException $e) {
                $db->rollBack();
                $message = 'An error occurred while sending your message. Please try again.';
                $message_type = 'error';
            }
        }
    }
}

// Load user's threads
$user_threads = [];
if ($user_id) {
    $stmt = $db->prepare("
        SELECT t.*, 
               (SELECT message_text FROM contact_messages WHERE thread_id = t.id ORDER BY created_at DESC LIMIT 1) AS last_message,
               (SELECT sender_type FROM contact_messages WHERE thread_id = t.id ORDER BY created_at DESC LIMIT 1) AS last_sender
        FROM contact_threads t
        WHERE t.user_id = ?
        ORDER BY t.updated_at DESC
    ");
    $stmt->execute([$user_id]);
    $user_threads = $stmt->fetchAll();
}

// Load messages per thread
$thread_messages = [];
if (!empty($user_threads)) {
    $ids = array_column($user_threads, 'id');
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

include 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">Contact Us</h1>
        
        <?php if ($selected_thread_id > 0 && $selected_thread): ?>
            <!-- Detailed Thread View -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><?php echo htmlspecialchars($selected_thread['subject']); ?></h2>
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
                            <input type="hidden" name="submit_contact" value="1">
                            <input type="hidden" name="name" value="<?php echo htmlspecialchars($selected_thread['name']); ?>">
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($selected_thread['email']); ?>">
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
                        <a href="contact.php" class="btn btn-secondary btn-sm">← Back to Messages</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Inbox View -->
            <div class="grid grid-2" style="margin-top: 3rem; gap: 3rem;">
                <div>
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Get in Touch</h2>
                        </div>
                        <div class="card-body">
                            <div style="margin-bottom: 2rem;">
                                <h3 style="color: var(--light-blue); margin-bottom: 1rem;">Contact Information</h3>
                                <p style="color: var(--medium-gray); margin-bottom: 0.5rem;">
                                    <strong style="color: var(--white);">Email:</strong><br>
                                    mticsofficial@gmail.com
                                </p>
                                <p style="color: var(--medium-gray); margin-bottom: 0.5rem;">
                                    <strong style="color: var(--white);">Phone:</strong><br>
                                    +63 123 456 7890
                                </p>
                                <p style="color: var(--medium-gray); margin-bottom: 0.5rem;">
                                    <strong style="color: var(--white);">Address:</strong><br>
                                    Manila Technician Institute<br>
                                    Computer Society
                                </p>
                            </div>
                            
                            <div>
                                <h3 style="color: var(--light-blue); margin-bottom: 1rem;">Office Hours</h3>
                                <p style="color: var(--medium-gray);">
                                    Monday - Friday: 9:00 AM - 5:00 PM<br>
                                    Saturday: 9:00 AM - 12:00 PM<br>
                                    Sunday: Closed
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">Send Us a Message</h2>
                            </div>
                            <div class="card-body">
                                <?php if ($message): ?>
                                    <div class="alert alert-<?php echo $message_type; ?>">
                                        <?php echo htmlspecialchars($message); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST" action="" data-validate>
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    
                                    <div class="form-group">
                                        <label for="name" class="form-label">Full Name *</label>
                                        <input type="text" id="name" name="name" class="form-input" 
                                               value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" id="email" name="email" class="form-input" 
                                               value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="subject" class="form-label">Subject *</label>
                                        <input type="text" id="subject" name="subject" class="form-input" 
                                               value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="message" class="form-label">Message *</label>
                                        <textarea id="message" name="message" class="form-textarea" required><?php echo isset($message_text) ? htmlspecialchars($message_text) : ''; ?></textarea>
                                    </div>
                                    
                                    <button type="submit" name="submit_contact" class="btn btn-primary btn-sm" style="width: 100%;">
                                        Send Message
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <?php if ($user_id): ?>
                            <div class="card" style="margin-top: 2rem;">
                                <div class="card-header">
                                    <h2 class="card-title">Your Message Inbox</h2>
                                    <div style="margin-bottom: 1rem; display: flex; gap: 1rem; align-items: center;">
                                        <div style="color: var(--medium-gray);">
                                            <strong>Total:</strong> <?php echo count($user_threads); ?> messages
                                        </div>
                                        <div style="color: var(--medium-gray);">
                                            <strong>Open:</strong> <?php echo count(array_filter($user_threads, fn($t) => $t['status'] === 'open')); ?> threads
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($user_threads)): ?>
                                        <div style="text-align: center; padding: 3rem;">
                                            <i class="fa-solid fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: var(--medium-gray);"></i>
                                            <p style="color: var(--medium-gray); font-size: 1.1rem;">
                                                You haven't sent any messages yet.
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
                                                    <?php foreach ($user_threads as $t): 
                                                        $message_count = count($thread_messages[$t['id']] ?? []);
                                                        $unread_class = $t['status'] === 'open' ? 'style="font-weight: bold; background: #f0f9ff;"' : '';
                                                    ?>
                                                        <tr <?php echo $unread_class; ?> style="cursor: pointer;" 
                                                            onmouseover="this.style.backgroundColor='#e5e7eb'" 
                                                            onmouseout="this.style.backgroundColor='transparent'"
                                                            onclick="window.location.href='contact.php?thread_id=<?php echo (int)$t['id']; ?>'">
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
                                                                <a href="contact.php?thread_id=<?php echo (int)$t['id']; ?>" 
                                                                   class="btn btn-xs btn-primary">
                                                                    <i class="fa-solid fa-envelope-open"></i> Open
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
