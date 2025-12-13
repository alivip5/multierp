-- ==========================================
-- جداول وحدة الإنتاج (Production)
-- قم بتشغيل هذا الملف في phpMyAdmin
-- ==========================================

-- جدول المنتجات المصنعة (BOM - Bill of Materials)
CREATE TABLE IF NOT EXISTS `production_bom` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL COMMENT 'المنتج النهائي',
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `quantity_per_batch` DECIMAL(10,2) DEFAULT 1,
    `estimated_time` INT NULL COMMENT 'الوقت المقدر بالدقائق',
    `labor_cost` DECIMAL(10,2) DEFAULT 0,
    `overhead_cost` DECIMAL(10,2) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    INDEX `idx_company` (`company_id`),
    INDEX `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول مكونات BOM
CREATE TABLE IF NOT EXISTS `production_bom_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `bom_id` INT UNSIGNED NOT NULL,
    `material_id` INT UNSIGNED NOT NULL COMMENT 'المادة الخام (منتج)',
    `quantity` DECIMAL(10,3) NOT NULL,
    `unit` VARCHAR(50) NULL,
    `waste_percentage` DECIMAL(5,2) DEFAULT 0,
    `notes` TEXT NULL,
    FOREIGN KEY (`bom_id`) REFERENCES `production_bom`(`id`) ON DELETE CASCADE,
    INDEX `idx_bom` (`bom_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول أوامر الإنتاج
CREATE TABLE IF NOT EXISTS `production_orders` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `order_number` VARCHAR(50) NOT NULL,
    `bom_id` INT UNSIGNED NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `quantity` DECIMAL(10,2) NOT NULL,
    `produced_quantity` DECIMAL(10,2) DEFAULT 0,
    `start_date` DATE NULL,
    `due_date` DATE NULL,
    `completion_date` DATE NULL,
    `status` ENUM('draft', 'pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'draft',
    `priority` ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    `notes` TEXT NULL,
    `total_material_cost` DECIMAL(12,2) DEFAULT 0,
    `total_labor_cost` DECIMAL(12,2) DEFAULT 0,
    `total_cost` DECIMAL(12,2) DEFAULT 0,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    INDEX `idx_company` (`company_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_order_number` (`order_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول مواد أمر الإنتاج
CREATE TABLE IF NOT EXISTS `production_order_materials` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT UNSIGNED NOT NULL,
    `material_id` INT UNSIGNED NOT NULL,
    `required_quantity` DECIMAL(10,3) NOT NULL,
    `consumed_quantity` DECIMAL(10,3) DEFAULT 0,
    `unit_cost` DECIMAL(10,2) DEFAULT 0,
    `total_cost` DECIMAL(12,2) DEFAULT 0,
    FOREIGN KEY (`order_id`) REFERENCES `production_orders`(`id`) ON DELETE CASCADE,
    INDEX `idx_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إضافة وحدة الإنتاج في جدول modules
INSERT INTO `modules` (`name`, `name_ar`, `slug`, `icon`, `sort_order`, `is_system`) 
VALUES ('Production', 'الإنتاج', 'production', 'fas fa-industry', 5, 0)
ON DUPLICATE KEY UPDATE name_ar = 'الإنتاج';

-- تفعيل الوحدة للشركة
INSERT INTO `company_modules` (`company_id`, `module_id`, `status`) 
SELECT 1, id, 'enabled' FROM modules WHERE slug = 'production'
ON DUPLICATE KEY UPDATE status = 'enabled';
