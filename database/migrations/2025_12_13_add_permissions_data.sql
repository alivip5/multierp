-- =============================================
-- Migration: إضافة الصلاحيات الافتراضية وربطها بالأدوار
-- تاريخ: 2025-12-13
-- =============================================

-- Module IDs:
-- 1: dashboard, 2: sales, 3: purchases, 4: inventory
-- 5: accounting, 6: settings, 7: hr, 8: reports, 9: production

-- 1. إضافة الصلاحيات الأساسية
INSERT IGNORE INTO `permissions` (`name`, `name_ar`, `slug`, `module_id`) VALUES
-- صلاحيات المبيعات (module_id = 2)
('View Sales', 'عرض المبيعات', 'sales.view', 2),
('Create Sales', 'إنشاء مبيعات', 'sales.create', 2),
('Edit Sales', 'تعديل المبيعات', 'sales.edit', 2),
('Delete Sales', 'حذف المبيعات', 'sales.delete', 2),

-- صلاحيات المشتريات (module_id = 3)
('View Purchases', 'عرض المشتريات', 'purchases.view', 3),
('Create Purchases', 'إنشاء مشتريات', 'purchases.create', 3),
('Edit Purchases', 'تعديل المشتريات', 'purchases.edit', 3),
('Delete Purchases', 'حذف المشتريات', 'purchases.delete', 3),

-- صلاحيات المخزون (module_id = 4)
('View Inventory', 'عرض المخزون', 'inventory.view', 4),
('Create Inventory', 'إضافة منتجات', 'inventory.create', 4),
('Edit Inventory', 'تعديل المخزون', 'inventory.edit', 4),
('Delete Inventory', 'حذف المخزون', 'inventory.delete', 4),

-- صلاحيات المحاسبة (module_id = 5)
('View Accounting', 'عرض المحاسبة', 'accounting.view', 5),
('Create Accounting', 'إنشاء قيود', 'accounting.create', 5),
('Edit Accounting', 'تعديل المحاسبة', 'accounting.edit', 5),
('Delete Accounting', 'حذف المحاسبة', 'accounting.delete', 5),

-- صلاحيات الموارد البشرية (module_id = 7)
('View HR', 'عرض الموارد البشرية', 'hr.view', 7),
('Create HR', 'إضافة موظفين', 'hr.create', 7),
('Edit HR', 'تعديل الموظفين', 'hr.edit', 7),
('Delete HR', 'حذف الموظفين', 'hr.delete', 7),

-- صلاحيات التقارير (module_id = 8)
('View Reports', 'عرض التقارير', 'reports.view', 8),
('Export Reports', 'تصدير التقارير', 'reports.export', 8),

-- صلاحيات الإعدادات (module_id = 6)
('View Settings', 'عرض الإعدادات', 'settings.view', 6),
('Edit Settings', 'تعديل الإعدادات', 'settings.edit', 6),
('Manage Users', 'إدارة المستخدمين', 'settings.users', 6),
('Manage Roles', 'إدارة الأدوار', 'settings.roles', 6),

-- صلاحيات الإنتاج (module_id = 9)
('View Production', 'عرض الإنتاج', 'production.view', 9),
('Create Production', 'إنشاء أوامر إنتاج', 'production.create', 9),

-- صلاحيات نقطة البيع (ضمن المبيعات module_id = 2)
('POS Access', 'الوصول لنقطة البيع', 'pos.access', 2);

-- 2. ربط الصلاحيات بدور manager (كل الصلاحيات ما عدا إدارة الأدوار)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r, `permissions` p
WHERE r.name = 'manager' AND p.slug != 'settings.roles';

-- 3. ربط صلاحيات المحاسب بدور accountant
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r, `permissions` p
WHERE r.name = 'accountant' 
AND p.slug IN (
    'sales.view', 'sales.create',
    'purchases.view', 'purchases.create',
    'accounting.view', 'accounting.create', 'accounting.edit',
    'reports.view', 'reports.export',
    'inventory.view'
);

-- 4. ربط صلاحيات البائع بدور sales
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r, `permissions` p
WHERE r.name = 'sales' 
AND p.slug IN (
    'sales.view', 'sales.create', 'sales.edit',
    'pos.access',
    'inventory.view',
    'reports.view'
);

-- 5. ربط صلاحيات أمين المخزن بدور warehouse
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r, `permissions` p
WHERE r.name = 'warehouse' 
AND p.slug IN (
    'inventory.view', 'inventory.create', 'inventory.edit',
    'purchases.view',
    'reports.view'
);

-- 6. عرض الصلاحيات المرتبطة بكل دور للتأكد
SELECT r.name as role_name, r.name_ar, GROUP_CONCAT(p.slug ORDER BY p.slug) as permissions
FROM roles r
LEFT JOIN role_permissions rp ON r.id = rp.role_id
LEFT JOIN permissions p ON rp.permission_id = p.id
GROUP BY r.id, r.name, r.name_ar
ORDER BY r.id;
