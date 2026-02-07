<?php
require_once 'config/config.php';

$page_title = 'Event Details';

// Get event by ID
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($event_id <= 0) {
    header('Location: news.php');
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    
    if (!$event) {
        header('Location: news.php');
        exit;
    }
} catch (PDOException $e) {
    header('Location: news.php');
    exit;
}

// Get all events for gallery
$all_events = [];
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT * FROM events ORDER BY event_date DESC");
    $all_events = $stmt->fetchAll();
} catch (PDOException $e) {
    $all_events = [];
}

include 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="card">
            <?php
                // Choose primary image: prefer thumbnail, then first gallery image, then legacy image_url
                $primary_img = '';
                if (!empty($event['thumbnail_url'])) {
                    $primary_img = $event['thumbnail_url'];
                } elseif (!empty($event['gallery_json'])) {
                    $g = json_decode($event['gallery_json'], true);
                    if (is_array($g) && count($g) > 0) $primary_img = $g[0];
                } elseif (!empty($event['image_url'])) {
                    $primary_img = $event['image_url'];
                }
            ?>
            <?php if (!empty($primary_img)): ?>
                <img src="<?php echo SITE_URL . '/' . htmlspecialchars($primary_img); ?>" 
                     alt="<?php echo htmlspecialchars($event['title']); ?>"
                     style="width: 100%; max-width: 900px; aspect-ratio: 16 / 9; object-fit: cover; border-radius: var(--radius-md); margin: 0 auto 1.5rem; display: block;">
            <?php endif; ?>
            
            <div class="card-body">
                <h1 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h1>
                
                <div class="event-meta" style="margin-bottom: 1.5rem;">
                    <span class="event-date">
                        <i class="fa-solid fa-calendar-days"></i>
                        <?php echo date('F j, Y \a\t g:i A', strtotime($event['event_date'])); ?>
                    </span>
                    <span class="event-location">
                        <i class="fa-solid fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($event['location']); ?>
                    </span>
                </div>
                
                <div class="event-description">
                    <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                </div>
                
                <!-- Event Gallery -->
                <?php
                    $event_gallery = [];
                    if (!empty($event['gallery_json'])) {
                        $parsed = json_decode($event['gallery_json'], true);
                        if (is_array($parsed) && count($parsed) > 0) {
                            $event_gallery = $parsed;
                        }
                    }
                    // Fallback: show primary image if no gallery
                    if (empty($event_gallery) && !empty($primary_img)) {
                        $event_gallery = [$primary_img];
                    }
                    
                    // Debug: Show what we have
                    echo '<!-- Debug: Gallery count: ' . count($event_gallery) . ' -->';
                    if (isset($event['gallery_json'])) {
                        echo '<!-- Debug: Gallery data: ' . htmlspecialchars($event['gallery_json']) . ' -->';
                    } else {
                        echo '<!-- Debug: No gallery_json field -->';
                    }
                ?>
                <?php if (!empty($event_gallery)): ?>
                    <div class="event-gallery" style="margin-top: 2rem;">
                        <h3 style="color: var(--primary-blue); margin-bottom: 1rem;">Event Gallery</h3>
                        <div class="gallery-grid">
                            <?php foreach ($event_gallery as $gimg): ?>
                                <div class="gallery-item">
                                    <img src="<?php echo SITE_URL . '/' . htmlspecialchars($gimg); ?>" 
                                         alt="Gallery image"
                                         style="border-radius: var(--radius-md);">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="news.php" class="btn btn-secondary">Back to Events</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
