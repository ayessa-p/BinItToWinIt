-- =====================================================
-- MTICS - Bin It to Win It
-- Complete Database Setup
-- =====================================================

-- Create database
CREATE DATABASE IF NOT EXISTS binittowinit;
USE binittowinit;

-- =====================================================
-- USERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) NULL,
    course VARCHAR(100),
    year_level VARCHAR(20),
    eco_tokens DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_admin BOOLEAN DEFAULT FALSE,
    INDEX idx_student_id (student_id),
    INDEX idx_email (email),
    INDEX idx_is_admin (is_admin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TRANSACTIONS TABLE
-- =====================================================
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
    INDEX idx_created_at (created_at),
    INDEX idx_transaction_type (transaction_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- RECYCLING ACTIVITIES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS recycling_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sensor_id VARCHAR(50),
    bottle_type VARCHAR(50),
    tokens_earned DECIMAL(10, 2) NOT NULL,
    device_timestamp DATETIME,
    device_id VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_sensor_id (sensor_id),
    INDEX idx_device_id (device_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- REWARDS TABLE
-- =====================================================
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
    INDEX idx_is_active (is_active),
    INDEX idx_token_cost (token_cost)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- REDEMPTIONS TABLE
-- =====================================================
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
    INDEX idx_redemption_code (redemption_code),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- API KEYS TABLE
-- =====================================================
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
    INDEX idx_device_id (device_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- NEWS TABLE
-- =====================================================
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

-- =====================================================
-- EVENTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATETIME,
    location VARCHAR(255),
    image_url VARCHAR(500),
    is_published BOOLEAN DEFAULT FALSE,
    participant_count INT DEFAULT 0,
    attendance_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_event_date (event_date),
    INDEX idx_is_published (is_published),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PROJECTS TABLE
-- =====================================================
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
    INDEX idx_is_featured (is_featured),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ORG OFFICERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS org_officers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    position VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DEFAULT DATA
-- =====================================================

-- Insert sample projects
INSERT INTO projects (name, description, status, image_url, is_featured) VALUES
('Tech Workshops', 'Regular workshops covering programming, web development, cybersecurity, and emerging technologies.', 'active', NULL, FALSE),
('Hackathons & Competitions', 'Coding competitions and hackathons where students can showcase their skills and win prizes.', 'active', NULL, TRUE),
('Community Outreach', 'Technology education programs for local communities focusing on digital literacy and computer skills.', 'active', NULL, FALSE),
('Research & Development', 'Collaborative research projects exploring innovative applications of technology in education and sustainability.', 'active', NULL, FALSE);

-- Insert default rewards
INSERT INTO rewards (name, description, token_cost, category, stock_quantity) VALUES
('MTICS Shirt', 'Official MTICS organization t-shirt', 200.00, 'Merchandise', 50),
('MTICS Sticker Pack', 'Set of official MTICS stickers', 25.00, 'Merchandise', 100),
('USB Flash Drive (16GB)', '16GB USB flash drive', 150.00, 'Electronics', 20),
('Wireless Mouse', 'Ergonomic wireless mouse', 180.00, 'Electronics', 15);

-- Insert sample news
INSERT INTO news (title, content, author, is_published) VALUES
('Welcome to Bin It to Win It!', 'We are excited to launch our new recycling initiative that rewards students for their environmental efforts. Start recycling today and earn Eco-Tokens!', 'MTICS Admin', TRUE),
('MTICS Annual General Meeting', 'Join us for our annual general meeting on March 15th. We will discuss upcoming projects and initiatives for the semester.', 'MTICS Officers', TRUE);

-- Seed initial officers (including Adviser)
INSERT INTO org_officers (full_name, position, display_order, is_active) VALUES
('Prof. Pops V. Madriaga', 'MTICS Adviser', 0, TRUE),
('Juan Dela Cruz', 'Chief Executive President', 1, TRUE),
('Maria Santos', 'Chief Executive Vice President', 2, TRUE);

INSERT INTO users (student_id, email, password_hash, full_name, is_admin, is_active, eco_tokens)
VALUES (
    'mtics.official',
    'mtics.official@mtics.edu.ph',
    '$2y$10$x6jIuYXyhc6V477JM0iNkuxhmRfinCK5D8BcRxW.yUCbT0nKPNPqq',
    'MTICS Official Admin',
    1,
    1,
    0.00
)
ON DUPLICATE KEY UPDATE 
    is_admin = 1,
    is_active = 1,
    password_hash = '$2y$10$x6jIuYXyhc6V477JM0iNkuxhmRfinCK5D8BcRxW.yUCbT0nKPNPqq';

-- =====================================================
-- RESOURCE, SERVICE, AND AUTOMATION TABLES
-- =====================================================

USE binittowinit;

-- Equipment/Resources table
CREATE TABLE IF NOT EXISTS resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('equipment', 'facility', 'service', 'material') NOT NULL,
    type VARCHAR(100),
    total_quantity INT DEFAULT 0,
    available_quantity INT DEFAULT 0,
    location VARCHAR(255),
    condition_status ENUM('excellent', 'good', 'fair', 'poor') DEFAULT 'good',
    acquisition_date DATE,
    last_maintenance_date DATE,
    next_maintenance_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    requires_approval BOOLEAN DEFAULT FALSE,
    min_user_level ENUM('student', 'officer', 'admin') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_is_active (is_active),
    INDEX idx_available_quantity (available_quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default borrowable equipment
INSERT INTO resources (name, description, category, type, total_quantity, available_quantity, location, condition_status, is_active, requires_approval, min_user_level) VALUES
('Projector', 'Standard multimedia projector', 'equipment', 'borrowable', 3, 3, 'MTICS Office', 'good', TRUE, TRUE, 'student'),
('HDMI Cable', 'High‑speed HDMI cable', 'equipment', 'borrowable', 10, 10, 'MTICS Office', 'excellent', TRUE, FALSE, 'student'),
('VGA Cable', 'Legacy VGA display cable', 'equipment', 'borrowable', 5, 5, 'MTICS Office', 'good', TRUE, FALSE, 'student'),
('Power Extension Cord', '4‑socket power extension cord', 'equipment', 'borrowable', 6, 6, 'MTICS Office', 'good', TRUE, TRUE, 'student'),
('Audio Speaker', 'Portable audio speaker set', 'equipment', 'borrowable', 2, 2, 'MTICS Office', 'good', TRUE, TRUE, 'student'),
('Microphone', 'Wired microphone for events', 'equipment', 'borrowable', 4, 4, 'MTICS Office', 'good', TRUE, TRUE, 'student'),
('Whiteboard', 'Portable whiteboard', 'equipment', 'borrowable', 2, 2, 'MTICS Office', 'good', TRUE, FALSE, 'student'),
('Marker Set', 'Set of whiteboard markers', 'equipment', 'borrowable', 8, 8, 'MTICS Office', 'good', TRUE, FALSE, 'student'),
('Stapler', 'Standard office stapler', 'equipment', 'borrowable', 4, 4, 'MTICS Office', 'good', TRUE, FALSE, 'student');

-- Default rooms (facility -> room)
INSERT INTO resources (name, description, category, type, total_quantity, available_quantity, location, condition_status, is_active, requires_approval, min_user_level) VALUES
('RM101', 'IT Building Room 101', 'facility', 'room', 1, 1, 'IT Building', 'good', TRUE, TRUE, 'student'),
('RM102', 'IT Building Room 102', 'facility', 'room', 1, 1, 'IT Building', 'good', TRUE, TRUE, 'student'),
('RM103', 'IT Building Room 103', 'facility', 'room', 1, 1, 'IT Building', 'good', TRUE, TRUE, 'student'),
('RM201', 'IT Building Room 201', 'facility', 'room', 1, 1, 'IT Building', 'good', TRUE, TRUE, 'student'),
('RM202', 'IT Building Room 202', 'facility', 'room', 1, 1, 'IT Building', 'good', TRUE, TRUE, 'student'),
('RM203', 'IT Building Room 203', 'facility', 'room', 1, 1, 'IT Building', 'good', TRUE, TRUE, 'student'),
('RM301', 'IT Building Room 301', 'facility', 'room', 1, 1, 'IT Building', 'good', TRUE, TRUE, 'student'),
('RM302', 'IT Building Room 302', 'facility', 'room', 1, 1, 'IT Building', 'good', TRUE, TRUE, 'student');

-- Resource reservations table
CREATE TABLE IF NOT EXISTS resource_reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_id INT NOT NULL,
    user_id INT NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    purpose TEXT,
    status ENUM('pending', 'approved', 'rejected', 'cancelled', 'completed', 'no_show') DEFAULT 'pending',
    approval_notes TEXT,
    rejection_reason TEXT,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    auto_approval BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_resource_id (resource_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_start_date (start_date),
    INDEX idx_end_date (end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Room reservations table (IT Building rooms)
CREATE TABLE IF NOT EXISTS room_reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_code VARCHAR(20) NOT NULL,
    user_id INT NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    purpose TEXT,
    status ENUM('pending', 'approved', 'rejected', 'cancelled', 'completed', 'no_show') DEFAULT 'pending',
    approval_notes TEXT,
    rejection_reason TEXT,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_room_code (room_code),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_start_date (start_date),
    INDEX idx_end_date (end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Service requests table
CREATE TABLE IF NOT EXISTS service_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_type ENUM('printing', 'internet_access', 'equipment_borrowing', 'consultation', 'other') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    urgency ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled', 'rejected') DEFAULT 'pending',
    assigned_to INT NULL,
    estimated_completion_date DATE,
    actual_completion_date DATE,
    completion_notes TEXT,
    tokens_required DECIMAL(10, 2) DEFAULT 0.00,
    tokens_charged DECIMAL(10, 2) DEFAULT 0.00,
    file_path VARCHAR(500),
    auto_approval BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_service_type (service_type),
    INDEX idx_status (status),
    INDEX idx_urgency (urgency),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Printing services table
CREATE TABLE IF NOT EXISTS printing_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price_per_page DECIMAL(10, 2) NOT NULL,
    color_options ENUM('bw', 'color') DEFAULT 'bw',
    paper_size ENUM('a4', 'a3', 'legal', 'letter') DEFAULT 'a4',
    is_available BOOLEAN DEFAULT TRUE,
    unavailable_reason VARCHAR(255) NULL,
    unavailable_from DATETIME NULL,
    unavailable_to DATETIME NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active),
    INDEX idx_is_available (is_available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Internet access plans table
CREATE TABLE IF NOT EXISTS internet_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    duration_minutes INT NOT NULL,
    token_cost DECIMAL(10, 2) NOT NULL,
    speed_mbps INT,
    data_limit_mb INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Automation rules table
CREATE TABLE IF NOT EXISTS automation_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    rule_type ENUM('approval', 'notification', 'scheduling', 'pricing') NOT NULL,
    conditions JSON,
    actions JSON,
    is_active BOOLEAN DEFAULT TRUE,
    priority INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rule_type (rule_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Resource usage logs table
CREATE TABLE IF NOT EXISTS resource_usage_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_id INT NOT NULL,
    user_id INT NOT NULL,
    usage_type ENUM('reservation', 'checkout', 'maintenance', 'repair') NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME,
    notes TEXT,
    tokens_charged DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_resource_id (resource_id),
    INDEX idx_user_id (user_id),
    INDEX idx_usage_type (usage_type),
    INDEX idx_start_time (start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default printing services
INSERT INTO printing_services (name, description, price_per_page, color_options, paper_size, is_available) VALUES
('Black & White Printing', 'Standard black and white printing service', 1.00, 'bw', 'a4', TRUE),
('Color Printing', 'Color printing service for documents and presentations', 5.00, 'color', 'a4', TRUE),
('High Quality Printing', 'Premium quality printing for important documents', 8.00, 'color', 'a4', TRUE);

-- Default internet plans
INSERT INTO internet_plans (name, description, duration_minutes, token_cost, speed_mbps, data_limit_mb) VALUES
('Basic Internet Access', '1 hour basic internet access for research and browsing', 60, 30.00, 10, 1000),
('Premium Internet Access', 'High-speed internet access for downloads and streaming', 120, 50.00, 50, 5000),
('Extended Internet Access', 'Full day internet access for projects and assignments', 480, 150.00, 100, 10000);

-- Default automation rules
INSERT INTO automation_rules (rule_name, rule_type, conditions, actions, priority) VALUES
('Auto-approve low-risk reservations', 'approval', 
 '{"max_duration_hours": 2, "user_level": "student", "resource_category": "equipment"}',
 '{"auto_approve": true, "notify_admin": false}', 1),
('Auto-charge printing services', 'pricing',
 '{"service_type": "printing", "user_level": "student"}',
 '{"charge_tokens": true, "use_standard_pricing": true}', 2),
('Auto-notify high urgency requests', 'notification',
 '{"urgency": "high", "service_type": "equipment_repair"}',
 '{"send_immediate_notification": true, "notify_admins": true}', 3);

-- Views for reporting
CREATE OR REPLACE VIEW resource_utilization AS
SELECT 
    r.id,
    r.name,
    r.category,
    r.total_quantity,
    r.available_quantity,
    COUNT(rr.id) as total_reservations,
    COUNT(CASE WHEN rr.status = 'approved' THEN 1 END) as approved_reservations,
    AVG(TIMESTAMPDIFF(HOUR, rr.start_date, rr.end_date)) as avg_duration_hours
FROM resources r
LEFT JOIN resource_reservations rr ON r.id = rr.resource_id
WHERE r.is_active = true
GROUP BY r.id, r.name, r.category, r.total_quantity, r.available_quantity;

CREATE OR REPLACE VIEW service_request_stats AS
SELECT 
    sr.service_type,
    COUNT(*) as total_requests,
    COUNT(CASE WHEN sr.status = 'completed' THEN 1 END) as completed_requests,
    COUNT(CASE WHEN sr.status = 'pending' THEN 1 END) as pending_requests,
    AVG(TIMESTAMPDIFF(HOUR, sr.created_at, COALESCE(sr.actual_completion_date, NOW()))) as avg_completion_hours,
    SUM(sr.tokens_charged) as total_tokens_charged
FROM service_requests sr
GROUP BY sr.service_type;

-- =====================================================
-- CONTACT MESSAGES TABLES
-- =====================================================
CREATE TABLE IF NOT EXISTS contact_threads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    status ENUM('open', 'answered', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    sender_type ENUM('user','admin','guest') NOT NULL,
    user_id INT NULL,
    message_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES contact_threads(id) ON DELETE CASCADE,
    INDEX idx_thread_id (thread_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SENSOR READINGS TABLE 
-- =====================================================

CREATE TABLE IF NOT EXISTS sensor_readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id VARCHAR(50) NOT NULL,
    weight DECIMAL(10,2) NOT NULL,
    distance DECIMAL(10,2) NOT NULL,
    is_metal BOOLEAN NOT NULL DEFAULT FALSE,
    accepted BOOLEAN NOT NULL DEFAULT FALSE,
    reading_time DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_device_time (device_id, reading_time),
    INDEX idx_accepted (accepted),
    INDEX idx_created (created_at)
);

-- =====================================================
-- EVENT ATTENDANCE TABLES 
-- =====================================================

USE binittowinit;

-- Event attendance table
CREATE TABLE IF NOT EXISTS event_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    proof_image VARCHAR(500),
    attendance_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    tokens_awarded DECIMAL(10, 2) DEFAULT 0.00,
    admin_notes TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    reviewed_by INT NULL,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_event_user (event_id, user_id),
    INDEX idx_event_id (event_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (attendance_status),
    INDEX idx_submitted_at (submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- View for event statistics
CREATE OR REPLACE VIEW event_statistics AS
SELECT 
    e.id,
    e.title,
    e.event_date,
    COUNT(DISTINCT ea.user_id) as total_attendees,
    COUNT(DISTINCT CASE WHEN ea.attendance_status = 'approved' THEN ea.user_id END) as approved_attendees,
    COUNT(DISTINCT CASE WHEN ea.attendance_status = 'pending' THEN ea.user_id END) as pending_attendees,
    SUM(CASE WHEN ea.attendance_status = 'approved' THEN ea.tokens_awarded ELSE 0 END) as total_tokens_awarded
FROM events e
LEFT JOIN event_attendance ea ON e.id = ea.event_id
GROUP BY e.id, e.title, e.event_date;

-- =====================================================
-- Default Admin Login:
--   Student ID/Email: mtics.official
--   Password: mticstuptaguig
-- =====================================================
