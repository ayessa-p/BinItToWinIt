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
                        <li><a href="<?php echo SITE_URL; ?>/news.php" class="nav-link">News</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/contact.php" class="nav-link">Contact</a></li>
                        <?php if (is_logged_in()): ?>
                            <?php if (is_admin()): ?>
                                <li><a href="<?php echo SITE_URL; ?>/admin/index.php" class="nav-link nav-link-highlight">Admin Panel</a></li>
                            <?php else: ?>
                                <li><a href="<?php echo SITE_URL; ?>/dashboard/index.php" class="nav-link nav-link-highlight">Dashboard</a></li>
                            <?php endif; ?>
                            <li><a href="<?php echo SITE_URL; ?>/dashboard/profile.php" class="nav-link">Profile</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/auth/logout.php" class="nav-link">Logout</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo SITE_URL; ?>/auth/login.php" class="nav-link nav-link-highlight">Login</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <button class="mobile-menu-toggle" aria-label="Toggle menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>

    <main class="main-content">
