<?php
require_once 'config/config.php';

$page_title = 'News Article';

// Get news article by ID
$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($article_id <= 0) {
    header('Location: news.php');
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->prepare("SELECT * FROM news WHERE id = ? AND is_published = 1");
    $stmt->execute([$article_id]);
    $article = $stmt->fetch();
    
    if (!$article) {
        header('Location: news.php');
        exit;
    }
} catch (PDOException $e) {
    header('Location: news.php');
    exit;
}

include 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="card">
            <?php if ($article['image_url']): ?>
                <img src="<?php echo htmlspecialchars($article['image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($article['title']); ?>"
                     style="width: 100%; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
            <?php endif; ?>
            
            <div class="card-body">
                <h1 class="card-title"><?php echo htmlspecialchars($article['title']); ?></h1>
                
                <div class="news-meta" style="margin-bottom: 1.5rem;">
                    <span class="news-author">
                        <i class="fa-solid fa-user"></i>
                        By <?php echo htmlspecialchars($article['author']); ?>
                    </span>
                    <span class="news-date">
                        <i class="fa-solid fa-calendar"></i>
                        <?php echo date('F j, Y \a\t g:i A', strtotime($article['created_at'])); ?>
                    </span>
                    <?php if ($article['created_at'] != $article['updated_at']): ?>
                        <span class="news-updated">
                            <i class="fa-solid fa-edit"></i>
                            Updated: <?php echo date('F j, Y', strtotime($article['updated_at'])); ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <div style="font-size: 1.1rem; line-height: 1.8; color: var(--dark-gray);">
                    <?php echo nl2br(htmlspecialchars($article['content'])); ?>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 3rem;">
            <a href="news.php" class="btn btn-secondary">Back to News</a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
