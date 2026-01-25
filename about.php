<?php
require_once 'config/config.php';

$page_title = 'About Us';
include 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">About MTICS</h1>
        
        <div style="max-width: 900px; margin: 0 auto 3rem;">
            <h2 style="font-size: 2rem; font-weight: 600; margin-bottom: 1rem; color: var(--text-dark);">Who We Are</h2>
            <p style="font-size: 1.1rem; line-height: 1.8; margin-bottom: 1.5rem; color: var(--dark-gray);">
                The Manila Technician Institute Computer Society (MTICS) is a student-led 
                organization dedicated to advancing computer science education, fostering 
                technological innovation, and promoting environmental sustainability within 
                our academic community.
            </p>
            <p style="font-size: 1.1rem; line-height: 1.8; color: var(--dark-gray);">
                Founded with the vision of bridging the gap between theoretical knowledge 
                and practical application, MTICS provides a platform for students to 
                explore cutting-edge technologies, collaborate on meaningful projects, 
                and make a positive impact on both the digital and physical world.
            </p>
        </div>
        
        <div class="grid grid-2" style="margin-bottom: 3rem;">
            <div class="card">
                <h3 class="card-title" style="margin-bottom: var(--spacing-sm);">Our Mission</h3>
                <p class="card-body" style="margin: 0;">To empower students with comprehensive technological knowledge, foster 
                    a culture of innovation and collaboration, and promote environmental 
                    consciousness through technology-driven solutions. We strive to create 
                    opportunities for hands-on learning, professional development, and 
                    community engagement.</p>
            </div>
            
            <div class="card">
                <h3 class="card-title" style="margin-bottom: var(--spacing-sm);">Our Vision</h3>
                <p class="card-body" style="margin: 0;">To be recognized as the premier student organization that seamlessly 
                    integrates technological excellence with environmental responsibility. 
                    We envision a future where our members are leaders in both the tech 
                    industry and sustainable practices, creating innovative solutions that 
                    benefit society and the planet.</p>
            </div>
        </div>
        
        <h2 class="section-title" style="margin-top: 4rem;">Our Core Values</h2>
        <div class="grid grid-4" style="margin-top: 3rem;">
            <div class="card" style="text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">💡</div>
                <h4 style="color: var(--text-dark); margin-bottom: 0.5rem; font-weight: 600;">Innovation</h4>
                <p style="color: var(--dark-gray);">Embracing new technologies and creative solutions</p>
            </div>
            
            <div class="card" style="text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🤝</div>
                <h4 style="color: var(--text-dark); margin-bottom: 0.5rem; font-weight: 600;">Collaboration</h4>
                <p style="color: var(--dark-gray);">Working together to achieve common goals</p>
            </div>
            
            <div class="card" style="text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🌱</div>
                <h4 style="color: var(--text-dark); margin-bottom: 0.5rem; font-weight: 600;">Sustainability</h4>
                <p style="color: var(--dark-gray);">Promoting environmental responsibility</p>
            </div>
            
            <div class="card" style="text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📚</div>
                <h4 style="color: var(--text-dark); margin-bottom: 0.5rem; font-weight: 600;">Education</h4>
                <p style="color: var(--dark-gray);">Continuous learning and knowledge sharing</p>
            </div>
        </div>
        
        <h2 class="section-title" style="margin-top: 4rem;">Organization Officers</h2>
        <div style="max-width: 900px; margin: 3rem auto;">
            <p style="text-align: center; color: var(--dark-gray); font-size: 1.1rem; margin-bottom: 2rem;">
                Our dedicated team of officers works tirelessly to organize events, 
                manage projects, and support our members. Meet the leaders driving 
                MTICS forward!
            </p>
            <div class="card" style="text-align: center; padding: 3rem;">
                <p style="color: var(--text-dark); font-size: 1.1rem;">
                    <strong>Officer information will be displayed here.</strong><br>
                    Contact us to learn more about our current leadership team.
                </p>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 3rem;">
            <a href="contact.php" class="btn btn-primary btn-large">Contact Us</a>
            <?php if (!is_logged_in()): ?>
                <a href="auth/register.php" class="btn btn-secondary btn-large" style="margin-left: 1rem;">Join MTICS</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
