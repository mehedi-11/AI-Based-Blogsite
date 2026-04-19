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
('logo_url', 'assets/images/logo.png'),
('theme_mode', 'dark'), 
('accent_color', '#3b82f6'),
('bg_color', '#0f172a');

-- Users Table (Super Admins, Admins, Authors)
CREATE TABLE users (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `phone` VARCHAR(20) UNIQUE,
    `role` ENUM('super_admin', 'admin', 'author') DEFAULT 'admin',
    `bio` TEXT DEFAULT NULL,
    `social_link` VARCHAR(255) DEFAULT NULL,
    `avatar` VARCHAR(255) DEFAULT NULL,
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
-- MD Mehedi Hasan | Mehedi19 | mehedi@gmail.com | 0123654789 | 15253545
-- $2y$10$DrTn4Ql2mBuJfcud881BPeB4bPmEsSMkfwjLSmzysvpwFPd2q5F6i
INSERT INTO users (`id`, `name`, `username`, `password`, `email`, `phone`, `role`) VALUES 
(1, 'MD Mehedi Hasan', 'Mehedi19', '$2y$10$DrTn4Ql2mBuJfcud881BPeB4bPmEsSMkfwjLSmzysvpwFPd2q5F6i', 'mehedi@gmail.com', '0123654789', 'super_admin');

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
    `seo_keywords` VARCHAR(255),
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
('Technology', 'technology'), 
('AI & Ethics', 'ai-ethics'), 
('Future Tech', 'future-tech');

-- Comments Table
CREATE TABLE comments (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `post_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `content` TEXT NOT NULL,
    `status` ENUM('pending', 'approved') DEFAULT 'approved',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`post_id`) REFERENCES posts(`id`) ON DELETE CASCADE
);

-- Post Likes Table
CREATE TABLE post_likes (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `post_id` INT NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`post_id`) REFERENCES posts(`id`) ON DELETE CASCADE,
    UNIQUE KEY `one_like_per_ip` (`post_id`, `ip_address`)
);

-- Post Shares Table
CREATE TABLE post_shares (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `post_id` INT NOT NULL,
    `platform` VARCHAR(50) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`post_id`) REFERENCES posts(`id`) ON DELETE CASCADE
);
