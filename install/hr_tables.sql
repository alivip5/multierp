-- ==========================================
-- جداول وحدة شؤون العاملين (HR)
-- قم بتشغيل هذا الملف في phpMyAdmin
-- ==========================================

-- جدول الأقسام
CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `manager_id` INT UNSIGNED NULL,
    `parent_id` INT UNSIGNED NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    INDEX `idx_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول المناصب
CREATE TABLE IF NOT EXISTS `positions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `min_salary` DECIMAL(10,2) NULL,
    `max_salary` DECIMAL(10,2) NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    INDEX `idx_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الموظفين
CREATE TABLE IF NOT EXISTS `employees` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `employee_number` VARCHAR(50) NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(20) NULL,
    `mobile` VARCHAR(20) NULL,
    `national_id` VARCHAR(50) NULL,
    `date_of_birth` DATE NULL,
    `gender` ENUM('male', 'female') NULL,
    `marital_status` ENUM('single', 'married', 'divorced', 'widowed') NULL,
    `nationality` VARCHAR(50) NULL,
    `address` TEXT NULL,
    `department_id` INT UNSIGNED NULL,
    `position_id` INT UNSIGNED NULL,
    `hire_date` DATE NULL,
    `contract_type` ENUM('permanent', 'contract', 'part_time', 'probation') DEFAULT 'permanent',
    `salary` DECIMAL(10,2) DEFAULT 0,
    `bank_name` VARCHAR(100) NULL,
    `bank_account` VARCHAR(50) NULL,
    `iban` VARCHAR(50) NULL,
    `status` ENUM('active', 'inactive', 'on_leave', 'terminated') DEFAULT 'active',
    `termination_date` DATE NULL,
    `termination_reason` TEXT NULL,
    `notes` TEXT NULL,
    `photo` VARCHAR(255) NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`position_id`) REFERENCES `positions`(`id`) ON DELETE SET NULL,
    INDEX `idx_company` (`company_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_department` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- بيانات تجريبية (اختياري)
INSERT INTO `departments` (`company_id`, `name`) VALUES 
(1, 'الإدارة العامة'),
(1, 'المبيعات'),
(1, 'المحاسبة'),
(1, 'تقنية المعلومات');

INSERT INTO `positions` (`company_id`, `name`) VALUES 
(1, 'مدير عام'),
(1, 'مدير قسم'),
(1, 'موظف'),
(1, 'محاسب'),
(1, 'مندوب مبيعات');
