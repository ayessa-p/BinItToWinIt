-- Create Default Admin Account
-- Run this SQL after the database is set up
-- Username: mtics.official
-- Password: mticstuptaguig

USE binittowinit;

-- Note: This SQL uses a placeholder password hash. 
-- For security, run the PHP script instead: admin/create_admin.php
-- Or manually hash the password using PHP: password_hash('mticstuptaguig', PASSWORD_DEFAULT)

-- If you need to run this manually, first generate the password hash using PHP:
-- <?php echo password_hash('mticstuptaguig', PASSWORD_DEFAULT); ?>
-- Then replace the password_hash value below

-- Check if admin exists, if not create it
INSERT INTO users (student_id, email, password_hash, full_name, is_admin, is_active, eco_tokens)
VALUES (
    'mtics.official',
    'mtics.official@mtics.edu.ph',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- This is a placeholder - use create_admin.php instead
    'MTICS Official Admin',
    1,
    1,
    0.00
)
ON DUPLICATE KEY UPDATE 
    is_admin = 1,
    is_active = 1;

-- Or update existing user to admin
-- UPDATE users SET is_admin = 1, is_active = 1 WHERE student_id = 'mtics.official';
