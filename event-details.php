<?php
require_once 'config/config.php';

$page_title = 'Event Details';

// Get event by ID
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($event_id <= 0) {
    header('Location: news.php');
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();

    if (!$event) {
        header('Location: news.php');
        exit;
    }
} catch (PDOException $e) {
    header('Location: news.php');
    exit;
}

// Get all events for gallery
$all_events = [];
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT * FROM events ORDER BY event_date DESC");
    $all_events = $stmt->fetchAll();
} catch (PDOException $e) {
    $all_events = [];
}

// Get attendance statistics
$attendance_stats = [];
$user_attendance = null;
try {
    $stmt = $db->prepare("
        SELECT
            COUNT(*) as total_attendees,
            COUNT(CASE WHEN attendance_status = 'approved' THEN 1 END) as approved_attendees
        FROM event_attendance
        WHERE event_id = ?
    ");
    $stmt->execute([$event_id]);
    $attendance_stats = $stmt->fetch();

    // Check if user has already submitted attendance
    if (isset($_SESSION['user_id'])) {
        $stmt = $db->prepare("
            SELECT attendance_status, submitted_at, proof_image
            FROM event_attendance
            WHERE event_id = ? AND user_id = ?
        ");
        $stmt->execute([$event_id, $_SESSION['user_id']]);
        $user_attendance = $stmt->fetch();
    }
} catch (PDOException $e) {
    $attendance_stats = ['total_attendees' => 0, 'approved_attendees' => 0];
}

include 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="card">
            <?php
                $primary_img = '';
                if (!empty($event['thumbnail_url'])) {
                    $primary_img = $event['thumbnail_url'];
                } elseif (!empty($event['gallery_json'])) {
                    $g = json_decode($event['gallery_json'], true);
                    if (is_array($g) && count($g) > 0) $primary_img = $g[0];
                } elseif (!empty($event['image_url'])) {
                    $primary_img = $event['image_url'];
                }
            ?>
            <?php if (!empty($primary_img)): ?>
                <img src="<?php echo SITE_URL . '/' . htmlspecialchars($primary_img); ?>"
                     alt="<?php echo htmlspecialchars($event['title']); ?>"
                     style="width: 100%; max-width: 900px; aspect-ratio: 16 / 9; object-fit: cover; border-radius: var(--radius-md); margin: 0 auto 1.5rem; display: block;">
            <?php endif; ?>

            <div class="card-body">
                <h1 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h1>

                <div class="event-meta" style="margin-bottom: 1.5rem;">
                    <span class="event-date">
                        <i class="fa-solid fa-calendar-days"></i>
                        <?php echo date('F j, Y \a\t g:i A', strtotime($event['event_date'])); ?>
                    </span>
                    <span class="event-location">
                        <i class="fa-solid fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($event['location']); ?>
                    </span>
                </div>

                <div class="event-description">
                    <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                </div>

                <!-- Attendance Statistics -->
                <div class="attendance-stats" style="background: var(--light-gray); padding: 1rem; border-radius: var(--radius-md); margin: 1.5rem 0;">
                    <h4 style="color: var(--primary-blue); margin-bottom: 0.5rem;">Attendance Information</h4>
                    <div style="display: flex; gap: 2rem; flex-wrap: wrap; align-items: center;">
                        <div>
                            <strong>Total Participants:</strong>
                            <span style="color: var(--primary-blue); font-size: 1.2rem;"><?php echo $attendance_stats['approved_attendees']; ?></span>
                        </div>
                        <div>
                            <strong>Pending Approval:</strong>
                            <span style="color: orange;"><?php echo $attendance_stats['total_attendees'] - $attendance_stats['approved_attendees']; ?></span>
                        </div>
                        <?php if (!empty($event['attendance_closed'])): ?>
                            <div>
                                <span style="background: #dc3545; color: #fff; padding: 3px 10px; border-radius: 4px; font-size: 0.85rem; font-weight: bold;">
                                    🔒 Attendance Closed
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Event Gallery -->
                <?php
                    $event_gallery = [];
                    if (!empty($event['gallery_json'])) {
                        $parsed = json_decode($event['gallery_json'], true);
                        if (is_array($parsed) && count($parsed) > 0) {
                            $event_gallery = $parsed;
                        }
                    }
                    if (empty($event_gallery) && !empty($primary_img)) {
                        $event_gallery = [$primary_img];
                    }
                ?>
                <?php if (!empty($event_gallery)): ?>
                    <div class="event-gallery" style="margin-top: 2rem;">
                        <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">Event Gallery</h3>
                        <div class="gallery-grid">
                            <?php foreach ($event_gallery as $gimg): ?>
                                <div class="gallery-item">
                                    <img src="<?php echo SITE_URL . '/' . htmlspecialchars($gimg); ?>"
                                         alt="Gallery image"
                                         style="border-radius: var(--radius-md);">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Attendance Section -->
                <?php if ($event['attendance_enabled'] && isset($_SESSION['user_id'])): ?>

                    <?php if (!empty($event['attendance_closed']) && !$user_attendance): ?>
                        <!-- Attendance is closed and user hasn't submitted yet -->
                        <div class="attendance-section" style="margin-top: 2rem; padding: 1.5rem; border: 2px solid #dc3545; border-radius: var(--radius-md); background: #fff5f5;">
                            <h3 style="color: #dc3545; margin-bottom: 0.75rem;">🔒 Attendance Closed</h3>
                            <p style="color: #666; margin: 0;">
                                The admin has closed attendance submissions for this event.
                                If you attended and didn't submit in time, please contact the event organizer.
                            </p>
                        </div>

                    <?php else: ?>
                        <!-- Attendance is open OR user already has a submission to show -->
                        <div class="attendance-section" style="margin-top: 2rem; padding: 1.5rem; border: 2px solid var(--primary-blue); border-radius: var(--radius-md);">
                            <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">
                                Event Attendance
                                <?php if (!empty($event['attendance_closed'])): ?>
                                    <span style="font-size: 0.8rem; font-weight: normal; color: #dc3545; margin-left: 0.5rem;">(Closed — showing your submission)</span>
                                <?php endif; ?>
                            </h3>

                            <?php if ($user_attendance): ?>
                                <div class="attendance-status" style="padding: 1rem; border-radius: var(--radius-md);">
                                    <?php if ($user_attendance['attendance_status'] === 'approved'): ?>
                                        <div style="color: green; font-weight: bold; margin-bottom: 0.5rem;">
                                            ✓ Your attendance has been approved! You earned 10 tokens.
                                        </div>
                                    <?php elseif ($user_attendance['attendance_status'] === 'pending'): ?>
                                        <div style="color: orange; font-weight: bold; margin-bottom: 0.5rem;">
                                            ⏳ Your attendance is pending approval.
                                        </div>
                                    <?php else: ?>
                                        <div style="color: red; font-weight: bold; margin-bottom: 0.5rem;">
                                            ❌ Your attendance was rejected.
                                        </div>
                                    <?php endif; ?>
                                    <div style="font-size: 0.9rem; color: #666;">
                                        Submitted: <?php echo date('F j, Y g:i A', strtotime($user_attendance['submitted_at'])); ?>
                                    </div>
                                    <?php if (!empty($user_attendance['proof_image'])): ?>
                                        <div style="margin-top: 1rem;">
                                            <strong>Your proof image:</strong><br>
                                            <img src="<?php echo SITE_URL . '/' . htmlspecialchars($user_attendance['proof_image']); ?>"
                                                 alt="Proof of attendance"
                                                 style="max-width: 300px; border-radius: var(--radius-md); margin-top: 0.5rem;">
                                        </div>
                                    <?php endif; ?>
                                </div>

                            <?php else: ?>
                                <!-- Attendance is open, no submission yet — show the form -->
                                <div class="attendance-form">
                                    <p style="margin-bottom: 1rem;">Record your attendance for this event and earn 10 tokens!</p>
                                    <form id="attendanceForm" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 1rem;">
                                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">

                                        <div>
                                            <label for="proof_image" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">
                                                Upload Proof Image *<br>
                                                <small style="font-weight: normal; color: #666;">Please upload a photo showing you attended this event</small>
                                            </label>
                                            <input type="file"
                                                   id="proof_image"
                                                   name="proof_image"
                                                   accept="image/*"
                                                   required
                                                   style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: var(--radius-md);">
                                        </div>

                                        <button type="submit" class="btn btn-primary" style="align-self: flex-start;">
                                            Submit Attendance
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php endif; ?>

                <?php elseif ($event['attendance_enabled'] && !isset($_SESSION['user_id'])): ?>
                    <div class="attendance-login-prompt" style="margin-top: 2rem; padding: 1rem; background: var(--light-gray); border-radius: var(--radius-md); text-align: center;">
                        <p>Please <a href="auth/login.php" style="color: var(--primary-blue); font-weight: bold;">log in</a> to record your attendance for this event.</p>
                    </div>
                <?php endif; ?>

                <div style="text-align: center; margin-top: 2rem;">
                    <a href="news.php" class="btn btn-secondary">Back to Events</a>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.getElementById('attendanceForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;

    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';

    try {
        const response = await fetch('api/event_attendance.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            const successDiv = document.createElement('div');
            successDiv.style.cssText = 'background: #d4edda; color: #155724; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;';
            successDiv.innerHTML = `✅ ${result.message}`;

            const formContainer = document.querySelector('.attendance-form');
            formContainer.parentNode.insertBefore(successDiv, formContainer);
            formContainer.style.display = 'none';

            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            const errorDiv = document.createElement('div');
            errorDiv.style.cssText = 'background: #f8d7da; color: #721c24; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;';
            errorDiv.innerHTML = `❌ ${result.message}`;

            const form = document.getElementById('attendanceForm');
            form.parentNode.insertBefore(errorDiv, form);
        }
    } catch (error) {
        console.error('Error:', error);
        const errorDiv = document.createElement('div');
        errorDiv.style.cssText = 'background: #f8d7da; color: #721c24; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem;';
        errorDiv.innerHTML = '❌ An error occurred while submitting your attendance. Please try again.';

        const form = document.getElementById('attendanceForm');
        form.parentNode.insertBefore(errorDiv, form);
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
});
</script>

<?php include 'includes/footer.php'; ?>