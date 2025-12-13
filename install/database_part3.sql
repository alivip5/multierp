-- الجزء 3: جداول الفواتير والحسابات والموظفين

-- فواتير المبيعات
CREATE TABLE `sales_invoices` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `invoice_number` VARCHAR(50) NOT NULL,
    `customer_id` INT UNSIGNED NULL,
    `invoice_date` DATE NOT NULL,
    `due_date` DATE NULL,
    `subtotal` DECIMAL(15,2) DEFAULT 0,
    `discount_type` ENUM('fixed', 'percentage') DEFAULT 'fixed',
    `discount_value` DECIMAL(15,2) DEFAULT 0,
    `discount_amount` DECIMAL(15,2) DEFAULT 0,
    `tax_amount` DECIMAL(15,2) DEFAULT 0,
    `total` DECIMAL(15,2) DEFAULT 0,
    `paid_amount` DECIMAL(15,2) DEFAULT 0,
    `status` ENUM('draft', 'pending', 'paid', 'partial', 'cancelled', 'refunded') DEFAULT 'draft',
    `payment_status` ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
    `notes` TEXT NULL,
    `terms` TEXT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `company_invoice_unique` (`company_id`, `invoice_number`),
    INDEX `idx_company_id` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sales_invoice_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NULL,
    `description` VARCHAR(500) NULL,
    `quantity` DECIMAL(15,4) NOT NULL DEFAULT 1,
    `unit_id` INT UNSIGNED NULL,
    `unit_price` DECIMAL(15,4) NOT NULL DEFAULT 0,
    `discount_amount` DECIMAL(15,2) DEFAULT 0,
    `tax_rate` DECIMAL(5,2) DEFAULT 0,
    `tax_amount` DECIMAL(15,2) DEFAULT 0,
    `total` DECIMAL(15,2) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`invoice_id`) REFERENCES `sales_invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- فواتير المشتريات
CREATE TABLE `purchase_invoices` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `invoice_number` VARCHAR(50) NOT NULL,
    `supplier_id` INT UNSIGNED NULL,
    `invoice_date` DATE NOT NULL,
    `due_date` DATE NULL,
    `subtotal` DECIMAL(15,2) DEFAULT 0,
    `discount_amount` DECIMAL(15,2) DEFAULT 0,
    `tax_amount` DECIMAL(15,2) DEFAULT 0,
    `total` DECIMAL(15,2) DEFAULT 0,
    `paid_amount` DECIMAL(15,2) DEFAULT 0,
    `status` ENUM('draft', 'pending', 'received', 'cancelled') DEFAULT 'draft',
    `payment_status` ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
    `notes` TEXT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `company_invoice_unique` (`company_id`, `invoice_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `purchase_invoice_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `invoice_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NULL,
    `description` VARCHAR(500) NULL,
    `quantity` DECIMAL(15,4) NOT NULL DEFAULT 1,
    `unit_id` INT UNSIGNED NULL,
    `unit_price` DECIMAL(15,4) NOT NULL DEFAULT 0,
    `discount_amount` DECIMAL(15,2) DEFAULT 0,
    `tax_rate` DECIMAL(5,2) DEFAULT 0,
    `tax_amount` DECIMAL(15,2) DEFAULT 0,
    `total` DECIMAL(15,2) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`invoice_id`) REFERENCES `purchase_invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- شجرة الحسابات
CREATE TABLE `accounts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `parent_id` INT UNSIGNED NULL,
    `code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `type` ENUM('asset', 'liability', 'equity', 'revenue', 'expense') NOT NULL,
    `nature` ENUM('debit', 'credit') NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `is_system` TINYINT(1) DEFAULT 0,
    `balance` DECIMAL(15,2) DEFAULT 0,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`parent_id`) REFERENCES `accounts`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `company_code_unique` (`company_id`, `code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- القيود اليومية
CREATE TABLE `journal_entries` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `entry_number` VARCHAR(50) NOT NULL,
    `entry_date` DATE NOT NULL,
    `reference_type` VARCHAR(50) NULL,
    `reference_id` INT UNSIGNED NULL,
    `description` TEXT NULL,
    `total_debit` DECIMAL(15,2) DEFAULT 0,
    `total_credit` DECIMAL(15,2) DEFAULT 0,
    `status` ENUM('draft', 'posted', 'cancelled') DEFAULT 'draft',
    `posted_at` TIMESTAMP NULL,
    `posted_by` INT UNSIGNED NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `company_entry_unique` (`company_id`, `entry_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `journal_entry_lines` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `entry_id` INT UNSIGNED NOT NULL,
    `account_id` INT UNSIGNED NOT NULL,
    `debit` DECIMAL(15,2) DEFAULT 0,
    `credit` DECIMAL(15,2) DEFAULT 0,
    `description` VARCHAR(500) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`entry_id`) REFERENCES `journal_entries`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`account_id`) REFERENCES `accounts`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- المدفوعات
CREATE TABLE `payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `payment_number` VARCHAR(50) NOT NULL,
    `payment_type` ENUM('receipt', 'payment') NOT NULL,
    `payment_date` DATE NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `payment_method` ENUM('cash', 'bank', 'check', 'transfer', 'other') DEFAULT 'cash',
    `reference_type` VARCHAR(50) NULL,
    `reference_id` INT UNSIGNED NULL,
    `customer_id` INT UNSIGNED NULL,
    `supplier_id` INT UNSIGNED NULL,
    `account_id` INT UNSIGNED NULL,
    `notes` TEXT NULL,
    `status` ENUM('pending', 'completed', 'cancelled') DEFAULT 'completed',
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `company_payment_unique` (`company_id`, `payment_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- الأقسام
CREATE TABLE `departments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `manager_id` INT UNSIGNED NULL,
    `description` TEXT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- المناصب
CREATE TABLE `positions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `department_id` INT UNSIGNED NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- الموظفين
CREATE TABLE `employees` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NULL,
    `employee_number` VARCHAR(50) NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(50) NULL,
    `mobile` VARCHAR(50) NULL,
    `address` TEXT NULL,
    `national_id` VARCHAR(50) NULL,
    `date_of_birth` DATE NULL,
    `gender` ENUM('male', 'female') NULL,
    `marital_status` ENUM('single', 'married', 'divorced', 'widowed') NULL,
    `nationality` VARCHAR(100) NULL,
    `department_id` INT UNSIGNED NULL,
    `position_id` INT UNSIGNED NULL,
    `hire_date` DATE NULL,
    `contract_type` ENUM('permanent', 'contract', 'part_time', 'probation') DEFAULT 'permanent',
    `salary` DECIMAL(15,2) DEFAULT 0,
    `bank_name` VARCHAR(255) NULL,
    `bank_account` VARCHAR(100) NULL,
    `iban` VARCHAR(50) NULL,
    `photo` VARCHAR(500) NULL,
    `status` ENUM('active', 'inactive', 'terminated', 'on_leave') DEFAULT 'active',
    `termination_date` DATE NULL,
    `notes` TEXT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`position_id`) REFERENCES `positions`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- الحضور
CREATE TABLE `attendance` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `employee_id` INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    `check_in` TIME NULL,
    `check_out` TIME NULL,
    `worked_hours` DECIMAL(5,2) NULL,
    `overtime_hours` DECIMAL(5,2) DEFAULT 0,
    `status` ENUM('present', 'absent', 'late', 'early_leave', 'half_day', 'holiday', 'leave') DEFAULT 'present',
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `employee_date_unique` (`employee_id`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
