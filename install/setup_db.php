<?php
/**
 * سكربت إعداد قاعدة البيانات السريع
 * Quick Database Setup Script
 * 
 * قم بتشغيل هذا الملف مرة واحدة لإنشاء قاعدة البيانات
 * http://localhost/multierp/install/setup_db.php
 */

// إعدادات الاتصال
$host = 'localhost';
$dbname = 'multierp';
$username = 'root';
$password = ''; // فارغ للـ XAMPP

echo "<html dir='rtl'><head><meta charset='utf-8'><title>إعداد قاعدة البيانات</title>
<style>body{font-family:Arial;padding:30px;background:#1a1a2e;color:white;}
.success{color:#22c55e;}.error{color:#ef4444;}pre{background:#2d2d44;padding:15px;border-radius:8px;overflow:auto;}
h1{color:#FF6A00;}</style></head><body>";
echo "<h1>🔧 إعداد قاعدة بيانات نظام ERP</h1>";

try {
    // الاتصال بـ MySQL
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p class='success'>✅ تم الاتصال بـ MySQL بنجاح</p>";
    
    // إنشاء قاعدة البيانات
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p class='success'>✅ تم إنشاء قاعدة البيانات: $dbname</p>";
    
    $pdo->exec("USE `$dbname`");
    
    // قراءة وتنفيذ ملفات SQL
    $sqlFiles = ['database.sql', 'database_part2.sql', 'database_part3.sql', 'database_part4.sql'];
    
    foreach ($sqlFiles as $file) {
        $path = __DIR__ . '/' . $file;
        if (file_exists($path)) {
            $sql = file_get_contents($path);
            
            // تقسيم بالـ delimiter
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $stmt) {
                if (!empty($stmt) && !preg_match('/^(--|#|\/\*)/', trim($stmt))) {
                    try {
                        $pdo->exec($stmt);
                    } catch (PDOException $e) {
                        // تجاهل الأخطاء للجداول الموجودة
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            echo "<p class='error'>⚠️ تحذير في $file: " . $e->getMessage() . "</p>";
                        }
                    }
                }
            }
            echo "<p class='success'>✅ تم تنفيذ: $file</p>";
        } else {
            echo "<p class='error'>❌ الملف غير موجود: $file</p>";
        }
    }
    
    // التحقق من وجود المستخدم admin
    $adminCheck = $pdo->query("SELECT id, username FROM users WHERE username = 'admin'")->fetch();
    
    if (!$adminCheck) {
        // إنشاء المستخدم admin
        $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
        $pdo->exec("INSERT INTO users (role_id, username, password, full_name, email, is_active) 
                    VALUES (1, 'admin', '$hashedPassword', 'مدير النظام', 'admin@system.com', 1)");
        echo "<p class='success'>✅ تم إنشاء المستخدم admin بكلمة مرور: admin123</p>";
        $adminId = $pdo->lastInsertId();
    } else {
        // تحديث كلمة المرور
        $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
        $pdo->exec("UPDATE users SET password = '$hashedPassword', is_active = 1 WHERE username = 'admin'");
        echo "<p class='success'>✅ تم تحديث كلمة مرور admin إلى: admin123</p>";
        $adminId = $adminCheck['id'];
    }
    
    // ربط المستخدم بالشركة الافتراضية
    $companyCheck = $pdo->query("SELECT id FROM companies LIMIT 1")->fetch();
    if (!$companyCheck) {
        // إنشاء شركة افتراضية
        $pdo->exec("INSERT INTO companies (name, name_en, status) VALUES ('شركتي الأولى', 'My First Company', 'active')");
        $companyId = $pdo->lastInsertId();
        echo "<p class='success'>✅ تم إنشاء شركة افتراضية</p>";
    } else {
        $companyId = $companyCheck['id'];
    }
    
    // ربط المستخدم بالشركة
    $linkCheck = $pdo->query("SELECT * FROM user_companies WHERE user_id = $adminId AND company_id = $companyId")->fetch();
    if (!$linkCheck) {
        $pdo->exec("INSERT INTO user_companies (user_id, company_id, is_default) VALUES ($adminId, $companyId, 1)");
        echo "<p class='success'>✅ تم ربط المستخدم admin بالشركة</p>";
    } else {
        echo "<p class='success'>✅ المستخدم admin مرتبط بالشركة بالفعل</p>";
    }
    
    // تفعيل الموديولات الأساسية
    $pdo->exec("INSERT IGNORE INTO company_modules (company_id, module_id, status) 
                SELECT 1, id, 'enabled' FROM modules WHERE is_system = 1");
    echo "<p class='success'>✅ تم تفعيل الموديولات الأساسية</p>";
    
    echo "<hr>";
    echo "<h2 class='success'>🎉 تم الإعداد بنجاح!</h2>";
    echo "<p>يمكنك الآن تسجيل الدخول:</p>";
    echo "<pre>
الرابط: <a href='../pages/login.php' style='color:#FF6A00'>pages/login.php</a>
اسم المستخدم: admin
كلمة المرور: admin123
</pre>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ خطأ: " . $e->getMessage() . "</p>";
    echo "<p>تأكد من:</p>";
    echo "<ul><li>تشغيل XAMPP (Apache + MySQL)</li><li>صحة بيانات الاتصال</li></ul>";
}

echo "</body></html>";
