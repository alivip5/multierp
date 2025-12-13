-- ==========================================
-- تحديثات شاملة لنظام ERP
-- تاريخ: 2025-12-13
-- ملاحظة: يجب تنفيذ هذا الملف بالتسلسل
-- ==========================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ==========================================
-- 1. تحديث جدول المخازن - إضافة branch_id
-- ==========================================
ALTER TABLE `warehouses` 
    ADD COLUMN IF NOT EXISTS `branch_id` INT UNSIGNED NULL COMMENT 'الفرع' AFTER `company_id`;

-- ==========================================
-- 2. جدول الفروع (إنشاء إذا لم يكن موجوداً)
-- ==========================================
CREATE TABLE IF NOT EXISTS `branches` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `code` VARCHAR(50) NULL,
    `address` TEXT NULL,
    `phone` VARCHAR(50) NULL,
    `email` VARCHAR(100) NULL,
    `manager_id` INT UNSIGNED NULL,
    `is_main` TINYINT(1) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_company` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 3. تحديث جدول المنتجات - إضافة تصنيف الأصناف
-- ==========================================
ALTER TABLE `products` 
    ADD COLUMN IF NOT EXISTS `product_type` ENUM('raw_material', 'finished_product', 'packaging') DEFAULT 'finished_product' COMMENT 'نوع المنتج';

-- ==========================================
-- 4. تحديث جدول العملاء - إضافة رصيد أول المدة
-- ==========================================
ALTER TABLE `customers` 
    ADD COLUMN IF NOT EXISTS `opening_balance` DECIMAL(15,2) DEFAULT 0 COMMENT 'رصيد أول المدة';

-- ==========================================
-- 5. تحديث جدول الموردين - إضافة رصيد أول المدة
-- ==========================================
ALTER TABLE `suppliers` 
    ADD COLUMN IF NOT EXISTS `opening_balance` DECIMAL(15,2) DEFAULT 0 COMMENT 'رصيد أول المدة';

-- ==========================================
-- 6. تحديث جدول فواتير المبيعات
-- ==========================================
ALTER TABLE `sales_invoices` 
    ADD COLUMN IF NOT EXISTS `warehouse_id` INT UNSIGNED DEFAULT NULL COMMENT 'المخزن المصدر';

ALTER TABLE `sales_invoices` 
    ADD COLUMN IF NOT EXISTS `delivery_driver_name` VARCHAR(100) NULL COMMENT 'اسم مندوب التوصيل';

ALTER TABLE `sales_invoices` 
    ADD COLUMN IF NOT EXISTS `vehicle_info` VARCHAR(100) NULL COMMENT 'بيانات السيارة';

ALTER TABLE `sales_invoices` 
    ADD COLUMN IF NOT EXISTS `sales_agent_id` INT UNSIGNED NULL COMMENT 'مندوب التعاقد';

-- ==========================================
-- 7. تحديث جدول بنود فواتير المبيعات
-- ==========================================
ALTER TABLE `sales_invoice_items` 
    ADD COLUMN IF NOT EXISTS `warehouse_id` INT UNSIGNED DEFAULT NULL COMMENT 'المخزن';

-- ==========================================
-- 8. تحديث جدول فواتير المشتريات
-- ==========================================
ALTER TABLE `purchase_invoices` 
    ADD COLUMN IF NOT EXISTS `warehouse_id` INT UNSIGNED DEFAULT NULL COMMENT 'المخزن الوجهة';

ALTER TABLE `purchase_invoices` 
    ADD COLUMN IF NOT EXISTS `supplier_invoice_number` VARCHAR(100) NULL COMMENT 'رقم فاتورة المورد';

-- ==========================================
-- 9. تحديث جدول بنود فواتير المشتريات
-- ==========================================
ALTER TABLE `purchase_invoice_items` 
    ADD COLUMN IF NOT EXISTS `warehouse_id` INT UNSIGNED DEFAULT NULL COMMENT 'المخزن';

-- ==========================================
-- 10. جدول مندوبي المبيعات/التعاقد
-- ==========================================
CREATE TABLE IF NOT EXISTS `sales_agents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `code` VARCHAR(20) NULL,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(50) NULL,
    `email` VARCHAR(100) NULL,
    `address` TEXT NULL,
    `commission_rate` DECIMAL(5,2) DEFAULT 0 COMMENT 'نسبة العمولة',
    `commission_type` ENUM('percentage', 'fixed') DEFAULT 'percentage',
    `is_active` TINYINT(1) DEFAULT 1,
    `notes` TEXT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_company` (`company_id`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 11. جدول التحويلات المخزنية
-- ==========================================
CREATE TABLE IF NOT EXISTS `stock_transfers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `transfer_number` VARCHAR(50) NOT NULL,
    `from_warehouse_id` INT UNSIGNED NOT NULL COMMENT 'المخزن المصدر',
    `to_warehouse_id` INT UNSIGNED NOT NULL COMMENT 'المخزن الوجهة',
    `from_branch_id` INT UNSIGNED NULL COMMENT 'الفرع المصدر',
    `to_branch_id` INT UNSIGNED NULL COMMENT 'الفرع الوجهة',
    `transfer_date` DATE NOT NULL,
    `status` ENUM('pending', 'in_transit', 'completed', 'cancelled') DEFAULT 'pending',
    `notes` TEXT NULL,
    `created_by` INT UNSIGNED NULL,
    `received_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_transfer_number` (`company_id`, `transfer_number`),
    INDEX `idx_company` (`company_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_from_warehouse` (`from_warehouse_id`),
    INDEX `idx_to_warehouse` (`to_warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 12. جدول بنود التحويلات المخزنية
-- ==========================================
CREATE TABLE IF NOT EXISTS `stock_transfer_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `transfer_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `quantity` DECIMAL(15,4) NOT NULL,
    `unit_cost` DECIMAL(15,4) DEFAULT 0,
    `received_quantity` DECIMAL(15,4) DEFAULT 0 COMMENT 'الكمية المستلمة',
    `notes` TEXT NULL,
    INDEX `idx_transfer` (`transfer_id`),
    INDEX `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 13. جدول أرصدة أول المدة للمخازن
-- ==========================================
CREATE TABLE IF NOT EXISTS `opening_stock` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `warehouse_id` INT UNSIGNED NOT NULL,
    `quantity` DECIMAL(15,4) NOT NULL DEFAULT 0,
    `unit_cost` DECIMAL(15,4) DEFAULT 0,
    `total_cost` DECIMAL(15,4) DEFAULT 0,
    `opening_date` DATE NOT NULL,
    `fiscal_year` INT NULL COMMENT 'السنة المالية',
    `notes` TEXT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_product_warehouse_year` (`product_id`, `warehouse_id`, `fiscal_year`),
    INDEX `idx_company` (`company_id`),
    INDEX `idx_warehouse` (`warehouse_id`),
    INDEX `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 14. جدول أوامر الإنتاج (إنشاء إذا لم يكن موجوداً)
-- ==========================================
CREATE TABLE IF NOT EXISTS `production_orders` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `order_number` VARCHAR(50) NOT NULL,
    `bom_id` INT UNSIGNED NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `quantity` DECIMAL(15,4) NOT NULL DEFAULT 1,
    `produced_quantity` DECIMAL(15,4) DEFAULT 0,
    `start_date` DATE NULL,
    `due_date` DATE NULL,
    `completion_date` DATETIME NULL,
    `priority` ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    `status` ENUM('draft', 'pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'draft',
    `output_warehouse_id` INT UNSIGNED NULL COMMENT 'مخزن استلام المنتج النهائي',
    `raw_material_warehouse_id` INT UNSIGNED NULL COMMENT 'مخزن صرف المواد الخام',
    `total_cost` DECIMAL(15,2) DEFAULT 0,
    `notes` TEXT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_order_number` (`company_id`, `order_number`),
    INDEX `idx_company` (`company_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 15. جدول قوائم المواد BOM (إنشاء إذا لم يكن موجوداً)
-- ==========================================
CREATE TABLE IF NOT EXISTS `production_bom` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `product_id` INT UNSIGNED NOT NULL COMMENT 'المنتج النهائي',
    `output_quantity` DECIMAL(15,4) DEFAULT 1,
    `is_active` TINYINT(1) DEFAULT 1,
    `notes` TEXT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_company` (`company_id`),
    INDEX `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 16. جدول بنود قوائم المواد
-- ==========================================
CREATE TABLE IF NOT EXISTS `production_bom_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `bom_id` INT UNSIGNED NOT NULL,
    `material_id` INT UNSIGNED NOT NULL COMMENT 'المادة الخام',
    `quantity` DECIMAL(15,4) NOT NULL,
    `unit_cost` DECIMAL(15,4) DEFAULT 0,
    `notes` TEXT NULL,
    INDEX `idx_bom` (`bom_id`),
    INDEX `idx_material` (`material_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 17. جدول مواد أوامر الإنتاج
-- ==========================================
CREATE TABLE IF NOT EXISTS `production_order_materials` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT UNSIGNED NOT NULL,
    `material_id` INT UNSIGNED NOT NULL,
    `planned_quantity` DECIMAL(15,4) NOT NULL,
    `consumed_quantity` DECIMAL(15,4) DEFAULT 0,
    `unit_cost` DECIMAL(15,4) DEFAULT 0,
    INDEX `idx_order` (`order_id`),
    INDEX `idx_material` (`material_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 18. جدول سجل الدفعات المرتبطة بالفواتير
-- ==========================================
CREATE TABLE IF NOT EXISTS `payment_allocations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `payment_id` INT UNSIGNED NOT NULL COMMENT 'رقم سند القبض/الصرف',
    `invoice_type` ENUM('sales', 'purchase') NOT NULL,
    `invoice_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_payment` (`payment_id`),
    INDEX `idx_invoice` (`invoice_type`, `invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- 19. تحديث صلاحيات إضافية
-- ==========================================
INSERT IGNORE INTO `permissions` (`module_id`, `name`, `name_ar`, `slug`) VALUES
(4, 'Manage Stock Transfers', 'إدارة التحويلات المخزنية', 'inventory.transfers'),
(4, 'Opening Stock', 'أرصدة أول المدة', 'inventory.opening'),
(2, 'View Agent Reports', 'تقارير المندوبين', 'sales.agent_reports');

SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================
-- نهاية التحديثات
-- ==========================================
