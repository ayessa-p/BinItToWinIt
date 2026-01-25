<?php
require_once '../config/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $student_id = sanitize_input($_POST['student_id'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($student_id) || empty($password)) {
            $error = 'Please enter both student ID and password.';
        } else {
            try {
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("SELECT id, student_id, email, password_hash, full_name, eco_tokens, is_active FROM users WHERE student_id = ? OR email = ?");
                $stmt->execute([$student_id, $student_id]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    if (!$user['is_active']) {
                        $error = 'Your account has been deactivated. Please contact support.';
                    } else {
                        // Update last login
                        $update_stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                        $update_stmt->execute([$user['id']]);
                        
                        // Set session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['student_id'] = $user['student_id'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['eco_tokens'] = $user['eco_tokens'];
                        $_SESSION['last_activity'] = time();
                        
                        // Check if user is admin and redirect accordingly
                        $check_admin_stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
                        $check_admin_stmt->execute([$user['id']]);
                        $admin_check = $check_admin_stmt->fetch();
                        
                        if ($admin_check && $admin_check['is_admin'] == 1) {
                            header('Location: ' . SITE_URL . '/admin/index.php');
                        } else {
                            header('Location: ' . SITE_URL . '/dashboard/index.php');
                        }
                        exit();
                    }
                } else {
                    $error = 'Invalid student ID/email or password.';
                }
            } catch (PDOException $e) {
                $error = 'Database error. Please ensure the database tables are properly installed. <a href="' . SITE_URL . '/install_check.php" style="color: var(--light-blue);">Check installation</a>';
            }
        }
    }
}

$page_title = 'Login';
include '../includes/header.php';
?>

<section class="section">
    <div class="container">
        <div style="max-width: 500px; margin: 0 auto;">
            <div class="card">
                <div class="card-header" style="text-align: center;">
                    <h1 class="card-title">Login to Your Account</h1>
                    <p style="color: var(--medium-gray);">Access your Eco-Token dashboard</p>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" data-validate>
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="form-group">
                            <label for="student_id" class="form-label">Student ID or Email *</label>
                            <input type="text" id="student_id" name="student_id" class="form-input" 
                                   placeholder="Enter your student ID or email" required autofocus>
                        </div>
                        
                        <div class="form-group">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" id="password" name="password" class="form-input" 
                                   placeholder="Enter your password" required>
                        </div>
                        
                        <div class="form-group" style="text-align: center; margin-top: 2rem;">
                            <button type="submit" name="login" class="btn btn-primary" style="width: 100%;">
                                Login
                            </button>
                        </div>
                    </form>
                    
                    <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--azure-blue);">
                        <p style="color: var(--medium-gray);">
                            Don't have an account? 
                            <a href="<?php echo SITE_URL; ?>/auth/register.php" style="color: var(--light-blue); text-decoration: none;">
                                Register here
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
