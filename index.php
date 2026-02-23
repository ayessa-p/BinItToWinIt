<?php
require_once 'config/config.php';

$page_title = 'Home';
include 'includes/header.php';

// Fetch recent news
$recent_news = [];
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM news WHERE is_published = 1 ORDER BY created_at DESC LIMIT 3");
    $stmt->execute();
    $recent_news = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table doesn't exist yet - will be empty array
    $recent_news = [];
}
?>

<section class="hero-section">
    <div class="container">
        <div class="hero-content-block">
            <div class="hero-text-content">
                <span class="hero-label" style="font-size: 0.9rem;">Project</span>
                <h1 class="hero-title" style="font-size: 2.2rem; margin-bottom: 1rem;">Bin It to Win It</h1>
                <p class="hero-description" style="font-size: 1rem; line-height: 1.6;">
                    An innovative recycling initiative that combines environmental consciousness with technology. 
                    Using smart sensors integrated into recycling bins, we track recycling activities and reward 
                    students with Eco-Tokens for their contributions to sustainability. This project demonstrates 
                    how IoT technology can create positive behavioral change while promoting environmental responsibility.
                </p>
                <div style="margin-top: var(--spacing-md);">
                    <a href="projects.php#bin-it-to-win-it" class="btn btn-secondary btn-large" style="font-size: 1rem;">LEARN MORE</a>
                </div>
            </div>
            <div>
                <img src="projectpic.png" alt="Bin It to Win It Project" style="width: 100%; height: 300px; object-fit: cover; border-radius: var(--radius-md);">
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="mission-section">
            <div>
                <img src="bottle.jpg" alt="Mission" style="width: 100%; height: 400px; object-fit: cover; border-radius: var(--radius-md);">
            </div>
            <div class="mission-content">
                <h2>Our Mission</h2>
                <p>
                    To empower students with technological knowledge, foster innovation, 
                    and create a community that values both technical excellence and 
                    environmental responsibility. We bridge the gap between academic learning 
                    and real-world applications.
                </p>
                <p>
                    Through our innovative programs like "Bin It to Win It," we demonstrate 
                    how technology can be harnessed to create positive environmental impact 
                    while rewarding students for their contributions to sustainability.
                </p>
                <div style="margin-top: var(--spacing-md);">
                    <a href="about.php" class="btn btn-primary">Learn More</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section" style="background: var(--light-gray);">
    <div class="container">
        <h2 class="section-title">Featured Project: Bin It to Win It</h2>
        <div class="card" style="max-width: 900px; margin: 0 auto;">
            <div class="card-header">
                <h3 class="card-title">Transform Recycling into Rewards</h3>
            </div>
            <div class="card-body">
                <p style="font-size: 1.1rem; margin-bottom: 1.5rem;">
                    <strong>Bin It to Win It</strong> is our innovative recycling program that 
                    rewards students for their environmental efforts. Every recycled bottle 
                    you deposit earns you <strong style="color: var(--gold-yellow);">Eco-Tokens</strong> 
                    that can be redeemed for exciting rewards!
                </p>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
                    <div style="text-align: center;">
                        <div style="font-size: 3rem; color: var(--gold-yellow); margin-bottom: 0.5rem;"><i class="fa-solid fa-recycle"></i></div>
                        <h4 style="color: var(--light-blue); margin-bottom: 0.5rem;">Recycle</h4>
                        <p style="color: var(--medium-gray);">Deposit bottles in our smart bins</p>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 3rem; color: var(--gold-yellow); margin-bottom: 0.5rem;"><i class="fa-solid fa-coins"></i></div>
                        <h4 style="color: var(--light-blue); margin-bottom: 0.5rem;">Earn Tokens</h4>
                        <p style="color: var(--medium-gray);">Get Eco-Tokens automatically</p>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 3rem; color: var(--gold-yellow); margin-bottom: 0.5rem;"><i class="fa-solid fa-gift"></i></div>
                        <h4 style="color: var(--light-blue); margin-bottom: 0.5rem;">Redeem</h4>
                        <p style="color: var(--medium-gray);">Exchange tokens for rewards</p>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="projects.php#bin-it-to-win-it" class="btn btn-primary">Learn More</a>
                    <?php if (!is_logged_in()): ?>
                        <a href="auth/register.php" class="btn btn-secondary" style="margin-left: 1rem;">Get Started</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($recent_news)): ?>
<section class="section">
    <div class="container">
        <h2 class="section-title">Latest News & Updates</h2>
        <div class="grid grid-3" style="margin-top: 3rem;">
            <?php foreach ($recent_news as $news): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php echo htmlspecialchars($news['title']); ?></h3>
                    <p style="color: var(--medium-gray); font-size: 0.9rem;">
                        <?php echo date('F j, Y', strtotime($news['created_at'])); ?>
                    </p>
                </div>
                <div class="card-body">
                    <p><?php echo htmlspecialchars(substr($news['content'], 0, 150)) . '...'; ?></p>
                    <a href="news.php#news-<?php echo $news['id']; ?>" style="color: var(--light-blue); text-decoration: none;">
                        Read more →
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align: center; margin-top: 2rem;">
            <a href="news.php" class="btn btn-secondary">View All News</a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
