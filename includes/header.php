<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>MTICS</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <a href="<?php echo SITE_URL; ?>/index.php" class="logo-link">
                        <div class="logo-container">
                            <img src="<?php echo SITE_URL; ?>/mtics2.png" alt="MTICS Logo" class="logo-img">
                            <div class="logo-text">
                                <div class="logo-main-text">MTICS</div>
                                <div class="logo-sub-text">MANILA TECHNICIAN INSTITUTE COMPUTER SOCIETY</div>
                            </div>
                        </div>
                    </a>
                </div>
                
                <nav class="main-nav">
                    <ul class="nav-list">
                        <li><a href="<?php echo SITE_URL; ?>/index.php" class="nav-link">Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/about.php" class="nav-link">About</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/projects.php" class="nav-link">Projects</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/news.php" class="nav-link">Events</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/dashboard/automation.php" class="nav-link">Services</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/contact.php" class="nav-link">Contact</a></li>
                        <?php if (is_logged_in()): ?>
                            <?php if (is_admin()): ?>
                                <li><a href="<?php echo SITE_URL; ?>/admin/index.php" class="nav-link nav-link-highlight">Admin Panel</a></li>
                            <?php else: ?>
                                <li><a href="<?php echo SITE_URL; ?>/dashboard/index.php" class="nav-link nav-link-highlight">Dashboard</a></li>
                            <?php endif; ?>
                        <?php else: ?>
                            <li><a href="<?php echo SITE_URL; ?>/auth/login.php" class="nav-link nav-link-highlight">Login</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>

                <?php if (is_logged_in()): ?>
                    <?php
                    $current_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Account';
                    $initials = strtoupper(substr($current_name, 0, 1));
                    $avatar_url = null;
                    
                    // Try to get current user data from database to ensure latest profile image
                    try {
                        $db = Database::getInstance()->getConnection();
                        $stmt = $db->prepare("SELECT profile_image FROM users WHERE id = ?");
                        $stmt->execute([get_user_id()]);
                        $user_data = $stmt->fetch();
                        $avatar_url = $user_data['profile_image'] ?? null;
                    } catch (PDOException $e) {
                        // Column doesn't exist or other database error, use session fallback
                        $avatar_url = $_SESSION['profile_image'] ?? null;
                        error_log("Header - DB error, using session fallback: " . $e->getMessage());
                    }
                    
                    // Debug: Log what we're using
                    error_log("Header - Using profile_image: " . ($avatar_url ?? 'null'));
                    error_log("Header - Session full_name: " . $current_name);
                    ?>
                    <div class="user-dropdown" style="position: relative; margin-left: 1rem;">
                        <button class="user-dropdown-toggle" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 1rem; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; cursor: pointer; transition: all 0.3s ease;">
                            <div style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; background: #e0e7ff; color: #4f46e5; text-decoration: none; overflow: hidden;">
                                <?php if ($avatar_url): ?>
                                    <?php 
                                    // Debug: Log the image source
                                    error_log("Header - Displaying image: " . htmlspecialchars($avatar_url));
                                    ?>
                                    <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <span style="display: none; font-weight: 600; font-size: 0.9rem;"><?php echo $initials; ?></span>
                                <?php else: ?>
                                    <span style="font-weight: 600; font-size: 0.9rem;"><?php echo $initials; ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="font-size: 0.9rem; color: var(--medium-gray); max-width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo htmlspecialchars($current_name); ?>
                                </span>
                                <i class="fa-solid fa-chevron-down" style="font-size: 0.7rem; color: var(--medium-gray);"></i>
                            </div>
                        </button>
                        
                        <div class="user-dropdown-menu" style="position: absolute; top: 100%; right: 0; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); min-width: 180px; z-index: 9999; display: none; pointer-events: auto;">
                            <div style="padding: 0.5rem 0;">
                                <a href="<?php echo SITE_URL; ?>/dashboard/profile.php" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; color: var(--text-dark); text-decoration: none; transition: background 0.2s ease; border-radius: 4px;">
                                    <i class="fa-solid fa-user-edit" style="color: var(--medium-gray);"></i>
                                    <span>Edit Profile</span>
                                </a>
                               
                                <div style="border-top: 1px solid #e5e7eb; margin: 0.25rem 0;"></div>
                                <a href="<?php echo SITE_URL; ?>/auth/logout.php" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; color: var(--text-dark); text-decoration: none; transition: background 0.2s ease; border-radius: 4px;">
                                    <i class="fa-solid fa-sign-out-alt" style="color: var(--medium-gray);"></i>
                                    <span>Logout</span>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <button class="mobile-menu-toggle" aria-label="Toggle menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>

    <script>
        // User dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownToggle = document.querySelector('.user-dropdown-toggle');
            const dropdownMenu = document.querySelector('.user-dropdown-menu');
            
            if (dropdownToggle && dropdownMenu) {
                console.log('Dropdown elements found!');
                
                // Show dropdown on hover
                dropdownToggle.addEventListener('mouseenter', function() {
                    console.log('Mouse entered toggle');
                    dropdownMenu.style.display = 'block';
                });
                
                // Hide dropdown when leaving the dropdown area
                dropdownToggle.addEventListener('mouseleave', function(e) {
                    setTimeout(() => {
                        if (!dropdownMenu.matches(':hover')) {
                            dropdownMenu.style.display = 'none';
                        }
                    }, 100);
                });
                
                dropdownMenu.addEventListener('mouseleave', function() {
                    dropdownMenu.style.display = 'none';
                });
                
                // Close dropdown when clicking on menu items
                dropdownMenu.addEventListener('click', function(e) {
                    if (e.target.tagName === 'A') {
                        dropdownMenu.style.display = 'none';
                    }
                });
                
                // Add hover effects to menu items
                const menuItems = dropdownMenu.querySelectorAll('a');
                menuItems.forEach(item => {
                    item.addEventListener('mouseenter', function() {
                        this.style.background = 'rgba(0, 123, 255, 0.1)';
                    });
                    
                    item.addEventListener('mouseleave', function() {
                        this.style.background = 'transparent';
                    });
                });
            } else {
                console.log('Dropdown elements NOT found!');
                console.log('Toggle:', dropdownToggle);
                console.log('Menu:', dropdownMenu);
            }
        });
    </script>

    <main class="main-content">
