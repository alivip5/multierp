<?php
/**
 * سكربت التثبيت
 * Installation Script
 */

session_start();

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// التحقق من التثبيت المسبق
$configFile = __DIR__ . '/../api/config/config.php';
$installed = file_exists(__DIR__ . '/.installed');

if ($installed && $step < 4) {
    header('Location: ../index.php');
    exit;
}

// معالجة الخطوات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 2) {
        // اختبار الاتصال بقاعدة البيانات
        $dbHost = $_POST['db_host'] ?? 'localhost';
        $dbName = $_POST['db_name'] ?? 'multierp';
        $dbUser = $_POST['db_user'] ?? 'root';
        $dbPass = $_POST['db_pass'] ?? '';
        
        try {
            $pdo = new PDO("mysql:host=$dbHost", $dbUser, $dbPass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // إنشاء قاعدة البيانات إذا لم تكن موجودة
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$dbName`");
            
            // تنفيذ ملفات SQL
            $sqlFiles = ['database.sql', 'database_part2.sql', 'database_part3.sql', 'database_part4.sql'];
            foreach ($sqlFiles as $file) {
                $sqlPath = __DIR__ . '/' . $file;
                if (file_exists($sqlPath)) {
                    $sql = file_get_contents($sqlPath);
                    // تقسيم الأوامر وتنفيذها
                    $pdo->exec($sql);
                }
            }
            
            // تحديث ملف التكوين
            $configContent = file_get_contents($configFile);
            $configContent = preg_replace("/define\('DB_HOST', '.*?'\)/", "define('DB_HOST', '$dbHost')", $configContent);
            $configContent = preg_replace("/define\('DB_NAME', '.*?'\)/", "define('DB_NAME', '$dbName')", $configContent);
            $configContent = preg_replace("/define\('DB_USER', '.*?'\)/", "define('DB_USER', '$dbUser')", $configContent);
            $configContent = preg_replace("/define\('DB_PASS', '.*?'\)/", "define('DB_PASS', '$dbPass')", $configContent);
            file_put_contents($configFile, $configContent);
            
            $_SESSION['install_db'] = true;
            header('Location: index.php?step=3');
            exit;
        } catch (PDOException $e) {
            $error = 'خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
        }
    }
    
    if ($step == 3) {
        // إنشاء حساب المدير
        require_once __DIR__ . '/../api/config/config.php';
        require_once __DIR__ . '/../includes/Database.php';
        require_once __DIR__ . '/../includes/Auth.php';
        
        $companyName = $_POST['company_name'] ?? 'شركتي';
        $adminName = $_POST['admin_name'] ?? 'مدير النظام';
        $adminEmail = $_POST['admin_email'] ?? 'admin@system.com';
        $adminPassword = $_POST['admin_password'] ?? 'admin';
        
        try {
            $db = Database::getInstance();
            
            // تحديث بيانات الشركة
            $db->update('companies', ['name' => $companyName], 'id = 1');
            
            // تحديث بيانات المدير
            $db->update('users', [
                'full_name' => $adminName,
                'email' => $adminEmail,
                'password' => Auth::hashPassword($adminPassword)
            ], 'id = 1');
            
            // إنشاء ملف التثبيت
            file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));
            
            header('Location: index.php?step=4');
            exit;
        } catch (Exception $e) {
            $error = 'خطأ: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تثبيت نظام ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .installer {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #FF6A00, #CC5500);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 36px;
            color: white;
        }
        h1 { color: white; font-size: 1.5rem; margin-bottom: 5px; }
        .subtitle { color: rgba(255,255,255,0.6); font-size: 0.9rem; }
        
        .steps {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 30px 0;
        }
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        .step.active .step-circle {
            background: linear-gradient(135deg, #FF6A00, #CC5500);
            color: white;
        }
        .step.done .step-circle {
            background: #22c55e;
            color: white;
        }
        .step-label {
            color: rgba(255,255,255,0.5);
            font-size: 0.8rem;
        }
        .step.active .step-label { color: white; }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; color: rgba(255,255,255,0.8); margin-bottom: 8px; font-size: 0.9rem; }
        input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            font-family: inherit;
        }
        input:focus {
            outline: none;
            border-color: #FF6A00;
        }
        input::placeholder { color: rgba(255,255,255,0.4); }
        
        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #FF6A00, #CC5500);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(255,106,0,0.4); }
        
        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .alert-error { background: rgba(239,68,68,0.15); color: #fca5a5; }
        .alert-success { background: rgba(34,197,94,0.15); color: #86efac; }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: #22c55e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            color: white;
        }
        .info-box {
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 16px;
            margin: 20px 0;
        }
        .info-box p { color: rgba(255,255,255,0.7); margin-bottom: 8px; font-size: 0.9rem; }
        .info-box strong { color: #FF6A00; }
    </style>
</head>
<body>
    <div class="installer">
        <div class="logo">
            <div class="logo-icon">🏢</div>
            <h1>نظام ERP متعدد الشركات</h1>
            <p class="subtitle">معالج التثبيت</p>
        </div>
        
        <div class="steps">
            <div class="step <?= $step >= 1 ? ($step > 1 ? 'done' : 'active') : '' ?>">
                <div class="step-circle"><?= $step > 1 ? '✓' : '1' ?></div>
                <span class="step-label">الترحيب</span>
            </div>
            <div class="step <?= $step >= 2 ? ($step > 2 ? 'done' : 'active') : '' ?>">
                <div class="step-circle"><?= $step > 2 ? '✓' : '2' ?></div>
                <span class="step-label">قاعدة البيانات</span>
            </div>
            <div class="step <?= $step >= 3 ? ($step > 3 ? 'done' : 'active') : '' ?>">
                <div class="step-circle"><?= $step > 3 ? '✓' : '3' ?></div>
                <span class="step-label">الإعدادات</span>
            </div>
            <div class="step <?= $step >= 4 ? 'active' : '' ?>">
                <div class="step-circle">4</div>
                <span class="step-label">انتهاء</span>
            </div>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($step == 1): ?>
        <div style="text-align: center; color: rgba(255,255,255,0.8);">
            <p style="margin-bottom: 20px;">مرحباً بك في معالج تثبيت نظام ERP</p>
            <p style="margin-bottom: 30px; font-size: 0.9rem; color: rgba(255,255,255,0.6);">
                سيساعدك هذا المعالج في إعداد النظام على خادمك
            </p>
            <a href="?step=2" class="btn" style="display: inline-block; text-decoration: none;">
                ابدأ التثبيت
            </a>
        </div>
        
        <?php elseif ($step == 2): ?>
        <form method="POST">
            <div class="form-group">
                <label>خادم قاعدة البيانات</label>
                <input type="text" name="db_host" value="localhost" required>
            </div>
            <div class="form-group">
                <label>اسم قاعدة البيانات</label>
                <input type="text" name="db_name" value="multierp" required>
            </div>
            <div class="form-group">
                <label>اسم المستخدم</label>
                <input type="text" name="db_user" value="root" required>
            </div>
            <div class="form-group">
                <label>كلمة المرور</label>
                <input type="password" name="db_pass" value="" placeholder="اتركها فارغة إذا لم تكن محمية">
            </div>
            <button type="submit" class="btn">إنشاء قاعدة البيانات</button>
        </form>
        
        <?php elseif ($step == 3): ?>
        <form method="POST">
            <div class="form-group">
                <label>اسم الشركة</label>
                <input type="text" name="company_name" value="شركتي" required>
            </div>
            <div class="form-group">
                <label>اسم المدير</label>
                <input type="text" name="admin_name" value="مدير النظام" required>
            </div>
            <div class="form-group">
                <label>البريد الإلكتروني</label>
                <input type="email" name="admin_email" value="admin@system.com" required>
            </div>
            <div class="form-group">
                <label>كلمة المرور</label>
                <input type="password" name="admin_password" value="admin" required>
            </div>
            <button type="submit" class="btn">إنهاء التثبيت</button>
        </form>
        
        <?php elseif ($step == 4): ?>
        <div style="text-align: center;">
            <div class="success-icon">✓</div>
            <h2 style="color: white; margin-bottom: 10px;">تم التثبيت بنجاح!</h2>
            <p style="color: rgba(255,255,255,0.7); margin-bottom: 20px;">تم إعداد نظام ERP بنجاح</p>
            
            <div class="info-box">
                <p><strong>رابط الدخول:</strong> /pages/login.php</p>
                <p><strong>اسم المستخدم:</strong> admin</p>
                <p><strong>كلمة المرور:</strong> التي أدخلتها</p>
            </div>
            
            <a href="../pages/login.php" class="btn" style="display: inline-block; text-decoration: none;">
                الدخول للنظام
            </a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
