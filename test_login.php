<?php
/**
 * اختبار تسجيل الدخول - للتشخيص
 * Login Debug Test
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');
echo "<html dir='rtl'><head><meta charset='utf-8'><title>اختبار تسجيل الدخول</title>
<style>body{font-family:Arial;padding:30px;background:#1a1a2e;color:white;}
.success{color:#22c55e;}.error{color:#ef4444;}.warn{color:#f59e0b;}
pre{background:#2d2d44;padding:15px;border-radius:8px;overflow:auto;white-space:pre-wrap;}
h1{color:#FF6A00;}</style></head><body>";

echo "<h1>🔍 اختبار تسجيل الدخول</h1>";

// 1. اختبار الاتصال بقاعدة البيانات
echo "<h2>1. اختبار الاتصال بقاعدة البيانات</h2>";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=multierp;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p class='success'>✅ تم الاتصال بقاعدة البيانات</p>";
} catch (PDOException $e) {
    echo "<p class='error'>❌ خطأ في الاتصال: " . $e->getMessage() . "</p>";
    exit;
}

// 2. التحقق من جدول المستخدمين
echo "<h2>2. التحقق من المستخدم admin</h2>";
$user = $pdo->query("SELECT id, username, email, full_name, role_id, is_active, password FROM users WHERE username = 'admin'")->fetch(PDO::FETCH_ASSOC);
if ($user) {
    echo "<p class='success'>✅ المستخدم موجود</p>";
    echo "<pre>ID: {$user['id']}\nUsername: {$user['username']}\nEmail: {$user['email']}\nName: {$user['full_name']}\nRole ID: {$user['role_id']}\nActive: {$user['is_active']}\nPassword Hash: " . substr($user['password'], 0, 30) . "...</pre>";
    
    // اختبار كلمة المرور
    if (password_verify('admin123', $user['password'])) {
        echo "<p class='success'>✅ كلمة المرور صحيحة</p>";
    } else {
        echo "<p class='error'>❌ كلمة المرور غير صحيحة - سيتم إعادة تعيينها</p>";
        $newHash = password_hash('admin123', PASSWORD_BCRYPT);
        $pdo->exec("UPDATE users SET password = '$newHash' WHERE id = " . $user['id']);
        echo "<p class='success'>✅ تم تحديث كلمة المرور</p>";
    }
} else {
    echo "<p class='error'>❌ المستخدم admin غير موجود - سيتم إنشاؤه</p>";
    $hash = password_hash('admin123', PASSWORD_BCRYPT);
    $pdo->exec("INSERT INTO users (role_id, username, password, full_name, email, is_active) VALUES (1, 'admin', '$hash', 'مدير النظام', 'admin@system.com', 1)");
    echo "<p class='success'>✅ تم إنشاء المستخدم</p>";
    $user = $pdo->query("SELECT id FROM users WHERE username = 'admin'")->fetch(PDO::FETCH_ASSOC);
}

// 3. التحقق من الشركة
echo "<h2>3. التحقق من الشركة</h2>";
$company = $pdo->query("SELECT * FROM companies LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($company) {
    echo "<p class='success'>✅ الشركة موجودة</p>";
    echo "<pre>ID: {$company['id']}\nName: {$company['name']}\nStatus: {$company['status']}</pre>";
} else {
    echo "<p class='error'>❌ لا توجد شركات - سيتم إنشاء واحدة</p>";
    $pdo->exec("INSERT INTO companies (name, name_en, status) VALUES ('شركتي الأولى', 'My Company', 'active')");
    $company = $pdo->query("SELECT * FROM companies LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    echo "<p class='success'>✅ تم إنشاء الشركة</p>";
}

// 4. التحقق من ربط المستخدم بالشركة
echo "<h2>4. التحقق من ربط المستخدم بالشركة</h2>";
$link = $pdo->query("SELECT * FROM user_companies WHERE user_id = {$user['id']}")->fetch(PDO::FETCH_ASSOC);
if ($link) {
    echo "<p class='success'>✅ المستخدم مرتبط بالشركة</p>";
    echo "<pre>User ID: {$link['user_id']}\nCompany ID: {$link['company_id']}\nDefault: {$link['is_default']}</pre>";
} else {
    echo "<p class='error'>❌ المستخدم غير مرتبط بالشركة - سيتم الربط</p>";
    $pdo->exec("INSERT INTO user_companies (user_id, company_id, is_default) VALUES ({$user['id']}, {$company['id']}, 1)");
    echo "<p class='success'>✅ تم ربط المستخدم بالشركة</p>";
}

// 5. اختبار API تسجيل الدخول
echo "<h2>5. اختبار API تسجيل الدخول</h2>";

// تضمين الملفات
echo "<p>جاري تحميل الملفات...</p>";

$configPath = __DIR__ . '/api/config/config.php';
$dbPath = __DIR__ . '/includes/Database.php';
$jwtPath = __DIR__ . '/includes/JWT.php';
$authPath = __DIR__ . '/includes/Auth.php';

echo "<pre>";
echo "Config: " . (file_exists($configPath) ? "✅" : "❌") . " $configPath\n";
echo "Database: " . (file_exists($dbPath) ? "✅" : "❌") . " $dbPath\n";
echo "JWT: " . (file_exists($jwtPath) ? "✅" : "❌") . " $jwtPath\n";
echo "Auth: " . (file_exists($authPath) ? "✅" : "❌") . " $authPath\n";
echo "</pre>";

try {
    require_once $configPath;
    echo "<p class='success'>✅ تم تحميل Config</p>";
    
    require_once $dbPath;
    echo "<p class='success'>✅ تم تحميل Database</p>";
    
    require_once $jwtPath;
    echo "<p class='success'>✅ تم تحميل JWT</p>";
    
    require_once $authPath;
    echo "<p class='success'>✅ تم تحميل Auth</p>";
    
    // اختبار تسجيل الدخول
    echo "<h2>6. تجربة تسجيل الدخول</h2>";
    $result = Auth::login('admin', 'admin123');
    
    if ($result['success']) {
        echo "<p class='success'>✅ تسجيل الدخول ناجح!</p>";
        echo "<pre>" . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p class='error'>❌ فشل تسجيل الدخول: " . $result['message'] . "</p>";
    }
    
} catch (Throwable $e) {
    echo "<p class='error'>❌ خطأ: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h2 class='success'>🎉 الآن جرب تسجيل الدخول</h2>";
echo "<p><a href='pages/login.php' style='color:#FF6A00;font-size:1.2em;'>➡️ صفحة تسجيل الدخول</a></p>";
echo "<p>المستخدم: <strong>admin</strong></p>";
echo "<p>كلمة المرور: <strong>admin123</strong></p>";

echo "</body></html>";
