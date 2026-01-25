<?php
require_once '../config/config.php';
require_admin();

$page_title = 'Admin CSS Test';
include '../includes/admin_header.php';
?>

<div class="admin-content">
    <div class="admin-container">
        <h1 style="color: red; font-size: 3rem; text-align: center; padding: 50px;">
            IF YOU SEE THIS PAGE, ADMIN CSS SHOULD BE LOADING
        </h1>
        <div style="background: #2c3e50; color: white; padding: 20px; margin: 20px; border-radius: 8px;">
            <h2>Admin Sidebar Color Test</h2>
            <p>This box should be dark teal (#2c3e50) if admin CSS is working.</p>
        </div>
        <div style="background: #34495e; color: white; padding: 20px; margin: 20px; border-radius: 8px;">
            <h2>Admin Header Color Test</h2>
            <p>This box should be dark grey (#34495e) if admin CSS is working.</p>
        </div>
        <div style="background: #f5f6fa; padding: 20px; margin: 20px; border: 2px solid #2c3e50; border-radius: 8px;">
            <h2>Admin Background Color Test</h2>
            <p>This box should have light grey background (#f5f6fa) if admin CSS is working.</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">✅</div>
            <div class="stat-info">
                <h3>Test Card</h3>
                <p>If you see a green circle icon, admin CSS is loading correctly!</p>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>
