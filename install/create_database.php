<?php
/**
 * سكربت إنشاء قاعدة البيانات الكامل
 * Complete Database Setup Script
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');
echo "<html dir='rtl'><head><meta charset='utf-8'><title>إنشاء قاعدة البيانات</title>
<style>body{font-family:Arial;padding:30px;background:#1a1a2e;color:white;}
.success{color:#22c55e;}.error{color:#ef4444;}
pre{background:#2d2d44;padding:15px;border-radius:8px;}
h1{color:#FF6A00;}</style></head><body>";

echo "<h1>🔧 إنشاء قاعدة البيانات الكاملة</h1>";

try {
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p class='success'>✅ تم الاتصال بـ MySQL</p>";
    
    // حذف وإنشاء قاعدة البيانات
    $pdo->exec("DROP DATABASE IF EXISTS multierp");
    $pdo->exec("CREATE DATABASE multierp DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE multierp");
    echo "<p class='success'>✅ تم إنشاء قاعدة البيانات multierp</p>";
    
    // إنشاء الجداول
    $tables = [
        // 1. الشركات
        "CREATE TABLE companies (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            name_en VARCHAR(255) NULL,
            logo VARCHAR(500) NULL,
            address TEXT NULL,
            phone VARCHAR(50) NULL,
            email VARCHAR(255) NULL,
            tax_number VARCHAR(100) NULL,
            commercial_registry VARCHAR(100) NULL,
            currency VARCHAR(10) DEFAULT 'SAR',
            currency_symbol VARCHAR(10) DEFAULT 'ر.س',
            tax_rate DECIMAL(5,2) DEFAULT 15.00,
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        // 2. الأدوار
        "CREATE TABLE roles (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            name_ar VARCHAR(100) NOT NULL,
            description TEXT NULL,
            is_system TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        // 3. المستخدمين
        "CREATE TABLE users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            phone VARCHAR(50) NULL,
            avatar VARCHAR(500) NULL,
            role_id INT UNSIGNED NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            theme ENUM('light', 'dark') DEFAULT 'dark',
            language VARCHAR(10) DEFAULT 'ar',
            last_login TIMESTAMP NULL,
            last_login_ip VARCHAR(45) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (role_id) REFERENCES roles(id)
        ) ENGINE=InnoDB",
        
        // 4. ربط المستخدمين بالشركات
        "CREATE TABLE user_companies (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            company_id INT UNSIGNED NOT NULL,
            is_default TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            UNIQUE KEY user_company_unique (user_id, company_id)
        ) ENGINE=InnoDB",
        
        // 5. الموديولات
        "CREATE TABLE modules (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            name_ar VARCHAR(100) NOT NULL,
            slug VARCHAR(50) NOT NULL,
            icon VARCHAR(50) NULL,
            description TEXT NULL,
            sort_order INT DEFAULT 0,
            is_system TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        // 6. تفعيل الموديولات
        "CREATE TABLE company_modules (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            company_id INT UNSIGNED NOT NULL,
            module_id INT UNSIGNED NOT NULL,
            status ENUM('enabled', 'disabled') DEFAULT 'enabled',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
            UNIQUE KEY company_module_unique (company_id, module_id)
        ) ENGINE=InnoDB",
        
        // 7. الصلاحيات
        "CREATE TABLE permissions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            module_id INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        // 8. صلاحيات الأدوار
        "CREATE TABLE role_permissions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            role_id INT UNSIGNED NOT NULL,
            permission_id INT UNSIGNED NOT NULL,
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
            UNIQUE KEY role_permission_unique (role_id, permission_id)
        ) ENGINE=InnoDB",
        
        // 9. سجل المراجعة
        "CREATE TABLE audit_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            company_id INT UNSIGNED NULL,
            user_id INT UNSIGNED NULL,
            action VARCHAR(50) NOT NULL,
            table_name VARCHAR(100) NULL,
            record_id INT NULL,
            old_values JSON NULL,
            new_values JSON NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        // 10. التوكنات
        "CREATE TABLE api_tokens (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            token VARCHAR(500) NOT NULL,
            expires_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        
        // 11. العملاء
        "CREATE TABLE customers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            company_id INT UNSIGNED NOT NULL,
            code VARCHAR(50) NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NULL,
            phone VARCHAR(50) NULL,
            mobile VARCHAR(50) NULL,
            address TEXT NULL,
            city VARCHAR(100) NULL,
            tax_number VARCHAR(100) NULL,
            credit_limit DECIMAL(15,2) DEFAULT 0,
            balance DECIMAL(15,2) DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            notes TEXT NULL,
            created_by INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        
        // 12. الموردين
        "CREATE TABLE suppliers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            company_id INT UNSIGNED NOT NULL,
            code VARCHAR(50) NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NULL,
            phone VARCHAR(50) NULL,
            address TEXT NULL,
            tax_number VARCHAR(100) NULL,
            balance DECIMAL(15,2) DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        
        // 13. التصنيفات
        "CREATE TABLE categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            company_id INT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            parent_id INT UNSIGNED NULL,
            type ENUM('product', 'expense', 'income') DEFAULT 'product',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        
        // 14. الوحدات
        "CREATE TABLE units (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            company_id INT UNSIGNED NOT NULL,
            name VARCHAR(100) NOT NULL,
            symbol VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        
        // 15. المخازن
        "CREATE TABLE warehouses (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            company_id INT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            address TEXT NULL,
            is_default TINYINT(1) DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        
        // 16. المنتجات
        "CREATE TABLE products (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            company_id INT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            name_en VARCHAR(255) NULL,
            code VARCHAR(100) NULL,
            barcode VARCHAR(100) NULL,
            description TEXT NULL,
            category_id INT UNSIGNED NULL,
            unit_id INT UNSIGNED NULL,
            purchase_price DECIMAL(15,2) DEFAULT 0,
            selling_price DECIMAL(15,2) DEFAULT 0,
            min_selling_price DECIMAL(15,2) DEFAULT 0,
            wholesale_price DECIMAL(15,2) DEFAULT 0,
            tax_rate DECIMAL(5,2) DEFAULT 0,
            is_taxable TINYINT(1) DEFAULT 1,
            min_stock INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            is_service TINYINT(1) DEFAULT 0,
            track_inventory TINYINT(1) DEFAULT 1,
            image VARCHAR(500) NULL,
            created_by INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        
        // 17. المخزون
        "CREATE TABLE product_stock (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id INT UNSIGNED NOT NULL,
            warehouse_id INT UNSIGNED NOT NULL,
            quantity DECIMAL(15,3) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
            UNIQUE KEY product_warehouse (product_id, warehouse_id)
        ) ENGINE=InnoDB",
        
        // 18. فواتير المبيعات
        "CREATE TABLE sales_invoices (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            company_id INT UNSIGNED NOT NULL,
            invoice_number VARCHAR(50) NOT NULL,
            customer_id INT UNSIGNED NULL,
            invoice_date DATE NOT NULL,
            due_date DATE NULL,
            subtotal DECIMAL(15,2) DEFAULT 0,
            discount_amount DECIMAL(15,2) DEFAULT 0,
            tax_amount DECIMAL(15,2) DEFAULT 0,
            total DECIMAL(15,2) DEFAULT 0,
            paid_amount DECIMAL(15,2) DEFAULT 0,
            payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
            status ENUM('draft', 'confirmed', 'cancelled') DEFAULT 'draft',
            notes TEXT NULL,
            created_by INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        
        // 19. بنود فواتير المبيعات
        "CREATE TABLE sales_invoice_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            invoice_id INT UNSIGNED NOT NULL,
            product_id INT UNSIGNED NULL,
            description VARCHAR(255) NULL,
            quantity DECIMAL(15,3) DEFAULT 1,
            unit_id INT UNSIGNED NULL,
            unit_price DECIMAL(15,2) DEFAULT 0,
            discount_amount DECIMAL(15,2) DEFAULT 0,
            tax_rate DECIMAL(5,2) DEFAULT 0,
            tax_amount DECIMAL(15,2) DEFAULT 0,
            total DECIMAL(15,2) DEFAULT 0,
            FOREIGN KEY (invoice_id) REFERENCES sales_invoices(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        
        // 20. فواتير المشتريات
        "CREATE TABLE purchase_invoices (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            company_id INT UNSIGNED NOT NULL,
            invoice_number VARCHAR(50) NOT NULL,
            supplier_id INT UNSIGNED NULL,
            invoice_date DATE NOT NULL,
            subtotal DECIMAL(15,2) DEFAULT 0,
            tax_amount DECIMAL(15,2) DEFAULT 0,
            total DECIMAL(15,2) DEFAULT 0,
            paid_amount DECIMAL(15,2) DEFAULT 0,
            payment_status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
            status ENUM('draft', 'confirmed', 'cancelled') DEFAULT 'draft',
            created_by INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        
        // 21. بنود فواتير المشتريات
        "CREATE TABLE purchase_invoice_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            invoice_id INT UNSIGNED NOT NULL,
            product_id INT UNSIGNED NULL,
            quantity DECIMAL(15,3) DEFAULT 1,
            unit_price DECIMAL(15,2) DEFAULT 0,
            total DECIMAL(15,2) DEFAULT 0,
            FOREIGN KEY (invoice_id) REFERENCES purchase_invoices(id) ON DELETE CASCADE
        ) ENGINE=InnoDB"
    ];
    
    foreach ($tables as $i => $sql) {
        try {
            $pdo->exec($sql);
            echo "<p class='success'>✅ تم إنشاء الجدول " . ($i + 1) . "</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>❌ خطأ في الجدول " . ($i + 1) . ": " . $e->getMessage() . "</p>";
        }
    }
    
    // إدخال البيانات الأساسية
    echo "<h2>إدخال البيانات الأساسية</h2>";
    
    // الأدوار
    $pdo->exec("INSERT INTO roles (name, name_ar, is_system) VALUES 
        ('super_admin', 'مدير النظام', 1),
        ('manager', 'مدير الشركة', 1),
        ('accountant', 'محاسب', 1),
        ('sales', 'موظف مبيعات', 1),
        ('storekeeper', 'أمين مخزن', 1)");
    echo "<p class='success'>✅ تم إضافة الأدوار</p>";
    
    // الشركة
    $pdo->exec("INSERT INTO companies (name, name_en, status) VALUES ('شركتي الأولى', 'My First Company', 'active')");
    echo "<p class='success'>✅ تم إنشاء الشركة</p>";
    
    // المستخدم
    $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
    $pdo->exec("INSERT INTO users (username, email, password, full_name, role_id, is_active) 
                VALUES ('admin', 'admin@system.com', '$hashedPassword', 'مدير النظام', 1, 1)");
    echo "<p class='success'>✅ تم إنشاء المستخدم admin</p>";
    
    // ربط المستخدم بالشركة
    $pdo->exec("INSERT INTO user_companies (user_id, company_id, is_default) VALUES (1, 1, 1)");
    echo "<p class='success'>✅ تم ربط المستخدم بالشركة</p>";
    
    // الموديولات
    $pdo->exec("INSERT INTO modules (name, name_ar, slug, icon, sort_order, is_system) VALUES 
        ('Dashboard', 'لوحة التحكم', 'dashboard', 'fas fa-tachometer-alt', 1, 1),
        ('Sales', 'المبيعات', 'sales', 'fas fa-shopping-cart', 2, 0),
        ('Purchases', 'المشتريات', 'purchases', 'fas fa-truck', 3, 0),
        ('Inventory', 'المخازن', 'inventory', 'fas fa-warehouse', 4, 0),
        ('Accounting', 'الحسابات', 'accounting', 'fas fa-calculator', 5, 0),
        ('Settings', 'الإعدادات', 'settings', 'fas fa-cog', 8, 1)");
    echo "<p class='success'>✅ تم إضافة الموديولات</p>";
    
    // تفعيل الموديولات
    $pdo->exec("INSERT INTO company_modules (company_id, module_id, status) VALUES 
        (1, 1, 'enabled'), (1, 2, 'enabled'), (1, 3, 'enabled'), 
        (1, 4, 'enabled'), (1, 5, 'enabled'), (1, 6, 'enabled')");
    echo "<p class='success'>✅ تم تفعيل الموديولات</p>";
    
    // وحدات
    $pdo->exec("INSERT INTO units (company_id, name, symbol) VALUES (1, 'قطعة', 'حبة'), (1, 'كيلو', 'كغ')");
    echo "<p class='success'>✅ تم إضافة الوحدات</p>";
    
    // مخزن
    $pdo->exec("INSERT INTO warehouses (company_id, name, is_default) VALUES (1, 'المخزن الرئيسي', 1)");
    echo "<p class='success'>✅ تم إنشاء المخزن</p>";
    
    echo "<hr>";
    echo "<h2 style='color:#22c55e;'>🎉 تم إنشاء قاعدة البيانات بنجاح!</h2>";
    echo "<div style='background:#2d2d44;padding:20px;border-radius:10px;margin-top:20px;'>";
    echo "<p><a href='pages/login.php' style='color:#FF6A00;font-size:1.3em;text-decoration:none;'>➡️ الذهاب لصفحة تسجيل الدخول</a></p>";
    echo "<p style='margin-top:15px;'>المستخدم: <strong style='color:#22c55e;'>admin</strong></p>";
    echo "<p>كلمة المرور: <strong style='color:#22c55e;'>admin123</strong></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ خطأ: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
