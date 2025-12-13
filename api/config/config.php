<?php
/**
 * ملف التكوين الرئيسي
 * Main Configuration File
 */

// منع الوصول المباشر
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

// دالة لقراءة ملف .env
if (!function_exists('loadEnv')) {
    function loadEnv($path) {
        if (!file_exists($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// تحميل متغيرات البيئة
loadEnv(BASE_PATH . '/.env');

// دالة مساعدة للحصول على المتغيرات
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return $value;
}

// وضع التطوير
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_DEBUG', filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN));

// معلومات التطبيق
define('APP_NAME', 'نظام ERP متعدد الشركات');
define('APP_VERSION', '1.0.0');
define('APP_URL', env('APP_URL', 'http://localhost/multierp'));
define('API_URL', APP_URL . '/api/v1');

// إعدادات قاعدة البيانات
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'multierp'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');

// إعدادات JWT
define('JWT_SECRET', env('JWT_SECRET', 'your-secret-key-change-this-in-production-' . md5('multierp')));
define('JWT_EXPIRY', 86400); // 24 ساعة
define('JWT_REFRESH_EXPIRY', 604800); // 7 أيام

// إعدادات الأمان
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_NAME', 'MULTIERP_SESSION');
define('SESSION_LIFETIME', 7200); // ساعتين
define('PASSWORD_MIN_LENGTH', 6);

// إعدادات الملفات
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'xlsx', 'xls', 'csv']);

// إعدادات Rate Limiting
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 60); // دقيقة واحدة

// إعدادات التقارير والتصدير
define('ITEMS_PER_PAGE', 25);
define('MAX_EXPORT_ROWS', 10000);

// المنطقة الزمنية
date_default_timezone_set('Asia/Riyadh');

// إعدادات الأخطاء
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// تشغيل الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
