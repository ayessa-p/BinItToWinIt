<?php
require_once 'config/config.php';

$page_title = 'Projects';
include 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">Our Projects</h1>
        
        <div id="bin-it-to-win-it" style="scroll-margin-top: 100px;">
            <div class="card" style="max-width: 1000px; margin: 0 auto 3rem;">
                <div class="card-header">
                    <h2 class="card-title" style="font-size: 2rem;">Bin It to Win It</h2>
                    <p style="color: var(--medium-gray);">Smart Recycling Rewards Program</p>
                </div>
                <div class="card-body">
                    <div style="margin-bottom: 2rem;">
                        <h3 style="color: var(--light-blue); margin-bottom: 1rem;">Project Overview</h3>
                        <p style="font-size: 1.1rem; line-height: 1.8; margin-bottom: 1.5rem;">
                            <strong>Bin It to Win It</strong> is an innovative recycling initiative that 
                            combines environmental consciousness with technology. Using smart sensors 
                            integrated into recycling bins, we track recycling activities and reward 
                            students with Eco-Tokens for their contributions to sustainability.
                        </p>
                        <p style="font-size: 1.1rem; line-height: 1.8;">
                            This project demonstrates how IoT (Internet of Things) technology can be 
                            leveraged to create positive behavioral change while promoting 
                            environmental responsibility within our campus community.
                        </p>
                    </div>
                    
                    <div class="circuit-divider"></div>
                    
                    <div style="margin: 2rem 0;">
                        <h3 style="color: var(--light-blue); margin-bottom: 1.5rem;">How It Works</h3>
                        <div class="grid grid-3">
                            <div style="text-align: center; padding: 1.5rem;">
                                <div style="font-size: 4rem; margin-bottom: 1rem;">1️⃣</div>
                                <h4 style="color: var(--light-blue); margin-bottom: 0.5rem;">Register</h4>
                                <p style="color: var(--medium-gray);">
                                    Create your account and get your student ID linked to the system
                                </p>
                            </div>
                            
                            <div style="text-align: center; padding: 1.5rem;">
                                <div style="font-size: 4rem; margin-bottom: 1rem;">2️⃣</div>
                                <h4 style="color: var(--light-blue); margin-bottom: 0.5rem;">Recycle</h4>
                                <p style="color: var(--medium-gray);">
                                    Deposit plastic bottles in our smart recycling bins equipped with sensors
                                </p>
                            </div>
                            
                            <div style="text-align: center; padding: 1.5rem;">
                                <div style="font-size: 4rem; margin-bottom: 1rem;">3️⃣</div>
                                <h4 style="color: var(--light-blue); margin-bottom: 0.5rem;">Earn & Redeem</h4>
                                <p style="color: var(--medium-gray);">
                                    Automatically receive Eco-Tokens and redeem them for exciting rewards
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="circuit-divider"></div>
                    
                    <div style="margin: 2rem 0;">
                        <h3 style="color: var(--light-blue); margin-bottom: 1rem;">Eco-Token System</h3>
                        <div style="background: rgba(61, 127, 199, 0.1); padding: 1.5rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                            <p style="font-size: 1.1rem; margin-bottom: 1rem;">
                                <strong style="color: var(--gold-yellow);">Each recycled bottle = <?php echo TOKENS_PER_BOTTLE; ?> Eco-Tokens</strong>
                            </p>
                            <p style="color: var(--medium-gray);">
                                Tokens are automatically credited to your account when the sensor detects 
                                a bottle deposit. You can track your balance and transaction history 
                                in your personal dashboard.
                            </p>
                        </div>
                    </div>
                    
                    <div style="margin: 2rem 0;">
                        <h3 style="color: var(--light-blue); margin-bottom: 1rem;">Available Rewards</h3>
                        <div class="grid grid-2">
                            <div style="padding: 1rem; background: rgba(22, 36, 71, 0.5); border-radius: var(--radius-sm);">
                                <h4 style="color: var(--light-blue); margin-bottom: 0.5rem;">Services</h4>
                                <ul style="color: var(--medium-gray); list-style: none; padding-left: 0;">
                                    <li>• Printing Credits</li>
                                    <li>• Internet Access</li>
                                    <li>• Lab Access</li>
                                </ul>
                            </div>
                            <div style="padding: 1rem; background: rgba(22, 36, 71, 0.5); border-radius: var(--radius-sm);">
                                <h4 style="color: var(--light-blue); margin-bottom: 0.5rem;">Merchandise & Electronics</h4>
                                <ul style="color: var(--medium-gray); list-style: none; padding-left: 0;">
                                    <li>• MTICS Merchandise</li>
                                    <li>• USB Drives</li>
                                    <li>• Computer Accessories</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--azure-blue);">
                        <?php if (is_logged_in()): ?>
                            <a href="dashboard/index.php" class="btn btn-primary btn-large">Go to Dashboard</a>
                            <a href="dashboard/rewards.php" class="btn btn-secondary btn-large" style="margin-left: 1rem;">Browse Rewards</a>
                        <?php else: ?>
                            <a href="auth/register.php" class="btn btn-primary btn-large">Get Started</a>
                            <a href="auth/login.php" class="btn btn-secondary btn-large" style="margin-left: 1rem;">Login</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="circuit-divider"></div>
        
        <h2 class="section-title" style="margin-top: 4rem;">Other MTICS Projects</h2>
        <div class="grid grid-2" style="margin-top: 3rem;">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Tech Workshops</h3>
                </div>
                <div class="card-body">
                    <p>Regular workshops covering programming, web development, cybersecurity, 
                    and emerging technologies. Open to all students interested in expanding 
                    their technical skills.</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Hackathons & Competitions</h3>
                </div>
                <div class="card-body">
                    <p>Organize and participate in coding competitions, hackathons, and 
                    innovation challenges. Showcase your skills and win prizes!</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Community Outreach</h3>
                </div>
                <div class="card-body">
                    <p>Technology education programs for local communities, teaching 
                    digital literacy and computer skills to underserved populations.</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Research & Development</h3>
                </div>
                <div class="card-body">
                    <p>Collaborative research projects exploring innovative applications 
                    of technology in education, sustainability, and social impact.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
