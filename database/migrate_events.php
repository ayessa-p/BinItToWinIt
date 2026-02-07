<?php
require_once '../config/config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Add thumbnail_url column if it doesn't exist
    $db->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS thumbnail_url VARCHAR(500) AFTER image_url");
    echo "✓ Added thumbnail_url column<br>";
    
    // Add gallery_json column if it doesn't exist
    $db->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS gallery_json TEXT AFTER thumbnail_url");
    echo "✓ Added gallery_json column<br>";
    
    echo "<br><strong style='color: green;'>Migration completed successfully!</strong>";
} catch (Exception $e) {
    echo "<strong style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</strong>";
}
?>
