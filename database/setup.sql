-- Business of Ghanaian Fashion Database Setup
-- This script creates the necessary tables for the BoGF website

-- Create database (uncomment if needed)
-- CREATE DATABASE bogf_website CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE bogf_website;

-- Create admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    role ENUM('super_admin', 'admin', 'editor') DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create speakers table
CREATE TABLE IF NOT EXISTS speakers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    title VARCHAR(150),
    organization VARCHAR(150),
    bio TEXT,
    image_url VARCHAR(255),
    category ENUM('keynote', 'panelist', 'moderator', 'roundtable', 'host', 'guest') DEFAULT 'panelist',
    expertise TEXT, -- Comma-separated expertise areas
    linkedin_url VARCHAR(255),
    twitter_url VARCHAR(255),
    website_url VARCHAR(255),
    session_title VARCHAR(200),
    session_description TEXT,
    session_time VARCHAR(50),
    is_featured BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_category (category),
    INDEX idx_is_active (is_active),
    INDEX idx_is_featured (is_featured)
);

-- Create summit applications table
CREATE TABLE IF NOT EXISTS summit_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    organization VARCHAR(150),
    job_title VARCHAR(100),
    country VARCHAR(50),
    city VARCHAR(50),
    industry_sector VARCHAR(100),
    years_experience INT,
    application_type ENUM('attendee', 'speaker', 'sponsor', 'media', 'student') DEFAULT 'attendee',
    motivation TEXT, -- Why they want to attend
    expectations TEXT, -- What they hope to gain
    contribution TEXT, -- How they can contribute
    dietary_requirements TEXT,
    accessibility_needs TEXT,
    how_heard_about VARCHAR(100), -- How they heard about the summit
    marketing_consent BOOLEAN DEFAULT FALSE,
    status ENUM('pending', 'reviewing', 'approved', 'rejected', 'waitlist') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'waived', 'refunded') DEFAULT 'pending',
    payment_reference VARCHAR(100),
    admin_notes TEXT,
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewed_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_email (email),
    INDEX idx_application_type (application_type),
    INDEX idx_applied_at (applied_at)
);

-- Create newsletter subscribers table
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    subscription_source VARCHAR(50) DEFAULT 'website', -- website, summit, event, etc.
    is_active BOOLEAN DEFAULT TRUE,
    unsubscribe_token VARCHAR(100) UNIQUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_is_active (is_active)
);

-- Create contact messages table
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    organization VARCHAR(150),
    subject VARCHAR(200),
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
    admin_response TEXT,
    responded_by INT,
    responded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (responded_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Create partnership inquiries table
CREATE TABLE IF NOT EXISTS partnership_inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    organization VARCHAR(150) NOT NULL,
    job_title VARCHAR(100),
    country VARCHAR(50),
    partnership_type ENUM('sponsor_summit', 'fund_programming', 'strategic_partner', 'other') NOT NULL,
    message TEXT,
    status ENUM('new', 'reviewing', 'contacted', 'proposal_sent', 'negotiating', 'confirmed', 'declined') DEFAULT 'new',
    estimated_value DECIMAL(10, 2),
    admin_notes TEXT,
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_partnership_type (partnership_type)
);

-- Create admin activity log table
CREATE TABLE IF NOT EXISTS admin_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- Create website settings table
CREATE TABLE IF NOT EXISTS website_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Insert default admin user (password: admin123!)
-- Note: In production, use a secure password and change immediately
INSERT INTO admin_users (username, email, password_hash, first_name, last_name, role) 
VALUES (
    'admin', 
    'admin@fashionnexusghana.com', 
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewdBPj6ukPs4/fJC', -- admin123!
    'Admin', 
    'User', 
    'super_admin'
) ON DUPLICATE KEY UPDATE username = username;

-- Insert sample speakers (you can remove these after adding real speakers)
INSERT INTO speakers (name, title, organization, bio, category, expertise, is_featured, display_order) VALUES
(
    'Lulu Shabell',
    'Creative Director',
    'Independent Fashion Consultant',
    'Lulu Shabell is a renowned fashion creative director with over 15 years of experience in the African fashion industry. She has worked with major fashion weeks across Africa and has been instrumental in promoting African fashion on the global stage.\n\nHer work focuses on sustainable fashion practices and supporting emerging African designers. She has consulted for major fashion brands and has been featured in numerous international publications.',
    'keynote',
    'Creative Direction, Sustainable Fashion, African Fashion, Brand Strategy',
    TRUE,
    1
),
(
    'Sara Sozzani Mainoo',
    'Fashion Editor & Creative Consultant',
    'Vogue Italia',
    'Sara Sozzani Mainoo is an influential fashion editor and creative consultant known for her work in promoting African fashion and designers on international platforms. She has been pivotal in bridging the gap between African fashion and global luxury markets.\n\nWith her extensive network and keen eye for talent, Sara has helped numerous African designers gain international recognition and commercial success.',
    'keynote',
    'Fashion Editorial, Global Markets, Luxury Fashion, Cultural Bridge-building',
    TRUE,
    2
),
(
    'Steve French',
    'CEO',
    'Curve Fashion Agency',
    'Steve French is a leading figure in fashion business development and brand strategy. As CEO of Curve Fashion Agency, he has helped scale numerous fashion brands and has deep expertise in fashion investment and market expansion.\n\nHis insights into the business of fashion, particularly in emerging markets, make him a sought-after speaker and consultant for fashion entrepreneurs looking to scale their businesses.',
    'panelist',
    'Business Development, Brand Strategy, Fashion Investment, Market Expansion',
    TRUE,
    3
),
(
    'Mimi Plange',
    'Creative Director',
    'Mimi Plange Studio',
    'Mimi Plange is an acclaimed Ghanaian-American fashion designer known for her innovative approach to contemporary African fashion. Her work has been featured in major fashion weeks and worn by celebrities worldwide.\n\nShe is passionate about ethical fashion production and has been working to create sustainable supply chains that benefit local communities while maintaining high fashion standards.',
    'panelist',
    'Fashion Design, Contemporary African Fashion, Ethical Production, Supply Chain',
    TRUE,
    4
);

-- Insert default website settings
INSERT INTO website_settings (setting_key, setting_value, setting_type, description) VALUES
('summit_date', '2025-10-16', 'text', 'Summit date'),
('summit_venue', 'Kempinski Hotel Gold Coast City, Accra', 'text', 'Summit venue'),
('registration_open', 'true', 'boolean', 'Whether registration is open'),
('max_attendees', '300', 'number', 'Maximum number of attendees'),
('early_bird_deadline', '2025-08-15', 'text', 'Early bird registration deadline'),
('regular_price', '150', 'number', 'Regular ticket price in USD'),
('early_bird_price', '120', 'number', 'Early bird ticket price in USD'),
('student_price', '75', 'number', 'Student ticket price in USD')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- Create indexes for better performance
CREATE INDEX idx_speakers_category_active ON speakers (category, is_active);
CREATE INDEX idx_applications_status_type ON summit_applications (status, application_type);
CREATE INDEX idx_newsletter_active_source ON newsletter_subscribers (is_active, subscription_source);