<?php
require_once 'config/config.php';

$page_title = 'News & Updates';
include 'includes/header.php';

// Fetch news articles
$all_news = [];
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM news WHERE is_published = 1 ORDER BY created_at DESC");
    $stmt->execute();
    $all_news = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table doesn't exist yet - will be empty array
    $all_news = [];
}
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">News & Updates</h1>
        
        <?php if (empty($all_news)): ?>
            <div class="card" style="max-width: 600px; margin: 3rem auto; text-align: center;">
                <div class="card-body">
                    <p style="color: var(--medium-gray); font-size: 1.1rem;">
                        No news articles available at this time. Check back soon for updates!
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div style="max-width: 900px; margin: 0 auto;">
                <?php foreach ($all_news as $news): ?>
                    <div id="news-<?php echo $news['id']; ?>" class="card" style="margin-bottom: 2rem; scroll-margin-top: 100px;">
                        <div class="card-header">
                            <h2 class="card-title"><?php echo htmlspecialchars($news['title']); ?></h2>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem;">
                                <p style="color: var(--medium-gray); font-size: 0.9rem;">
                                    <?php if ($news['author']): ?>
                                        By <?php echo htmlspecialchars($news['author']); ?> • 
                                    <?php endif; ?>
                                    <?php echo date('F j, Y \a\t g:i A', strtotime($news['created_at'])); ?>
                                </p>
                                <?php if ($news['updated_at'] != $news['created_at']): ?>
                                    <p style="color: var(--medium-gray); font-size: 0.85rem;">
                                        Updated: <?php echo date('F j, Y', strtotime($news['updated_at'])); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($news['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($news['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($news['title']); ?>"
                                     style="width: 100%; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                            <?php endif; ?>
                            <div style="font-size: 1.05rem; line-height: 1.8; color: var(--medium-gray);">
                                <?php echo nl2br(htmlspecialchars($news['content'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
