<?php
require_once '../config/config.php';
require_admin();

$page_title = 'Manage Event Attendance';
$message = '';
$message_type = '';

$db = Database::getInstance()->getConnection();

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if ($event_id <= 0) {
    header('Location: events.php');
    exit;
}

// Get event details
$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    header('Location: events.php');
    exit;
}

// Handle attendance approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token.';
        $message_type = 'error';
    } else {
        $attendance_id = isset($_POST['attendance_id']) ? (int)$_POST['attendance_id'] : 0;
        $action = $_POST['action'];
        $admin_notes = sanitize_input($_POST['admin_notes'] ?? '');
        
        if ($attendance_id > 0 && in_array($action, ['approve', 'reject'])) {
            try {
                $db->beginTransaction();
                
                // Get attendance record
                $stmt = $db->prepare("SELECT * FROM event_attendance WHERE id = ? AND event_id = ?");
                $stmt->execute([$attendance_id, $event_id]);
                $attendance = $stmt->fetch();
                
                if ($attendance) {
                    $new_status = $action === 'approve' ? 'approved' : 'rejected';
                    $tokens_awarded = 0;
                    
                    if ($action === 'approve' && $attendance['attendance_status'] !== 'approved') {
                        $tokens_awarded = 10.00; // Award 10 tokens for approved attendance
                        
                        // Update user's token balance
                        $stmt = $db->prepare("UPDATE users SET eco_tokens = eco_tokens + ? WHERE id = ?");
                        $stmt->execute([$tokens_awarded, $attendance['user_id']]);
                        
                        // Create transaction record
                        $stmt = $db->prepare("
                            INSERT INTO transactions (user_id, transaction_type, amount, description) 
                            VALUES (?, 'earned', ?, ?)
                        ");
                        $stmt->execute([
                            $attendance['user_id'], 
                            $tokens_awarded, 
                            "Event attendance reward: " . htmlspecialchars($event['title'])
                        ]);
                    }
                    
                    // Update attendance record
                    $stmt = $db->prepare("
                        UPDATE event_attendance 
                        SET attendance_status = ?, tokens_awarded = ?, admin_notes = ?, 
                            reviewed_at = NOW(), reviewed_by = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$new_status, $tokens_awarded, $admin_notes, $_SESSION['user_id'], $attendance_id]);
                    
                    // Update event participant count
                    $stmt = $db->prepare("
                        UPDATE events SET participant_count = (
                            SELECT COUNT(*) FROM event_attendance 
                            WHERE event_id = ? AND attendance_status = 'approved'
                        ) WHERE id = ?
                    ");
                    $stmt->execute([$event_id, $event_id]);
                    
                    $message = "Attendance " . $new_status . " successfully!";
                    $message_type = 'success';
                }
                
                $db->commit();
            } catch (PDOException $e) {
                $db->rollBack();
                $message = 'Database error: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// Get all attendance submissions for this event
$stmt = $db->prepare("
    SELECT ea.*, u.full_name, u.student_id, u.email, u.eco_tokens
    FROM event_attendance ea
    JOIN users u ON ea.user_id = u.id
    WHERE ea.event_id = ?
    ORDER BY ea.submitted_at DESC
");
$stmt->execute([$event_id]);
$attendances = $stmt->fetchAll();

include '../includes/admin_header.php';
?>

<div class="admin-content">
    <div class="admin-container">
        <div class="admin-page-header">
            <h1 class="admin-page-title">Manage Attendance</h1>
            <p class="admin-page-subtitle"><?php echo htmlspecialchars($event['title']); ?></p>
            <div class="admin-breadcrumb">
                <a href="<?php echo SITE_URL; ?>/admin/index.php">Home</a> / 
                <a href="events.php">Events</a> / 
                Manage Attendance
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" style="margin-bottom: var(--spacing-md);">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-section">
            <div class="attendance-summary" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: var(--light-gray); padding: 1rem; border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: var(--primary-blue);">
                        <?php echo count($attendances); ?>
                    </div>
                    <div style="color: var(--medium-gray);">Total Submissions</div>
                </div>
                <div style="background: #d4edda; padding: 1rem; border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: #155724;">
                        <?php echo count(array_filter($attendances, fn($a) => $a['attendance_status'] === 'approved')); ?>
                    </div>
                    <div style="color: #155724;">Approved</div>
                </div>
                <div style="background: #fff3cd; padding: 1rem; border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: #856404;">
                        <?php echo count(array_filter($attendances, fn($a) => $a['attendance_status'] === 'pending')); ?>
                    </div>
                    <div style="color: #856404;">Pending Review</div>
                </div>
                <div style="background: #f8d7da; padding: 1rem; border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: #721c24;">
                        <?php echo count(array_filter($attendances, fn($a) => $a['attendance_status'] === 'rejected')); ?>
                    </div>
                    <div style="color: #721c24;">Rejected</div>
                </div>
            </div>
            
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Submission Date</th>
                            <th>Proof Image</th>
                            <th>Status</th>
                            <th>Tokens Awarded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendances)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: var(--spacing-lg); color: var(--medium-gray);">
                                    No attendance submissions yet for this event.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attendances as $attendance): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: bold;"><?php echo htmlspecialchars($attendance['full_name']); ?></div>
                                    <div style="font-size: 0.9rem; color: var(--medium-gray);">
                                        <?php echo htmlspecialchars($attendance['student_id']); ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--medium-gray);">
                                        <?php echo htmlspecialchars($attendance['email']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo date('M j, Y g:i A', strtotime($attendance['submitted_at'])); ?>
                                </td>
                                <td>
                                    <?php if (!empty($attendance['proof_image'])): ?>
                                        <a href="<?php echo SITE_URL . '/' . htmlspecialchars($attendance['proof_image']); ?>" 
                                           target="_blank" 
                                           style="display: inline-block;">
                                            <img src="<?php echo SITE_URL . '/' . htmlspecialchars($attendance['proof_image']); ?>" 
                                                 alt="Proof of attendance" 
                                                 style="width: 80px; height: 60px; object-fit: cover; border-radius: var(--radius-sm); cursor: pointer;"
                                                 onclick="window.open(this.src, '_blank')">
                                        </a>
                                    <?php else: ?>
                                        <span style="color: var(--medium-gray);">No image</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo match($attendance['attendance_status']) {
                                            'approved' => 'success',
                                            'pending' => 'warning',
                                            'rejected' => 'danger',
                                            default => 'secondary'
                                        }; 
                                    ?>">
                                        <?php echo ucfirst($attendance['attendance_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="font-weight: bold; color: var(--primary-blue);">
                                        <?php echo number_format($attendance['tokens_awarded'], 2); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($attendance['attendance_status'] === 'pending'): ?>
                                        <div style="display: flex; gap: 0.5rem; flex-direction: column;">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                <input type="hidden" name="attendance_id" value="<?php echo $attendance['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="admin_notes" value="Approved by admin">
                                                <button type="submit" class="admin-btn admin-btn-success admin-btn-sm"
                                                        onclick="return confirm('Approve this attendance and award 10 tokens?')">
                                                    ✓ Approve
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                <input type="hidden" name="attendance_id" value="<?php echo $attendance['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="admin_notes" value="Rejected by admin">
                                                <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm"
                                                        onclick="return confirm('Reject this attendance submission?')">
                                                    ✗ Reject
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: var(--medium-gray); font-size: 0.9rem;">
                                            <?php if ($attendance['reviewed_at']): ?>
                                                Reviewed <?php echo date('M j, Y', strtotime($attendance['reviewed_at'])); ?>
                                            <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 2rem; text-align: center;">
                <a href="events.php" class="admin-btn admin-btn-secondary">Back to Events</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>
