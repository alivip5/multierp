-- ==========================================
-- إعادة إنشاء جداول الصلاحيات وإضافة البيانات
-- قم بتشغيل هذا الملف في phpMyAdmin
-- ==========================================

-- 1. تعطيل فحص المفاتيح الخارجية مؤقتاً
SET FOREIGN_KEY_CHECKS = 0;

-- 2. حذف الجداول القديمة لإعادة بنائها بالهيكل الصحيح
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `permissions`;

-- 3. تفعيل فحص المفاتيح الخارجية
SET FOREIGN_KEY_CHECKS = 1;

-- 4. إعادة إنشاء جدول الصلاحيات (مع التأكد من وجود name_ar)
CREATE TABLE `permissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `module_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `name_ar` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`module_id`) REFERENCES `modules`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. إعادة إنشاء جدول صلاحيات الأدوار
CREATE TABLE `role_permissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `role_permission_unique` (`role_id`, `permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 6. إدخال صلاحيات النظام
-- ==========================================

-- إضافة صلاحيات لوحة التحكم (module_id = 1)
INSERT INTO `permissions` (`module_id`, `name`, `name_ar`, `slug`) VALUES
(1, 'View Dashboard', 'عرض لوحة التحكم', 'dashboard.view');

-- إضافة صلاحيات المبيعات (module_id = 2)
INSERT INTO `permissions` (`module_id`, `name`, `name_ar`, `slug`) VALUES
(2, 'View Sales', 'عرض المبيعات', 'sales.view'),
(2, 'Create Invoice', 'إضافة فاتورة', 'sales.create'),
(2, 'Edit Invoice', 'تعديل فاتورة', 'sales.edit'),
(2, 'Delete Invoice', 'حذف فاتورة', 'sales.delete'),
(2, 'View Customers', 'عرض العملاء', 'customers.view'),
(2, 'Manage Customers', 'إدارة العملاء', 'customers.manage');

-- إضافة صلاحيات المشتريات (module_id = 3)
INSERT INTO `permissions` (`module_id`, `name`, `name_ar`, `slug`) VALUES
(3, 'View Purchases', 'عرض المشتريات', 'purchases.view'),
(3, 'Create Purchase', 'إضافة فاتورة مشتريات', 'purchases.create'),
(3, 'Edit Purchase', 'تعديل فاتورة مشتريات', 'purchases.edit'),
(3, 'Delete Purchase', 'حذف فاتورة مشتريات', 'purchases.delete'),
(3, 'View Suppliers', 'عرض الموردين', 'suppliers.view'),
(3, 'Manage Suppliers', 'إدارة الموردين', 'suppliers.manage');

-- إضافة صلاحيات المخزون (module_id = 4)
INSERT INTO `permissions` (`module_id`, `name`, `name_ar`, `slug`) VALUES
(4, 'View Products', 'عرض المنتجات', 'products.view'),
(4, 'Create Product', 'إضافة منتج', 'products.create'),
(4, 'Edit Product', 'تعديل منتج', 'products.edit'),
(4, 'Delete Product', 'حذف منتج', 'products.delete'),
(4, 'Manage Inventory', 'إدارة المخزون', 'inventory.manage');

-- إضافة صلاحيات المحاسبة (module_id = 5)
INSERT INTO `permissions` (`module_id`, `name`, `name_ar`, `slug`) VALUES
(5, 'View Entries', 'عرض القيود', 'accounting.view'),
(5, 'Create Entry', 'إضافة قيد', 'accounting.create'),
(5, 'Edit Entry', 'تعديل قيد', 'accounting.edit'),
(5, 'Delete Entry', 'حذف قيد', 'accounting.delete'),
(5, 'Manage Accounts', 'إدارة الحسابات', 'accounts.manage');

-- إضافة صلاحيات الإعدادات (module_id = 6)
INSERT INTO `permissions` (`module_id`, `name`, `name_ar`, `slug`) VALUES
(6, 'Manage Settings', 'إدارة الإعدادات', 'settings.manage'),
(6, 'Manage Users', 'إدارة المستخدمين', 'users.manage'),
(6, 'Manage Roles', 'إدارة الأدوار', 'roles.manage'),
(6, 'Manage Backup', 'النسخ الاحتياطي', 'backup.manage'),
(6, 'Import Export', 'استيراد/تصدير', 'import_export.manage');

-- إضافة صلاحيات شؤون العاملين (module_id = 7)
INSERT INTO `permissions` (`module_id`, `name`, `name_ar`, `slug`) VALUES
(7, 'View Employees', 'عرض الموظفين', 'employees.view'),
(7, 'Create Employee', 'إضافة موظف', 'employees.create'),
(7, 'Edit Employee', 'تعديل موظف', 'employees.edit'),
(7, 'Delete Employee', 'حذف موظف', 'employees.delete'),
(7, 'Manage Payroll', 'إدارة الرواتب', 'payroll.manage');

-- إضافة صلاحيات التقارير (module_id = 8)
INSERT INTO `permissions` (`module_id`, `name`, `name_ar`, `slug`) VALUES
(8, 'View Sales Report', 'تقرير المبيعات', 'reports.sales'),
(8, 'View Purchases Report', 'تقرير المشتريات', 'reports.purchases'),
(8, 'View Inventory Report', 'تقرير المخزون', 'reports.inventory'),
(8, 'View Profit Report', 'تقرير الأرباح', 'reports.profit'),
(8, 'View Employees Report', 'تقرير الموظفين', 'reports.employees');

-- إضافة صلاحيات الإنتاج (module_id = 9)
INSERT INTO `permissions` (`module_id`, `name`, `name_ar`, `slug`) VALUES
(9, 'View Production', 'عرض الإنتاج', 'production.view'),
(9, 'Manage Orders', 'إدارة أوامر الإنتاج', 'production.manage'),
(9, 'Manage BOM', 'إدارة قوائم المواد', 'bom.manage');



-- ==========================================
-- 7. التأكد من وجود الأدوار المطلوبة
-- ==========================================

INSERT IGNORE INTO `roles` (`id`, `name`, `name_ar`, `is_system`, `description`) VALUES
(1, 'super_admin', 'مدير النظام', 1, 'صلاحيات كاملة على النظام'),
(2, 'manager', 'مدير الشركة', 1, 'إدارة الشركة والموظفين'),
(3, 'accountant', 'محاسب', 1, 'إدارة الحسابات والتقارير المالية'),
(4, 'storekeeper', 'أمين مخزن', 1, 'إدارة المخزون والمنتجات'),
(5, 'sales', 'موظف مبيعات', 1, 'إدارة المبيعات والعملاء'),
(6, 'purchasing', 'موظف مشتريات', 0, 'إدارة المشتريات والموردين'),
(7, 'hr', 'موظف موارد بشرية', 0, 'إدارة شؤون الموظفين والرواتب');

-- ==========================================
-- 8. توزيع الصلاحيات على الأدوار
-- ==========================================

-- إعطاء super_admin (role_id = 1) جميع الصلاحيات
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM permissions;

-- إعطاء manager (role_id = 2) معظم الصلاحيات
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM permissions WHERE slug NOT IN ('roles.manage', 'backup.manage');

-- إعطاء accountant (role_id = 3) صلاحيات المحاسبة والتقارير
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, id FROM permissions WHERE module_id IN (5, 8) OR slug LIKE '%.view';

-- إعطاء storekeeper (role_id = 4) صلاحيات المخزون
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, id FROM permissions WHERE module_id = 4;

-- إعطاء sales (role_id = 5) صلاحيات المبيعات
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 5, id FROM permissions WHERE module_id = 2;

-- إعطاء purchasing (role_id = 6) صلاحيات المشتريات
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 6, id FROM permissions WHERE module_id = 3;

-- إعطاء hr (role_id = 7) صلاحيات شؤون العاملين
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 7, id FROM permissions WHERE module_id = 7;

SELECT 'تم إعادة بناء جداول الصلاحيات وإضافة البيانات بنجاح!' AS Result;
