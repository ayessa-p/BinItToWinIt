<?php
require_once 'config/config.php';

$page_title = 'News & Updates';

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

// Fetch all events for gallery
$all_events = [
    [
        'id' => 1,
        'title' => 'MTICS Annual General Assembly',
        'description' => 'Join us for our annual general assembly where we discuss upcoming projects, recognize outstanding members, and plan future initiatives for the Bin It to Win It program.',
        'event_date' => date('Y-m-d H:i:s', strtotime('+1 month')),
        'location' => 'MTICS Auditorium',
        'image_url' => 'bottle.jpg',
        'thumbnail_url' => 'bottle.jpg',
        'gallery_json' => json_encode(['bottle.jpg', 'bottle.jpg', 'bottle.jpg'])
    ],
    [
        'id' => 2,
        'title' => 'Eco-Token Workshop',
        'description' => 'Learn how to maximize your Eco-Token earnings through strategic recycling. This workshop covers best practices, sensor locations, and reward redemption strategies.',
        'event_date' => date('Y-m-d H:i:s', strtotime('+2 weeks')),
        'location' => 'Computer Lab 301',
        'image_url' => 'bottle.jpg',
        'thumbnail_url' => 'bottle.jpg',
        'gallery_json' => json_encode(['bottle.jpg', 'bottle.jpg', 'bottle.jpg'])
    ],
    [
        'id' => 3,
        'title' => 'Recycling Drive Competition',
        'description' => 'Compete with fellow students to see who can collect the most bottles! Great prizes and Eco-Token bonuses for top performers.',
        'event_date' => date('Y-m-d H:i:s', strtotime('+3 weeks')),
        'location' => 'Campus Grounds',
        'image_url' => 'bottle.jpg',
        'thumbnail_url' => 'bottle.jpg',
        'gallery_json' => json_encode(['bottle.jpg', 'bottle.jpg', 'bottle.jpg'])
    ]
];

// Try to fetch from database, but keep sample data as fallback
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM events ORDER BY event_date DESC");
    $stmt->execute();
    $db_events = $stmt->fetchAll();
    if (!empty($db_events)) {
        $all_events = $db_events;
    }
} catch (PDOException $e) {
    // Keep sample data
}

include 'includes/header.php';

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
                                     style="width: 100%; max-width: 800px; aspect-ratio: 16 / 9; object-fit: cover; border-radius: var(--radius-md); margin: 0 auto 1.5rem; display: block;">
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

<!-- Events Section -->
<section class="section" style="background: var(--light-blue-bg);">
    <div class="container">
        <h2 class="section-title" style="color: var(--medium-blue-text);">Events</h2>
        <div style="max-width: 1100px; margin: 0 auto;">
            <div class="events-grid">
                <?php foreach ($all_events as $event): ?>
                    <div class="event-card">
                        <div class="event-media">
                            <?php
                                $img = '';
                                if (!empty($event['thumbnail_url'])) $img = $event['thumbnail_url'];
                                elseif (!empty($event['gallery_json'])) {
                                    $g = json_decode($event['gallery_json'], true);
                                    if (is_array($g) && count($g)>0) $img = $g[0];
                                } elseif (!empty($event['image_url'])) $img = $event['image_url'];
                            ?>
                            <?php if ($img): ?>
                                <img src="<?php echo SITE_URL . '/' . htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                            <?php else: ?>
                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--medium-gray);">No Image</div>
                            <?php endif; ?>
                        </div>
                        <div class="event-body">
                            <h4 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h4>
                            <p class="event-desc"><?php echo htmlspecialchars(substr($event['description'],0,90)); ?>...</p>
                            <a href="event-details.php?id=<?php echo $event['id']; ?>" class="btn-view">View More</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>