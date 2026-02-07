-- MTICS Database Schema
-- Manila Technician Institute Computer Society

CREATE DATABASE IF NOT EXISTS binittowinit;
USE binittowinit;

-- Users table for students
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    course VARCHAR(100),
    year_level VARCHAR(20),
    eco_tokens DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_student_id (student_id),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transactions table for tracking token activities
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    transaction_type ENUM('earned', 'redeemed', 'admin_adjustment') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    description TEXT,
    related_reward_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recycling activities table
CREATE TABLE IF NOT EXISTS recycling_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sensor_id VARCHAR(50),
    bottle_type VARCHAR(50),
    tokens_earned DECIMAL(10, 2) NOT NULL,
    device_timestamp DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rewards table
CREATE TABLE IF NOT EXISTS rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    token_cost DECIMAL(10, 2) NOT NULL,
    category VARCHAR(100),
    stock_quantity INT DEFAULT -1, -- -1 means unlimited
    is_active BOOLEAN DEFAULT TRUE,
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Redemptions table
CREATE TABLE IF NOT EXISTS redemptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reward_id INT NOT NULL,
    tokens_spent DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'approved', 'fulfilled', 'cancelled') DEFAULT 'pending',
    redemption_code VARCHAR(50) UNIQUE,
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fulfilled_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE RESTRICT,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_redemption_code (redemption_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API keys for ESP32 devices
CREATE TABLE IF NOT EXISTS api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id VARCHAR(100) UNIQUE NOT NULL,
    api_key VARCHAR(255) UNIQUE NOT NULL,
    device_name VARCHAR(255),
    location VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used TIMESTAMP NULL,
    INDEX idx_api_key (api_key),
    INDEX idx_device_id (device_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- News and updates table
CREATE TABLE IF NOT EXISTS news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    author VARCHAR(255),
    image_url VARCHAR(500),
    is_published BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_published (is_published),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default rewards
INSERT INTO rewards (name, description, token_cost, category, stock_quantity) VALUES
('Printing Credits (10 pages)', '10 pages of printing credits at the computer lab', 50.00, 'Services', -1),
('Printing Credits (20 pages)', '20 pages of printing credits at the computer lab', 90.00, 'Services', -1),
('Internet Access (1 hour)', '1 hour of premium internet access', 30.00, 'Services', -1),
('Internet Access (2 hours)', '2 hours of premium internet access', 55.00, 'Services', -1),
('MTICS T-Shirt', 'Official MTICS organization t-shirt', 200.00, 'Merchandise', 50),
('MTICS Sticker Pack', 'Set of official MTICS stickers', 25.00, 'Merchandise', 100),
('USB Flash Drive (16GB)', '16GB USB flash drive', 150.00, 'Electronics', 20),
('Wireless Mouse', 'Ergonomic wireless mouse', 180.00, 'Electronics', 15);

-- Insert sample news
INSERT INTO news (title, content, author, is_published) VALUES
('Welcome to Bin It to Win It!', 'We are excited to launch our new recycling initiative that rewards students for their environmental efforts. Start recycling today and earn Eco-Tokens!', 'MTICS Admin', TRUE),
('MTICS Annual General Meeting', 'Join us for our annual general meeting on March 15th. We will discuss upcoming projects and initiatives for the semester.', 'MTICS Officers', TRUE);

-- Create events table for admin management
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATETIME,
    location VARCHAR(255),
    image_url VARCHAR(500),
    thumbnail_url VARCHAR(500),
    gallery_json TEXT,
    is_published BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_event_date (event_date),
    INDEX idx_is_published (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create projects table for admin management
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

-- Add is_admin field to users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin BOOLEAN DEFAULT FALSE;

-- Create default admin account
-- Username: mtics.official, Password: mticstuptaguig
INSERT INTO users (student_id, email, password_hash, full_name, is_admin, is_active, eco_tokens)
VALUES (
    'mtics.official',
    'mtics.official@mtics.edu.ph',
    '$2y$10$x6jIuYXyhc6V477JM0iNkuxhmRfinCK5D8BcRxW.yUCbT0nKPNPqq', -- mticstuptaguig
    'MTICS Official Admin',
    1,
    1,
    0.00
)
ON DUPLICATE KEY UPDATE 
    is_admin = 1,
    is_active = 1;
