-- نظام ERP متعدد الشركات - قاعدة البيانات - الجزء 1
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `multierp` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `multierp`;

-- جدول الشركات
CREATE TABLE `companies` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `name_en` VARCHAR(255) NULL,
    `logo` VARCHAR(500) NULL,
    `address` TEXT NULL,
    `phone` VARCHAR(50) NULL,
    `email` VARCHAR(255) NULL,
    `website` VARCHAR(255) NULL,
    `tax_number` VARCHAR(100) NULL,
    `commercial_registry` VARCHAR(100) NULL,
    `currency` VARCHAR(10) DEFAULT 'SAR',
    `currency_symbol` VARCHAR(10) DEFAULT 'ر.س',
    `decimal_places` TINYINT DEFAULT 2,
    `tax_rate` DECIMAL(5,2) DEFAULT 15.00,
    `fiscal_year_start` DATE NULL,
    `timezone` VARCHAR(50) DEFAULT 'Asia/Riyadh',
    `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الأدوار
CREATE TABLE `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `name_ar` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `is_system` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`name`, `name_ar`, `description`, `is_system`) VALUES
('super_admin', 'مدير النظام', 'صلاحيات كاملة', 1),
('manager', 'مدير الشركة', 'إدارة كاملة للشركة', 1),
('accountant', 'محاسب', 'إدارة الحسابات', 1),
('storekeeper', 'أمين مخزن', 'إدارة المخازن', 1),
('sales', 'موظف مبيعات', 'إدارة المبيعات', 1),
('purchasing', 'موظف مشتريات', 'إدارة المشتريات', 1),
('hr', 'موظف موارد بشرية', 'إدارة الموظفين', 1),
('data_entry', 'مدخل بيانات', 'إدخال البيانات', 1),
('guest', 'زائر', 'عرض فقط', 1);

-- جدول المستخدمين
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) NULL,
    `avatar` VARCHAR(500) NULL,
    `role_id` INT UNSIGNED NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `theme` ENUM('light', 'dark') DEFAULT 'light',
    `language` VARCHAR(10) DEFAULT 'ar',
    `last_login` TIMESTAMP NULL,
    `last_login_ip` VARCHAR(45) NULL,
    `password_changed_at` TIMESTAMP NULL,
    `remember_token` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ربط المستخدمين بالشركات
CREATE TABLE `user_companies` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `company_id` INT UNSIGNED NOT NULL,
    `is_default` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `user_company_unique` (`user_id`, `company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الموديولات
CREATE TABLE `modules` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `name_ar` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(50) NOT NULL,
    `icon` VARCHAR(50) NULL,
    `description` TEXT NULL,
    `sort_order` INT DEFAULT 0,
    `is_system` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `modules` (`name`, `name_ar`, `slug`, `icon`, `sort_order`, `is_system`) VALUES
('Dashboard', 'لوحة التحكم', 'dashboard', 'fas fa-tachometer-alt', 1, 1),
('Sales', 'المبيعات', 'sales', 'fas fa-shopping-cart', 2, 0),
('Purchases', 'المشتريات', 'purchases', 'fas fa-truck', 3, 0),
('Inventory', 'المخازن', 'inventory', 'fas fa-warehouse', 4, 0),
('Accounting', 'الحسابات', 'accounting', 'fas fa-calculator', 5, 0),
('HR', 'شؤون العاملين', 'hr', 'fas fa-users', 6, 0),
('Reports', 'التقارير', 'reports', 'fas fa-chart-bar', 7, 0),
('Settings', 'الإعدادات', 'settings', 'fas fa-cog', 8, 1);

-- تفعيل الموديولات لكل شركة
CREATE TABLE `company_modules` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `module_id` INT UNSIGNED NOT NULL,
    `status` ENUM('enabled', 'disabled') DEFAULT 'enabled',
    `settings` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`module_id`) REFERENCES `modules`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `company_module_unique` (`company_id`, `module_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
