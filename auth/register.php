<?php
require_once '../config/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $student_id = sanitize_input($_POST['student_id'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $full_name = sanitize_input($_POST['full_name'] ?? '');
        $course = sanitize_input($_POST['course'] ?? '');
        $year_level = sanitize_input($_POST['year_level'] ?? '');
        
        // Validation
        if (empty($student_id) || empty($email) || empty($password) || empty($full_name)) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            try {
                $db = Database::getInstance()->getConnection();
                
                // Check if student ID or email already exists
                $check_stmt = $db->prepare("SELECT id FROM users WHERE student_id = ? OR email = ?");
                $check_stmt->execute([$student_id, $email]);
                if ($check_stmt->fetch()) {
                    $error = 'Student ID or email already registered.';
                } else {
                    // Create new user
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $insert_stmt = $db->prepare("
                        INSERT INTO users (student_id, email, password_hash, full_name, course, year_level, eco_tokens) 
                        VALUES (?, ?, ?, ?, ?, ?, 0)
                    ");
                    
                    if ($insert_stmt->execute([$student_id, $email, $password_hash, $full_name, $course, $year_level])) {
                        $success = 'Registration successful! You can now login.';
                        // Clear form
                        $student_id = $email = $full_name = $course = $year_level = '';
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                }
            } catch (PDOException $e) {
                $error = 'Database error. Please ensure the database tables are properly installed. <a href="' . SITE_URL . '/install_check.php" style="color: var(--light-blue);">Check installation</a>';
            }
        }
    }
}

$page_title = 'Register';
include '../includes/header.php';
?>

<section class="section">
    <div class="container">
        <div style="max-width: 600px; margin: 0 auto;">
            <div class="card">
                <div class="card-header" style="text-align: center;">
                    <h1 class="card-title">Create Your Account</h1>
                    <p style="color: var(--medium-gray);">Join Bin It to Win It and start earning Eco-Tokens!</p>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success); ?>
                            <br><br>
                            <a href="<?php echo SITE_URL; ?>/auth/login.php" class="btn btn-primary">Go to Login</a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="" data-validate>
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            
                            <div class="form-group">
                                <label for="student_id" class="form-label">Student ID *</label>
                                <input type="text" id="student_id" name="student_id" class="form-input" 
                                       value="<?php echo isset($student_id) ? htmlspecialchars($student_id) : ''; ?>" 
                                       placeholder="Enter your student ID" required autofocus>
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-input" 
                                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" 
                                       placeholder="Enter your email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="full_name" class="form-label">Full Name *</label>
                                <input type="text" id="full_name" name="full_name" class="form-input" 
                                       value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>" 
                                       placeholder="Enter your full name" required>
                            </div>
                            
                            <div class="grid grid-2">
                                <div class="form-group">
                                    <label for="course" class="form-label">Course</label>
                                    <input type="text" id="course" name="course" class="form-input" 
                                           value="<?php echo isset($course) ? htmlspecialchars($course) : ''; ?>" 
                                           placeholder="e.g., Computer Science">
                                </div>
                                
                                <div class="form-group">
                                    <label for="year_level" class="form-label">Year Level</label>
                                    <select id="year_level" name="year_level" class="form-select">
                                        <option value="">Select year level</option>
                                        <option value="1st Year" <?php echo (isset($year_level) && $year_level === '1st Year') ? 'selected' : ''; ?>>1st Year</option>
                                        <option value="2nd Year" <?php echo (isset($year_level) && $year_level === '2nd Year') ? 'selected' : ''; ?>>2nd Year</option>
                                        <option value="3rd Year" <?php echo (isset($year_level) && $year_level === '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
                                        <option value="4th Year" <?php echo (isset($year_level) && $year_level === '4th Year') ? 'selected' : ''; ?>>4th Year</option>
                                        <option value="Graduate" <?php echo (isset($year_level) && $year_level === 'Graduate') ? 'selected' : ''; ?>>Graduate</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="password" class="form-label">Password *</label>
                                <input type="password" id="password" name="password" class="form-input" 
                                       placeholder="Minimum 8 characters" required>
                                <small style="color: var(--medium-gray); font-size: 0.875rem;">
                                    Password must be at least 8 characters long
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Confirm Password *</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                                       placeholder="Re-enter your password" required>
                            </div>
                            
                            <div class="form-group" style="text-align: center; margin-top: 2rem;">
                                <button type="submit" name="register" class="btn btn-primary" style="width: 100%;">
                                    Create Account
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                    <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--azure-blue);">
                        <p style="color: var(--medium-gray);">
                            Already have an account? 
                            <a href="<?php echo SITE_URL; ?>/auth/login.php" style="color: var(--light-blue); text-decoration: none;">
                                Login here
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
