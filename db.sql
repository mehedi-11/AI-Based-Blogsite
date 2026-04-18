-- Recreate database from scratch
DROP DATABASE IF EXISTS ai_blogsite;
CREATE DATABASE ai_blogsite;
USE ai_blogsite;

-- Settings Table (Configured by admin)
CREATE TABLE settings (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `key_name` VARCHAR(50) NOT NULL UNIQUE,
    `value` TEXT NOT NULL
);

-- Default Settings
INSERT INTO settings (`key_name`, `value`) VALUES
('site_name', 'AI Blogsite By Mehedi'),
('logo_url', '/AI-Based-Blogsite/assets/images/logo.png'),
('theme_mode', 'dark'), 
('accent_color', '#3b82f6'),
('bg_color', '#0f172a');

-- Users Table (Super Admins, Admins, Authors)
CREATE TABLE users (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `role` ENUM('super_admin', 'admin', 'author') DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Permissions Table (RBAC Mapping)
CREATE TABLE admin_permissions (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `module` VARCHAR(50) NOT NULL, -- e.g., 'dashboard', 'settings', 'users', 'ai_writer', 'posts'
    FOREIGN KEY (`user_id`) REFERENCES users(`id`) ON DELETE CASCADE
);

-- Default Super Admin (Password: admin123)
-- $2y$10$7rls.66.pZjx3R7X9XyG3.j5fVp8Yp6k9j5fVp8Yp6k9j5fVp8Yp6
INSERT INTO users (`id`, `username`, `password`, `email`, `role`) VALUES 
(1, 'admin', '$2y$10$7rls.66.pZjx3R7X9XyG3.j5fVp8Yp6k9j5fVp8Yp6k9j5fVp8Yp6', 'admin@example.com', 'super_admin');

-- Super Admin gets all permissions implicitly in code, but we can assign them explicitly for safety
INSERT INTO admin_permissions (`user_id`, `module`) VALUES 
(1, 'dashboard'), (1, 'settings'), (1, 'users'), (1, 'ai_writer'), (1, 'posts');

-- Categories Table
CREATE TABLE categories (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `slug` VARCHAR(100) NOT NULL UNIQUE
);

-- Posts Table
CREATE TABLE posts (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `content` TEXT NOT NULL,
    `excerpt` TEXT,
    `featured_image` VARCHAR(255),
    `category_id` INT,
    `author_id` INT,
    `status` ENUM('draft', 'published') DEFAULT 'published',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES categories(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`author_id`) REFERENCES users(`id`) ON DELETE SET NULL
);

-- Default Categories
INSERT INTO categories (`name`, `slug`) VALUES 
('Technology', 'technology'), 
('AI & Ethics', 'ai-ethics'), 
('Future Tech', 'future-tech');
