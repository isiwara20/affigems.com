-- =======================================================
-- ARIGATO.LK E-Commerce Database Setup
-- Complete Database Schema with all 30 required tables
-- Compatible with MySQL / MariaDB (phpMyAdmin)
-- =======================================================

CREATE DATABASE IF NOT EXISTS `arigato_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `arigato_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- =======================================================
-- 1. AUTHENTICATION & USERS
-- =======================================================

-- Users table (Customers)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(20) NULL,
    `password` VARCHAR(255) NOT NULL,
    `remember_token` VARCHAR(64) NULL,
    `points` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admins table
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('super_admin', 'admin') DEFAULT 'admin',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password Resets table
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(150) NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_password_resets_email` (`email`),
    INDEX `idx_password_resets_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login OTPs table (for SMS/Phone OTP authentication)
CREATE TABLE IF NOT EXISTS `login_otps` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `phone` VARCHAR(20) NOT NULL,
    `otp_hash` VARCHAR(255) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `attempts` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_login_otp_phone` (`phone`),
    INDEX `idx_login_otp_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Saved Addresses table
CREATE TABLE IF NOT EXISTS `user_saved_addresses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `label` VARCHAR(50) DEFAULT 'Home',
    `full_name` VARCHAR(100) NULL,
    `phone` VARCHAR(20) NULL,
    `address` VARCHAR(255) NOT NULL,
    `city` VARCHAR(100) NULL,
    `district` VARCHAR(100) NULL,
    `postal_code` VARCHAR(10) NULL,
    `is_default` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL,
    INDEX `idx_user_saved_addresses_user` (`user_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Points History table
CREATE TABLE IF NOT EXISTS `points_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `points` INT NOT NULL,
    `type` ENUM('earned', 'redeemed') DEFAULT 'earned',
    `description` VARCHAR(255) NULL,
    `order_id` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_points_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 2. CATALOG (CATEGORIES, SUBCATEGORIES, PRODUCTS)
-- =======================================================

-- Categories table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `image` VARCHAR(255) NULL,
    `sort_order` INT DEFAULT 0,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_categories_slug` (`slug`),
    INDEX `idx_categories_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Subcategories table
CREATE TABLE IF NOT EXISTS `subcategories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `image` VARCHAR(255) NULL,
    `sort_order` INT DEFAULT 0,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
    INDEX `idx_subcategories_category` (`category_id`),
    INDEX `idx_subcategories_slug` (`slug`),
    INDEX `idx_subcategories_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products table
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `subcategory_id` INT NULL DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `details` TEXT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `original_price` DECIMAL(10,2) NULL,
    `cost_price` DECIMAL(10,2) NULL,
    `stock` INT DEFAULT 0,
    `warranty_period` VARCHAR(20) NULL DEFAULT NULL,
    `image` VARCHAR(255) NULL,
    `images` TEXT NULL,
    `author` VARCHAR(100) DEFAULT 'ARIGATO.LK',
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `featured` TINYINT(1) DEFAULT 0,
    `sort_order` INT DEFAULT 0,
    `has_variations` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories`(`id`) ON DELETE SET NULL,
    INDEX `idx_products_category` (`category_id`),
    INDEX `idx_products_subcategory` (`subcategory_id`),
    INDEX `idx_products_slug` (`slug`),
    INDEX `idx_products_status` (`status`),
    INDEX `idx_products_featured` (`featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product variation options (e.g. Size, Color, Storage)
CREATE TABLE IF NOT EXISTS `product_variation_options` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `option_name` VARCHAR(100) NOT NULL,
    `option_values` TEXT NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    INDEX `idx_product_variation_options_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product variations (SKU, price, stock combination)
CREATE TABLE IF NOT EXISTS `product_variations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `sku` VARCHAR(100) NULL,
    `options_json` TEXT NOT NULL,
    `options_label` VARCHAR(255) NOT NULL,
    `price` DECIMAL(10,2) NULL,
    `stock` INT NOT NULL DEFAULT 0,
    `image` VARCHAR(255) NULL,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    INDEX `idx_product_variations_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mega / Hot Deals table
CREATE TABLE IF NOT EXISTS `mega_deals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL UNIQUE,
    `deal_price` DECIMAL(10,2) NOT NULL,
    `original_price` DECIMAL(10,2) NULL,
    `start_date` DATE NULL,
    `end_date` DATE NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    INDEX `idx_mega_deals_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reviews table
CREATE TABLE IF NOT EXISTS `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `user_name` VARCHAR(100) NOT NULL,
    `rating` INT NOT NULL DEFAULT 5,
    `comment` TEXT NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_reviews_product` (`product_id`),
    INDEX `idx_reviews_user` (`user_id`),
    INDEX `idx_reviews_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Restock notification subscribers
CREATE TABLE IF NOT EXISTS `restock_notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `variation_key` VARCHAR(255) NOT NULL DEFAULT '',
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(20) NULL,
    `user_id` INT NULL,
    `notified_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_restock_notify` (`product_id`, `variation_key`, `email`),
    INDEX `idx_product_pending` (`product_id`, `notified_at`),
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 3. SHOPPING CART, WISHLIST, COUPONS
-- =======================================================

-- Shopping Cart table
CREATE TABLE IF NOT EXISTS `cart` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `variation_id` INT NOT NULL DEFAULT 0,
    `quantity` INT NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_cart_item` (`user_id`, `product_id`, `variation_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wishlist table
CREATE TABLE IF NOT EXISTS `wishlist` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_wishlist_item` (`user_id`, `product_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Coupons table
CREATE TABLE IF NOT EXISTS `coupons` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `discount_type` ENUM('percentage', 'fixed') DEFAULT 'percentage',
    `discount_value` DECIMAL(10,2) NOT NULL,
    `min_order` DECIMAL(10,2) DEFAULT 0,
    `max_uses` INT DEFAULT 0,
    `used_count` INT DEFAULT 0,
    `expires_at` DATE NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_coupons_code` (`code`),
    INDEX `idx_coupons_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 4. ORDERS & SERIAL NUMBERS
-- =======================================================

-- Orders table
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NULL,
    `phone` VARCHAR(20) NOT NULL,
    `shipping_country` VARCHAR(5) DEFAULT 'LK',
    `district` VARCHAR(50) NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `postal_code` VARCHAR(10) NULL,
    `address_1` VARCHAR(255) NOT NULL,
    `address_2` VARCHAR(255) NULL,
    `notes` TEXT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL,
    `discount` DECIMAL(10,2) DEFAULT 0,
    `delivery_fee` DECIMAL(10,2) DEFAULT 0,
    `total` DECIMAL(10,2) NOT NULL,
    `coupon_code` VARCHAR(50) NULL,
    `delivery_method` VARCHAR(20) DEFAULT 'standard',
    `payment_method` VARCHAR(30) DEFAULT 'cod',
    `payment_gateway` VARCHAR(30) NULL,
    `payment_surcharge` DECIMAL(10,2) DEFAULT 0,
    `payment_status` ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    `order_status` ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    `tracking_id` VARCHAR(120) NULL,
    `courier_company` VARCHAR(120) NULL,
    `points_earned` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_orders_user` (`user_id`),
    INDEX `idx_orders_number` (`order_number`),
    INDEX `idx_orders_status` (`order_status`),
    INDEX `idx_orders_payment_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Items table
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `product_name` VARCHAR(255) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `quantity` INT NOT NULL,
    `total` DECIMAL(10,2) NOT NULL,
    `variation_id` INT NULL,
    `variation_label` VARCHAR(255) NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    INDEX `idx_order_items_order` (`order_id`),
    INDEX `idx_order_items_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Item Serial Numbers
CREATE TABLE IF NOT EXISTS `order_item_serials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `order_item_id` INT NOT NULL,
    `unit_index` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `serial_number` VARCHAR(100) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_item_unit` (`order_item_id`, `unit_index`),
    UNIQUE KEY `uniq_serial` (`serial_number`),
    INDEX `idx_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 5. WARRANTY MANAGEMENT
-- =======================================================

-- Warranty registry (one row per purchased unit with serial number)
CREATE TABLE IF NOT EXISTS `warranty_registry` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `order_item_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `user_id` INT NULL,
    `invoice_number` VARCHAR(80) NOT NULL,
    `serial_number` VARCHAR(100) NOT NULL,
    `product_name` VARCHAR(255) NOT NULL,
    `warranty_period` VARCHAR(20) NOT NULL,
    `purchase_date` DATE NOT NULL,
    `expires_at` DATE NOT NULL,
    `unit_index` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_serial` (`serial_number`),
    INDEX `idx_invoice` (`invoice_number`),
    INDEX `idx_order` (`order_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_expires` (`expires_at`),
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Warranty claims
CREATE TABLE IF NOT EXISTS `warranty_claims` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `claim_number` VARCHAR(24) NOT NULL UNIQUE,
    `registry_id` INT NULL,
    `order_id` INT NULL,
    `user_id` INT NULL,
    `invoice_number` VARCHAR(80) NOT NULL,
    `invoice_date` DATE NULL,
    `serial_number` VARCHAR(100) NOT NULL,
    `product_name` VARCHAR(255) NOT NULL,
    `customer_name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(150) NULL,
    `phone` VARCHAR(30) NOT NULL,
    `issue_type` VARCHAR(150) NOT NULL,
    `issue_description` TEXT NOT NULL,
    `attachment` VARCHAR(255) NULL,
    `status` ENUM('pending','approved','replaced','rejected') NOT NULL DEFAULT 'pending',
    `admin_notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_claim_number` (`claim_number`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created` (`created_at`),
    FOREIGN KEY (`registry_id`) REFERENCES `warranty_registry`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 6. CMS, MESSAGING & COMMUNICATIONS
-- =======================================================

-- Contact messages submitted via Contact Us form
CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `reference_no` VARCHAR(24) NOT NULL UNIQUE,
    `full_name` VARCHAR(120) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `email` VARCHAR(150) NULL,
    `topic` VARCHAR(80) NOT NULL,
    `subject` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_contact_ref` (`reference_no`),
    INDEX `idx_contact_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Banners table
CREATE TABLE IF NOT EXISTS `banners` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NULL,
    `image` VARCHAR(255) NOT NULL,
    `link` VARCHAR(255) NULL,
    `sort_order` INT DEFAULT 0,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Testimonials table
CREATE TABLE IF NOT EXISTS `testimonials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NULL,
    `rating` INT NOT NULL DEFAULT 5,
    `message` TEXT NOT NULL,
    `order_number` VARCHAR(50) NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_testimonials_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Newsletter Subscribers table
CREATE TABLE IF NOT EXISTS `subscribers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `status` ENUM('active', 'unsubscribed') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_subscribers_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Site Settings table
CREATE TABLE IF NOT EXISTS `site_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat Conversations table
CREATE TABLE IF NOT EXISTS `chat_conversations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `guest_name` VARCHAR(100) NULL,
    `guest_email` VARCHAR(150) NULL,
    `status` ENUM('bot', 'awaiting_email', 'human_requested', 'human_active', 'closed') DEFAULT 'bot',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat Messages table
CREATE TABLE IF NOT EXISTS `chat_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT NOT NULL,
    `sender` ENUM('user', 'bot', 'admin') DEFAULT 'user',
    `message` TEXT NOT NULL,
    `message_type` ENUM('text', 'products', 'quick_replies') DEFAULT 'text',
    `metadata` JSON NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_conversation` (`conversation_id`),
    INDEX `idx_sender` (`sender`),
    FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMS Logs table
CREATE TABLE IF NOT EXISTS `sms_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `campaign_name` VARCHAR(150) NULL,
    `recipient` VARCHAR(20) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('sent','failed') NOT NULL DEFAULT 'sent',
    `error_message` VARCHAR(255) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_created` (`created_at`),
    INDEX `idx_campaign` (`campaign_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =======================================================
-- 7. DEFAULT & SEED DATA
-- =======================================================

-- Default Super Admin account:
-- Email: info@arigato.lk
-- Password: Admin@123
INSERT INTO `admins` (`name`, `email`, `password`, `role`) VALUES 
('ARIGATO.LK Admin', 'info@arigato.lk', '$2y$10$aUO5yA8qs2Kxc46vLoXnLu0tdlFEg89GydWBVQEin4pZrcLUohveO', 'super_admin')
ON DUPLICATE KEY UPDATE `password` = VALUES(`password`);

-- Default Site Settings
INSERT INTO `site_settings` (`setting_key`, `value`) VALUES 
('store_name', 'ARIGATO.LK'),
('store_phone', '077 899 8990 / 071 489 9899'),
('store_email', 'info@arigato.lk'),
('store_address', '779/3, Pannipitiya Road, Pelawatta, Battaramulla'),
('whatsapp_number', '94778998990'),
('low_stock_threshold', '5'),
('low_stock_email', 'info@arigato.lk'),
('order_notification_email', 'info@arigato.lk'),
('delivery_fee', '250'),
('free_delivery_threshold', '2500'),
('checkout_place_order_enabled', '1'),
('checkout_whatsapp_order_enabled', '1'),
('payment_cod_enabled', '1'),
('payment_card_enabled', '1'),
('mega_deals_enabled', '1'),
('sms_enabled', '0'),
('sms_api_provider', 'notify_lk'),
('sms_api_user_id', ''),
('sms_api_key', ''),
('sms_sender_id', ''),
('sms_shoutout_api_key', ''),
('sms_shoutout_source', ''),
('sms_order_placed_enabled', '1'),
('sms_order_status_enabled', '1'),
('sms_admin_new_order_enabled', '1'),
('sms_admin_low_stock_enabled', '1'),
('google_analytics_enabled', '0'),
('google_analytics_id', '')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- Default Categories
INSERT INTO `categories` (`id`, `name`, `slug`, `sort_order`, `status`) VALUES 
(1, 'Smartphones & Tablets', 'smartphones-tablets', 1, 'active'),
(2, 'Audio & Speakers', 'audio-speakers', 2, 'active'),
(3, 'Accessories', 'accessories', 3, 'active'),
(4, 'Wearables & Smartwatches', 'wearables-smartwatches', 4, 'active'),
(5, 'Laptops & Computers', 'laptops-computers', 5, 'active'),
(6, 'TVs & Home Entertainment', 'tvs-home-entertainment', 6, 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Default Subcategories
INSERT INTO `subcategories` (`id`, `category_id`, `name`, `slug`, `sort_order`, `status`) VALUES 
(1, 1, 'Apple iPhones', 'apple-iphones', 1, 'active'),
(2, 1, 'Android Phones', 'android-phones', 2, 'active'),
(3, 1, 'iPads & Tablets', 'ipads-tablets', 3, 'active'),
(4, 2, 'Wireless Earbuds', 'wireless-earbuds', 1, 'active'),
(5, 2, 'Headphones', 'headphones', 2, 'active'),
(6, 2, 'Bluetooth Speakers', 'bluetooth-speakers', 3, 'active'),
(7, 3, 'Chargers & Adapters', 'chargers-adapters', 1, 'active'),
(8, 3, 'Cables & Hubs', 'cables-hubs', 2, 'active'),
(9, 3, 'Cases & Covers', 'cases-covers', 3, 'active'),
(10, 4, 'Apple Watch', 'apple-watch', 1, 'active'),
(11, 4, 'Fitness Trackers', 'fitness-trackers', 2, 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
