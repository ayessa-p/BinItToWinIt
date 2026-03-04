<?php
require_once '../config/config.php';
require_login();

$page_title = 'My Profile';
$user_id = get_user_id();

$db = Database::getInstance()->getConnection();

// Handle profile update
$message = '';
$message_type = '';

// Check for successful update redirect
if (isset($_GET['updated']) && $_GET['updated'] == '1') {
    $message = 'Profile updated successfully!';
    $message_type = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token. Please try again.';
        $message_type = 'error';
    } else {
        $full_name = sanitize_input($_POST['full_name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $course = sanitize_input($_POST['course'] ?? '');
        $year_level = sanitize_input($_POST['year_level'] ?? '');
        
        if (empty($full_name) || empty($email)) {
            $message = 'Full name and email are required.';
            $message_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $message_type = 'error';
        } else {
            // Check if email is already taken by another user
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $message = 'Email address is already in use by another account.';
                $message_type = 'error';
            } else {
                // Handle profile image upload (optional)
                $profile_image_path = null;
                error_log("FILES array: " . print_r($_FILES, true));
                
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $avatar_dir = __DIR__ . '/../uploads/avatars/';
                    if (!is_dir($avatar_dir)) {
                        mkdir($avatar_dir, 0755, true);
                    }
                    $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                    $allowed_ext = ['jpg', 'jpeg', 'png'];
                    if (in_array($ext, $allowed_ext, true)) {
                        $safe_name = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                        $target_path = $avatar_dir . $safe_name;
                        $web_path = SITE_URL . '/uploads/avatars/' . $safe_name;
                        
                        error_log("Target path: " . $target_path);
                        error_log("Web path: " . $web_path);
                        error_log("Avatar dir exists: " . (is_dir($avatar_dir) ? 'YES' : 'NO'));
                        
                        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_path)) {
                            $profile_image_path = $web_path;
                            error_log("File moved successfully to: " . $target_path);
                            error_log("Profile image path set to: " . $profile_image_path);
                            error_log("File exists after move: " . (file_exists($target_path) ? 'YES' : 'NO'));
                        } else {
                            error_log("Failed to move uploaded file");
                        }
                    }
                }

                if ($profile_image_path !== null) {
                    $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, course = ?, year_level = ?, profile_image = ? WHERE id = ?");
                    $params = [$full_name, $email, $course, $year_level, $profile_image_path, $user_id];
                } else {
                    $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, course = ?, year_level = ? WHERE id = ?");
                    $params = [$full_name, $email, $course, $year_level, $user_id];
                }

                if ($stmt->execute($params)) {
                    $_SESSION['full_name'] = $full_name;
                    if ($profile_image_path !== null) {
                        $_SESSION['profile_image'] = $profile_image_path;
                        // Debug: Log the update
                        error_log("Profile image updated to: " . $profile_image_path);
                        error_log("Session profile_image now: " . ($_SESSION['profile_image'] ?? 'null'));
                    }
                    $message = 'Profile updated successfully!';
                    $message_type = 'success';
                    
                    // Force redirect to refresh session and header
                    header("Location: profile.php?updated=1");
                    exit();
                } else {
                    $message = 'Failed to update profile. Please try again.';
                    $message_type = 'error';
                }
            }
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token. Please try again.';
        $message_type = 'error';
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $message = 'Please fill in all password fields.';
            $message_type = 'error';
        } elseif (strlen($new_password) < 8) {
            $message = 'New password must be at least 8 characters long.';
            $message_type = 'error';
        } elseif ($new_password !== $confirm_password) {
            $message = 'New passwords do not match.';
            $message_type = 'error';
        } else {
            // Verify current password
            $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($current_password, $user['password_hash'])) {
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                if ($stmt->execute([$new_password_hash, $user_id])) {
                    $message = 'Password changed successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to change password. Please try again.';
                    $message_type = 'error';
                }
            } else {
                $message = 'Current password is incorrect.';
                $message_type = 'error';
            }
        }
    }
}

// Get user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Debug: Check what's in the database vs session
error_log("Database profile_image: " . ($user['profile_image'] ?? 'null'));
error_log("Session profile_image: " . ($_SESSION['profile_image'] ?? 'null'));
error_log("Session full_name: " . ($_SESSION['full_name'] ?? 'null'));

include '../includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">My Profile</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>" style="max-width: 800px; margin: 0 auto 2rem;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-2" style="max-width: 1000px; margin: 0 auto; gap: 2rem;">
            <!-- Profile Information -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Profile Information</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data" data-validate>
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="form-group">
                            <label class="form-label">Profile Picture</label>
                            <div style="display:flex; align-items:center; gap:1rem;">
                                <?php $avatar = $user['profile_image'] ?? null; ?>
                                <div style="width:64px; height:64px; border-radius:50%; overflow:hidden; background:#e0e7ff; display:flex; align-items:center; justify-content:center; font-weight:600; color:#4f46e5;">
                                    <?php if ($avatar): ?>
                                        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Profile picture" style="width:100%; height:100%; object-fit:cover;">
                                    <?php else: ?>
                                        <span><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="profile_image" accept=".jpg,.jpeg,.png">
                            </div>
                            <small style="color: var(--medium-gray); font-size: 0.875rem;">Optional. JPG or PNG, up to 5MB.</small>
                        </div>

                        <div class="form-group">
                            <label for="student_id" class="form-label">Student ID</label>
                            <input type="text" id="student_id" class="form-input" 
                                   value="<?php echo htmlspecialchars($user['student_id']); ?>" disabled>
                            <small style="color: var(--medium-gray); font-size: 0.875rem;">
                                Student ID cannot be changed
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="full_name" class="form-label">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" class="form-input" 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" id="email" name="email" class="form-input" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="grid grid-2">
                            <div class="form-group">
                                <label for="course" class="form-label">Course</label>
                                <input type="text" id="course" name="course" class="form-input" 
                                       value="<?php echo htmlspecialchars($user['course'] ?? ''); ?>" 
                                       placeholder="e.g., Computer Science">
                            </div>
                            
                            <div class="form-group">
                                <label for="year_level" class="form-label">Year Level</label>
                                <select id="year_level" name="year_level" class="form-select">
                                    <option value="">Select year level</option>
                                    <option value="1st Year" <?php echo ($user['year_level'] === '1st Year') ? 'selected' : ''; ?>>1st Year</option>
                                    <option value="2nd Year" <?php echo ($user['year_level'] === '2nd Year') ? 'selected' : ''; ?>>2nd Year</option>
                                    <option value="3rd Year" <?php echo ($user['year_level'] === '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
                                    <option value="4th Year" <?php echo ($user['year_level'] === '4th Year') ? 'selected' : ''; ?>>4th Year</option>
                                    <option value="Graduate" <?php echo ($user['year_level'] === 'Graduate') ? 'selected' : ''; ?>>Graduate</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-top: 2rem;">
                            <button type="submit" name="update_profile" class="btn btn-primary" style="width: 100%;">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Change Password</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="" data-validate>
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="form-group">
                            <label for="current_password" class="form-label">Current Password *</label>
                            <input type="password" id="current_password" name="current_password" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password" class="form-label">New Password *</label>
                            <input type="password" id="new_password" name="new_password" class="form-input" 
                                   placeholder="Minimum 8 characters" required>
                            <small style="color: var(--medium-gray); font-size: 0.875rem;">
                                Password must be at least 8 characters long
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                                   placeholder="Re-enter new password" required>
                        </div>
                        
                        <div class="form-group" style="margin-top: 2rem;">
                            <button type="submit" name="change_password" class="btn btn-primary" style="width: 100%;">
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 3rem;">
            <a href="index.php" class="btn btn-secondary btn-large">Back to Dashboard</a>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
