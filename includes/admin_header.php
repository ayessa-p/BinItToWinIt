<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Admin - MTICS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=<?php echo time(); ?>">
    <style>
        /* CRITICAL: Admin page isolation - must override everything */
        html, body.admin-page, body.admin-page * {
            box-sizing: border-box !important;
        }
        
        html, body.admin-page {
            margin: 0 !important;
            padding: 0 !important;
            background: #f5f6fa !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
        }
        
        /* Hide ALL user-facing elements */
        body.admin-page .main-header,
        body.admin-page .main-footer,
        body.admin-page .circuit-background,
        body.admin-page .hero-section,
        body.admin-page .section:not(.admin-section),
        body.admin-page .container:not(.admin-container) {
            display: none !important;
        }
    </style>
</head>
<body class="admin-page">
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-logo-section">
                <a href="<?php echo SITE_URL; ?>/index.php" class="logo-link">
                    <img src="<?php echo SITE_URL; ?>/mtics2.png" alt="MTICS Logo" class="logo-img">
                </a>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="admin-badge">Admin</span>
                    <span style="color: #ffffff; font-weight: 600; font-size: 0.9rem;">MTICS</span>
                </div>
            </div>
            
            <nav class="admin-nav">
                <a href="<?php echo SITE_URL; ?>/index.php" class="admin-nav-link">View Site</a>
                <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="admin-nav-link">Logout</a>
            </nav>
        </div>
    </header>
    
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <nav class="admin-sidebar-nav">
                <a href="<?php echo SITE_URL; ?>/admin/index.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
                    <span class="sidebar-icon"><i class="fa-solid fa-gauge"></i></span>
                    <span>Dashboard</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/tokens.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) == 'tokens.php') ? 'active' : ''; ?>">
                    <span class="sidebar-icon"><i class="fa-solid fa-coins"></i></span>
                    <span>Manage Tokens</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/events.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) == 'events.php') ? 'active' : ''; ?>">
                    <span class="sidebar-icon"><i class="fa-solid fa-calendar-days"></i></span>
                    <span>Events</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/projects.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) == 'projects.php') ? 'active' : ''; ?>">
                    <span class="sidebar-icon"><i class="fa-solid fa-diagram-project"></i></span>
                    <span>Projects</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/news.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) == 'news.php') ? 'active' : ''; ?>">
                    <span class="sidebar-icon"><i class="fa-solid fa-newspaper"></i></span>
                    <span>News</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/users.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>">
                    <span class="sidebar-icon"><i class="fa-solid fa-users"></i></span>
                    <span>Users</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/rewards.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) == 'rewards.php') ? 'active' : ''; ?>">
                    <span class="sidebar-icon"><i class="fa-solid fa-gift"></i></span>
                    <span>Rewards</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/automation.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) == 'automation.php') ? 'active' : ''; ?>">
                    <span class="sidebar-icon"><i class="fa-solid fa-cogs"></i></span>
                    <span>Services</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/sensor_monitor.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) == 'sensor_monitor.php') ? 'active' : ''; ?>">
                    <span class="sidebar-icon"><i class="fa-solid fa-microchip"></i></span>
                    <span>Sensor Monitor</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/api_keys.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) == 'api_keys.php') ? 'active' : ''; ?>">
                    <span class="sidebar-icon"><i class="fa-solid fa-key"></i></span>
                    <span>API Keys</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/redemptions.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) == 'redemptions.php') ? 'active' : ''; ?>">
                    <span class="sidebar-icon"><i class="fa-solid fa-circle-check"></i></span>
                    <span>Redemptions</span>
                </a>
            </nav>
        </aside>
        
        <main class="admin-main">
