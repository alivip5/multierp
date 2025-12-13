-- Migration: إنشاء جدول الفروع وتحديث المخازن
-- تاريخ: 2025-12-12
-- ملاحظة: تم إزالة FOREIGN KEY لتجنب مشاكل التوافق

-- 1. جدول الفروع
CREATE TABLE IF NOT EXISTS `branches` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `code` VARCHAR(20) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `name_en` VARCHAR(100),
    `address` TEXT,
    `city` VARCHAR(50),
    `phone` VARCHAR(20),
    `email` VARCHAR(100),
    `manager_id` INT UNSIGNED,
    `is_main` TINYINT(1) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_by` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. إضافة عمود branch_id للمخازن (تجاهل الخطأ إذا موجود)
ALTER TABLE `warehouses` ADD COLUMN `branch_id` INT UNSIGNED DEFAULT NULL;
ALTER TABLE `warehouses` ADD INDEX `idx_branch` (`branch_id`);

-- 3. إضافة فرع افتراضي للشركات الموجودة
INSERT INTO `branches` (`company_id`, `code`, `name`, `is_main`, `is_active`)
SELECT c.id, 'BR-001', 'الفرع الرئيسي', 1, 1
FROM `companies` c
WHERE NOT EXISTS (SELECT 1 FROM `branches` b WHERE b.company_id = c.id AND b.is_main = 1);

-- 4. جدول الإشعارات
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `message` TEXT,
    `type` ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    `link` VARCHAR(255),
    `icon` VARCHAR(50),
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_company` (`company_id`),
    INDEX `idx_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. جدول أوامر الإنتاج
CREATE TABLE IF NOT EXISTS `production_orders` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `order_number` VARCHAR(50) NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `quantity` DECIMAL(15,4) NOT NULL,
    `planned_start` DATETIME,
    `planned_end` DATETIME,
    `actual_start` DATETIME,
    `actual_end` DATETIME,
    `status` ENUM('draft', 'pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'draft',
    `notes` TEXT,
    `created_by` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_company` (`company_id`),
    INDEX `idx_product` (`product_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. جدول مواد أوامر الإنتاج
CREATE TABLE IF NOT EXISTS `production_order_materials` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT UNSIGNED NOT NULL,
    `material_id` INT UNSIGNED NOT NULL,
    `required_quantity` DECIMAL(15,4) NOT NULL,
    `consumed_quantity` DECIMAL(15,4) DEFAULT 0,
    `unit_cost` DECIMAL(15,4) DEFAULT 0,
    INDEX `idx_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. جدول قوائم المواد (BOM)
CREATE TABLE IF NOT EXISTS `production_bom` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100),
    `output_quantity` DECIMAL(15,4) DEFAULT 1,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_by` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_company` (`company_id`),
    INDEX `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. جدول عناصر قوائم المواد
CREATE TABLE IF NOT EXISTS `production_bom_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `bom_id` INT UNSIGNED NOT NULL,
    `material_id` INT UNSIGNED NOT NULL,
    `quantity` DECIMAL(15,4) NOT NULL,
    `unit_cost` DECIMAL(15,4) DEFAULT 0,
    INDEX `idx_bom` (`bom_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. جدول القيود اليومية
CREATE TABLE IF NOT EXISTS `journal_entries` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `entry_date` DATE NOT NULL,
    `reference` VARCHAR(50),
    `description` TEXT,
    `total_debit` DECIMAL(15,4) DEFAULT 0,
    `total_credit` DECIMAL(15,4) DEFAULT 0,
    `reference_type` VARCHAR(50),
    `reference_id` INT UNSIGNED,
    `status` ENUM('draft', 'posted', 'cancelled') DEFAULT 'draft',
    `created_by` INT UNSIGNED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_company` (`company_id`),
    INDEX `idx_date` (`entry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. جدول أسطر القيود
CREATE TABLE IF NOT EXISTS `journal_entry_lines` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `entry_id` INT UNSIGNED NOT NULL,
    `account_id` INT UNSIGNED NOT NULL,
    `debit` DECIMAL(15,4) DEFAULT 0,
    `credit` DECIMAL(15,4) DEFAULT 0,
    `description` TEXT,
    INDEX `idx_entry` (`entry_id`),
    INDEX `idx_account` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
