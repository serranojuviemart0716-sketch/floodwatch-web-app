-- ========================================
-- FloodWatch Community Monitoring System
-- Database Setup SQL
-- ========================================

-- Create Database
CREATE DATABASE IF NOT EXISTS floodwatch_db;
USE floodwatch_db;

-- ========================================
-- Users Table
-- ========================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    fullName VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    barangay VARCHAR(100),
    is_active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Reports Table (for flood reports)
-- ========================================
CREATE TABLE IF NOT EXISTS reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    severity ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    status ENUM('Active', 'Resolved', 'Under Review') DEFAULT 'Active',
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Test/Demo User
-- Password: Demo1234
-- ========================================
INSERT INTO users (fullName, email, password_hash, barangay, is_active) 
VALUES (
    'Demo User',
    'demo@floodwatch.local',
    '$2y$10$N9qo8uLOickgx2ZMRZoMye6qmm5z5W1S7b5dEqR1g9O8x3d3D8W3S',
    'Barangay 1',
    1
);

-- ========================================
-- Sample Reports for Testing
-- ========================================
INSERT INTO reports (user_id, title, description, location, latitude, longitude, severity, status)
VALUES
(1, 'Flooding in Main Street', 'Heavy rain caused water to accumulate in the main commercial district', 'Main Street, Barangay 1', 14.5994, 120.9842, 'High', 'Active'),
(1, 'Flooding Near Market', 'Water level rising near public market', 'Public Market Area', 14.5995, 120.9843, 'Medium', 'Active'),
(1, 'Road Closure Alert', 'Road blocked due to flooding', 'Highway Junction', 14.5996, 120.9844, 'Critical', 'Under Review');

-- ========================================
-- Verify Installation
-- ========================================
-- Run these commands to verify:
-- SELECT * FROM users;
-- SELECT * FROM reports;
-- SELECT COUNT(*) as total_users FROM users;
