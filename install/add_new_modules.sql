-- إضافة الوحدات الجديدة (HR و Reports) لجدول modules
-- قم بتشغيل هذا في phpMyAdmin

-- وحدة شؤون العاملين
INSERT INTO `modules` (`name`, `name_ar`, `slug`, `icon`, `description`, `sort_order`, `is_system`, `is_active`) 
VALUES ('Human Resources', 'شؤون العاملين', 'hr', 'fas fa-users', 'إدارة الموظفين والرواتب', 6, 0, 1);

-- وحدة التقارير
INSERT INTO `modules` (`name`, `name_ar`, `slug`, `icon`, `description`, `sort_order`, `is_system`, `is_active`) 
VALUES ('Reports', 'التقارير', 'reports', 'fas fa-chart-bar', 'التقارير والإحصائيات', 7, 0, 1);

-- تفعيل الوحدات للشركة (استبدل 1 برقم الشركة إذا لزم الأمر)
INSERT INTO `company_modules` (`company_id`, `module_id`, `status`) 
SELECT 1, id, 'enabled' FROM modules WHERE slug IN ('hr', 'reports');
