<?php
/**
 * Database Installation Checker
 * Run this file to check if all required tables exist
 */

require_once 'config/config.php';

$missing_tables = [];
$existing_tables = [];
$required_tables = [
    'users',
    'transactions',
    'recycling_activities',
    'rewards',
    'redemptions',
    'api_keys',
    'news'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Installation Check - MTICS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #0a1a2e;
            color: #fff;
        }
        .card {
            background: rgba(22, 36, 71, 0.8);
            border: 1px solid #3d7fc7;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .success { color: #90ee90; }
        .error { color: #ff6b6b; }
        .warning { color: #ffd700; }
        h1 { color: #5ba3e8; }
        h2 { color: #3d7fc7; margin-top: 0; }
        code {
            background: rgba(0, 0, 0, 0.3);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3d7fc7;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        .btn:hover { background: #2a5f8f; }
        ul { line-height: 1.8; }
    </style>
</head>
<body>
    <h1>🔍 Database Installation Check</h1>
    
    <?php
    try {
        $db = Database::getInstance()->getConnection();
        $db_name = DB_NAME;
        
        echo "<div class='card'>";
        echo "<h2>Database Connection</h2>";
        echo "<p class='success'>✓ Successfully connected to database: <code>$db_name</code></p>";
        echo "</div>";
        
        // Check each table
        foreach ($required_tables as $table) {
            try {
                $stmt = $db->prepare("SELECT 1 FROM `$table` LIMIT 1");
                $stmt->execute();
                $existing_tables[] = $table;
            } catch (PDOException $e) {
                $missing_tables[] = $table;
            }
        }
        
        echo "<div class='card'>";
        echo "<h2>Table Status</h2>";
        
        if (empty($missing_tables)) {
            echo "<p class='success'>✓ All required tables exist!</p>";
            echo "<ul>";
            foreach ($existing_tables as $table) {
                echo "<li class='success'>✓ <code>$table</code> - OK</li>";
            }
            echo "</ul>";
            echo "<p style='margin-top: 20px;'><strong>Your database is properly set up!</strong></p>";
        } else {
            echo "<p class='error'>✗ Some tables are missing. Please import the database schema.</p>";
            echo "<h3 style='color: #ff6b6b;'>Missing Tables:</h3>";
            echo "<ul>";
            foreach ($missing_tables as $table) {
                echo "<li class='error'>✗ <code>$table</code> - MISSING</li>";
            }
            echo "</ul>";
            
            if (!empty($existing_tables)) {
                echo "<h3 style='color: #90ee90;'>Existing Tables:</h3>";
                echo "<ul>";
                foreach ($existing_tables as $table) {
                    echo "<li class='success'>✓ <code>$table</code> - OK</li>";
                }
                echo "</ul>";
            }
            
            echo "<div style='background: rgba(255, 215, 0, 0.1); padding: 15px; border-radius: 4px; margin-top: 20px; border-left: 4px solid #ffd700;'>";
            echo "<h3 class='warning'>How to Fix:</h3>";
            echo "<ol style='line-height: 2;'>";
            echo "<li>Open phpMyAdmin: <code>http://localhost/phpmyadmin</code></li>";
            echo "<li>Select the database: <code>$db_name</code></li>";
            echo "<li>Click the <strong>Import</strong> tab</li>";
            echo "<li>Choose file: <code>database/tables_only.sql</code></li>";
            echo "<li>Click <strong>Go</strong> to import</li>";
            echo "</ol>";
            echo "<p style='margin-top: 15px;'><strong>Or use the SQL tab:</strong></p>";
            echo "<ol style='line-height: 2;'>";
            echo "<li>Select database: <code>$db_name</code></li>";
            echo "<li>Click the <strong>SQL</strong> tab</li>";
            echo "<li>Open and copy all content from <code>database/tables_only.sql</code></li>";
            echo "<li>Paste into the SQL textarea and click <strong>Go</strong></li>";
            echo "</ol>";
            echo "</div>";
        }
        
        echo "</div>";
        
    } catch (PDOException $e) {
        echo "<div class='card'>";
        echo "<h2>Database Connection Error</h2>";
        echo "<p class='error'>✗ Failed to connect to database</p>";
        echo "<p>Error: <code>" . htmlspecialchars($e->getMessage()) . "</code></p>";
        echo "<p>Please check your database configuration in <code>config/database.php</code></p>";
        echo "</div>";
    }
    ?>
    
    <div class="card">
        <h2>Next Steps</h2>
        <?php if (empty($missing_tables)): ?>
            <p class="success">Your database is ready! You can now:</p>
            <ul>
                <li><a href="index.php" class="btn">Visit Homepage</a></li>
                <li><a href="auth/register.php" class="btn">Create an Account</a></li>
            </ul>
        <?php else: ?>
            <p>After importing the database schema, refresh this page to verify the installation.</p>
            <p><a href="install_check.php" class="btn">Refresh Check</a></p>
        <?php endif; ?>
    </div>
    
    <div style="text-align: center; margin-top: 30px; color: #a0a0a0; font-size: 0.9rem;">
        <p>MTICS - Bin It to Win It</p>
        <p><small>You can delete this file after installation is complete.</small></p>
    </div>
</body>
</html>
