<?php
require_once 'config/config.php';

$page_title = 'Contact Us';
$message = '';
$message_type = '';

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
            // In a real application, you would send an email or save to database
            // For now, we'll just show a success message
            $message = 'Thank you for contacting us! We will get back to you soon.';
            $message_type = 'success';
            
            // Clear form
            $name = $email = $subject = $message_text = '';
        }
    }
}

include 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">Contact Us</h1>
        
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
                            
                            <button type="submit" name="submit_contact" class="btn btn-primary" style="width: 100%;">
                                Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
