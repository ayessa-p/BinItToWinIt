<?php
/**
 * Create Default Admin Account
 * Run this file once to create the admin account
 */

require_once '../config/config.php';

// Admin credentials
$student_id = 'mtics.official';
$email = 'mtics.official@mtics.edu.ph';
$password = 'mticstuptaguig';
$full_name = 'MTICS Official Admin';
$is_admin = true;

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if admin already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE student_id = ? OR email = ?");
    $stmt->execute([$student_id, $email]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing user to admin
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            UPDATE users 
            SET password_hash = ?, is_admin = 1, is_active = 1 
            WHERE id = ?
        ");
        $stmt->execute([$password_hash, $existing['id']]);
        echo "<!DOCTYPE html>
<html>
<head>
    <title>Admin Account Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class='success'>
        <h2>✓ Admin Account Updated Successfully!</h2>
        <p>The existing user has been updated to admin status with the new password.</p>
    </div>
    <div class='info'>
        <h3>Login Credentials:</h3>
        <p><strong>Student ID/Email:</strong> mtics.official</p>
        <p><strong>Password:</strong> mticstuptaguig</p>
        <p><a href='../auth/login.php'>Go to Login Page</a></p>
    </div>
</body>
</html>";
    } else {
        // Create new admin user
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO users (student_id, email, password_hash, full_name, is_admin, is_active, eco_tokens) 
            VALUES (?, ?, ?, ?, ?, 1, 0)
        ");
        $stmt->execute([$student_id, $email, $password_hash, $full_name, $is_admin ? 1 : 0]);
        
        echo "<!DOCTYPE html>
<html>
<head>
    <title>Admin Account Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin-top: 20px; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class='success'>
        <h2>✓ Admin Account Created Successfully!</h2>
        <p>The admin account has been created in the database.</p>
    </div>
    <div class='info'>
        <h3>Login Credentials:</h3>
        <p><strong>Student ID/Email:</strong> mtics.official</p>
        <p><strong>Password:</strong> mticstuptaguig</p>
        <p><a href='../auth/login.php'>Go to Login Page</a> | <a href='index.php'>Go to Admin Panel</a></p>
    </div>
    <div class='warning'>
        <p><strong>Security Note:</strong> For security reasons, delete this file (create_admin.php) after running it once.</p>
    </div>
</body>
</html>";
    }
    
} catch (PDOException $e) {
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Admin Account Setup - Error</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='error'>
        <h2>✗ Error Creating Admin Account</h2>
        <p>Error: " . htmlspecialchars($e->getMessage()) . "</p>
        <p>Please make sure:</p>
        <ul>
            <li>The database is properly configured</li>
            <li>The users table exists</li>
            <li>The is_admin column exists in the users table</li>
        </ul>
        <p>Run the database schema update first: <code>database/admin_update.sql</code></p>
    </div>
</body>
</html>";
}
