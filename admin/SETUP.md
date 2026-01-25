# Admin Panel Setup Guide

## Initial Setup

### 1. Update Database Schema

Run the admin update SQL file to add admin functionality:

```sql
-- In phpMyAdmin, select the 'binittowinit' database and run:
-- File: database/admin_update.sql
```

Or manually run:

```sql
USE binittowinit;

-- Add is_admin field
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin BOOLEAN DEFAULT FALSE;

-- Create events table
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATETIME,
    location VARCHAR(255),
    image_url VARCHAR(500),
    is_published BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_event_date (event_date),
    INDEX idx_is_published (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create projects table
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    status VARCHAR(50) DEFAULT 'active',
    image_url VARCHAR(500),
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_is_featured (is_featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Create Default Admin Account

**Option 1: Use the Setup Script (Recommended)**
1. Navigate to: `http://localhost/BinItToWinIt/admin/create_admin.php`
2. The script will create the admin account automatically
3. **Delete the file after use for security**

**Option 2: Manual SQL (Alternative)**
Run this SQL after generating a password hash:
```sql
-- First, generate password hash using PHP:
-- <?php echo password_hash('mticstuptaguig', PASSWORD_DEFAULT); ?>
-- Then insert the user with the generated hash

INSERT INTO users (student_id, email, password_hash, full_name, is_admin, is_active, eco_tokens)
VALUES (
    'mtics.official',
    'mtics.official@mtics.edu.ph',
    'GENERATED_PASSWORD_HASH_HERE',
    'MTICS Official Admin',
    1,
    1,
    0.00
);
```

**Default Admin Credentials:**
- **Student ID/Email:** `mtics.official`
- **Password:** `mticstuptaguig`

**Note:** If you already have a user account, you can promote them to admin:
```sql
UPDATE users SET is_admin = 1 WHERE student_id = 'YOUR_STUDENT_ID';
```

### 3. Access Admin Panel

1. Log in with an admin account
2. Navigate to: `http://localhost/BinItToWinIt/admin/index.php`
3. Or use the "Admin Panel" link in the navigation (if visible)

## Admin Features

### Token Management
- Adjust user token balances
- Add or subtract tokens
- View all user balances
- Track token transactions

### Events Management
- Create and edit events
- Set event dates and locations
- Publish/unpublish events

### Projects Management
- Manage organization projects
- Set project status (active/completed/archived)
- Feature projects on homepage

### News Management
- Create and edit news articles
- Publish/unpublish news
- Manage news content

### Users Management
- View all users
- Activate/deactivate accounts
- Promote users to admin
- View user details

### Rewards Management
- Create and edit rewards
- Set token costs
- Manage stock quantities
- Activate/deactivate rewards

### API Keys Management
- Generate API keys for ESP32 devices
- View device information
- Activate/deactivate API keys
- Track API key usage

### Redemptions Management
- View all reward redemptions
- Approve pending redemptions
- Mark redemptions as fulfilled
- Cancel redemptions

## Security Notes

- Only users with `is_admin = 1` can access the admin panel
- All admin actions require CSRF token verification
- Admin functions are protected by `require_admin()` function
- Regular users cannot access admin routes
