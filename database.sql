-- ============================================
-- DevShowcase Database Schema
-- MySQL Database Creation Script
-- ============================================

-- Create database (uncomment if needed)
-- CREATE DATABASE IF NOT EXISTS devshowcase_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE devshowcase_db;

-- ============================================
-- Users Table
-- Stores user account information
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL COMMENT 'Hashed password using password_hash()',
    `first_name` VARCHAR(50) DEFAULT NULL,
    `last_name` VARCHAR(50) DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    `github_url` VARCHAR(255) DEFAULT NULL,
    `profile_photo` VARCHAR(255) DEFAULT NULL COMMENT 'Path to profile photo',
    `job_title` VARCHAR(100) DEFAULT NULL,
    `skills` TEXT DEFAULT NULL COMMENT 'JSON array of skills',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_email` (`email`),
    INDEX `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Projects Table
-- Stores user projects
-- ============================================
CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `technologies` TEXT DEFAULT NULL COMMENT 'Comma-separated or JSON array of technologies',
    `github_url` VARCHAR(255) DEFAULT NULL,
    `screenshot` VARCHAR(255) DEFAULT NULL COMMENT 'Path to project screenshot',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Documents Table
-- Stores uploaded documents
-- ============================================
CREATE TABLE IF NOT EXISTS `documents` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `file_path` VARCHAR(255) NOT NULL COMMENT 'Path to uploaded file',
    `file_type` VARCHAR(50) NOT NULL COMMENT 'File extension or MIME type',
    `file_size` INT(11) DEFAULT NULL COMMENT 'File size in bytes',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Sample Data (Optional - for testing)
-- ============================================
-- Password for test user: password123 (hashed)
-- INSERT INTO `users` (`username`, `email`, `password`, `first_name`, `last_name`, `bio`, `github_url`, `job_title`, `skills`) 
-- VALUES 
-- ('johndoe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', 'I am a passionate developer', 'https://github.com/johndoe', 'Full Stack Developer', '["JavaScript", "React", "Node.js", "PHP"]');

