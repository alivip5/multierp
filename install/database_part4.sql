-- الجزء 4: البيانات الافتراضية

-- إنشاء شركة افتراضية
INSERT INTO `companies` (`name`, `name_en`, `address`, `phone`, `email`, `currency`, `currency_symbol`, `tax_rate`) VALUES
('شركتي الأولى', 'My First Company', 'الرياض، المملكة العربية السعودية', '+966500000000', 'info@mycompany.com', 'SAR', 'ر.س', 15.00);

-- إنشاء المستخدم الافتراضي (كلمة المرور: admin)
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role_id`, `is_active`) VALUES
('admin', 'admin@system.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مدير النظام', 1, 1);

-- ربط المستخدم بالشركة
INSERT INTO `user_companies` (`user_id`, `company_id`, `is_default`) VALUES (1, 1, 1);

-- تفعيل جميع الموديولات للشركة
INSERT INTO `company_modules` (`company_id`, `module_id`, `status`)
SELECT 1, `id`, 'enabled' FROM `modules`;

-- إنشاء وحدات افتراضية
INSERT INTO `units` (`company_id`, `name`, `short_name`) VALUES
(1, 'قطعة', 'قطعة'), (1, 'كيلوجرام', 'كجم'), (1, 'لتر', 'لتر'),
(1, 'متر', 'م'), (1, 'علبة', 'علبة'), (1, 'كرتون', 'كرتون');

-- إنشاء مخزن افتراضي
INSERT INTO `warehouses` (`company_id`, `name`, `code`, `is_default`) VALUES (1, 'المخزن الرئيسي', 'WH001', 1);

-- إنشاء شجرة حسابات أساسية
INSERT INTO `accounts` (`company_id`, `code`, `name`, `type`, `nature`, `is_system`) VALUES
(1, '1', 'الأصول', 'asset', 'debit', 1),
(1, '11', 'الأصول المتداولة', 'asset', 'debit', 1),
(1, '111', 'الصندوق', 'asset', 'debit', 1),
(1, '112', 'البنك', 'asset', 'debit', 1),
(1, '113', 'العملاء', 'asset', 'debit', 1),
(1, '114', 'المخزون', 'asset', 'debit', 1),
(1, '2', 'الخصوم', 'liability', 'credit', 1),
(1, '21', 'الخصوم المتداولة', 'liability', 'credit', 1),
(1, '211', 'الموردين', 'liability', 'credit', 1),
(1, '212', 'ضريبة القيمة المضافة', 'liability', 'credit', 1),
(1, '3', 'حقوق الملكية', 'equity', 'credit', 1),
(1, '31', 'رأس المال', 'equity', 'credit', 1),
(1, '4', 'الإيرادات', 'revenue', 'credit', 1),
(1, '41', 'إيرادات المبيعات', 'revenue', 'credit', 1),
(1, '5', 'المصروفات', 'expense', 'debit', 1),
(1, '51', 'تكلفة المبيعات', 'expense', 'debit', 1),
(1, '52', 'مصروفات الرواتب', 'expense', 'debit', 1);

-- إنشاء الصلاحيات
INSERT INTO `permissions` (`module_id`, `name`, `name_ar`, `slug`) VALUES
(2, 'View Sales', 'عرض المبيعات', 'sales.view'),
(2, 'Create Sales', 'إنشاء مبيعات', 'sales.create'),
(2, 'Edit Sales', 'تعديل المبيعات', 'sales.edit'),
(2, 'Delete Sales', 'حذف المبيعات', 'sales.delete'),
(3, 'View Purchases', 'عرض المشتريات', 'purchases.view'),
(3, 'Create Purchases', 'إنشاء مشتريات', 'purchases.create'),
(3, 'Edit Purchases', 'تعديل المشتريات', 'purchases.edit'),
(3, 'Delete Purchases', 'حذف المشتريات', 'purchases.delete'),
(4, 'View Inventory', 'عرض المخزون', 'inventory.view'),
(4, 'Manage Products', 'إدارة المنتجات', 'products.manage'),
(4, 'Manage Warehouses', 'إدارة المخازن', 'warehouses.manage'),
(5, 'View Accounting', 'عرض الحسابات', 'accounting.view'),
(5, 'Manage Accounts', 'إدارة الحسابات', 'accounts.manage'),
(5, 'Create Entries', 'إنشاء قيود', 'entries.create'),
(6, 'View HR', 'عرض الموظفين', 'hr.view'),
(6, 'Manage Employees', 'إدارة الموظفين', 'employees.manage'),
(6, 'Manage Payroll', 'إدارة الرواتب', 'payroll.manage'),
(7, 'View Reports', 'عرض التقارير', 'reports.view'),
(7, 'Export Reports', 'تصدير التقارير', 'reports.export'),
(8, 'View Settings', 'عرض الإعدادات', 'settings.view'),
(8, 'Manage Settings', 'إدارة الإعدادات', 'settings.manage'),
(8, 'Manage Users', 'إدارة المستخدمين', 'users.manage'),
(8, 'Manage Modules', 'إدارة الموديولات', 'modules.manage');

-- منح جميع الصلاحيات للـ super_admin
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `permissions`;

-- إنشاء جدول الإشعارات
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NULL,
    `user_id` INT UNSIGNED NULL,
    `type` VARCHAR(100) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NULL,
    `data` JSON NULL,
    `read_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول حركات المخزون
CREATE TABLE IF NOT EXISTS `inventory_movements` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `warehouse_id` INT UNSIGNED NOT NULL,
    `movement_type` ENUM('in', 'out', 'transfer', 'adjustment') NOT NULL,
    `reference_type` VARCHAR(50) NULL,
    `reference_id` INT UNSIGNED NULL,
    `quantity` DECIMAL(15,4) NOT NULL,
    `unit_cost` DECIMAL(15,4) DEFAULT 0,
    `balance_before` DECIMAL(15,4) DEFAULT 0,
    `balance_after` DECIMAL(15,4) DEFAULT 0,
    `notes` TEXT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
