<?php
/**
 * Enhanced Database Setup with Additional Tables
 * File: database/enhanced_setup.sql
 */

-- Add rate limiting table
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier_time (identifier, created_at)
);

-- Add email queue table for better email handling
CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    to_email VARCHAR(255) NOT NULL,
    to_name VARCHAR(255),
    subject VARCHAR(500) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    scheduled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status_scheduled (status, scheduled_at)
);

-- Add application status history table
CREATE TABLE IF NOT EXISTS application_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    changed_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES summit_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Add website analytics table
CREATE TABLE IF NOT EXISTS website_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_url VARCHAR(500) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    referer VARCHAR(500),
    session_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_page_date (page_url(100), created_at),
    INDEX idx_session (session_id)
);

-- Enhanced indexes for better performance
ALTER TABLE summit_applications ADD INDEX idx_email_applied (email, applied_at);
ALTER TABLE summit_applications ADD INDEX idx_status_type_applied (status, application_type, applied_at);
ALTER TABLE admin_activity_log ADD INDEX idx_admin_action_date (admin_id, action, created_at);

-- Update existing data with better formatting
UPDATE summit_applications 
SET phone = CONCAT('+233 ', SUBSTRING(phone, 2))
WHERE phone LIKE '0%' AND LENGTH(phone) = 10;

?>