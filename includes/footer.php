    </main>

    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>MTICS</h3>
                    <p>Manila Technician Institute Computer Society</p>
                    <p>Empowering students through technology and innovation.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/about.php">About Us</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/projects.php">Projects</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/news.php">News & Updates</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Bin It to Win It</h4>
                    <ul>
                        <li><a href="<?php echo SITE_URL; ?>/projects.php#bin-it-to-win-it">Project Info</a></li>
                        <?php if (is_logged_in()): ?>
                            <li><a href="<?php echo SITE_URL; ?>/dashboard/index.php">My Dashboard</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/dashboard/rewards.php">Rewards</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo SITE_URL; ?>/auth/login.php">Login</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/auth/register.php">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Contact</h4>
                    <p>Email: info@mtics.edu.ph</p>
                    <p>Phone: +63 XXX XXX XXXX</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> MTICS. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    <?php if (isset($additional_scripts)): ?>
        <?php foreach ($additional_scripts as $script): ?>
            <script src="<?php echo SITE_URL . $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
