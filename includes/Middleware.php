<?php
/**
 * Middleware للتحقق من الطلبات
 * Request Middleware Class
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

class Middleware {
    
    /**
     * التحقق من تسجيل الدخول
     */
    public static function auth(): bool {
        if (!Auth::check()) {
            self::sendError('غير مصرح - يرجى تسجيل الدخول', 401);
            return false;
        }
        return true;
    }
    
    /**
     * التحقق من صلاحية معينة
     */
    public static function permission(string $permission): bool {
        if (!self::auth()) {
            return false;
        }
        
        if (!Auth::can($permission)) {
            self::sendError('غير مصرح - ليس لديك صلاحية للوصول', 403);
            return false;
        }
        return true;
    }
    
    /**
     * التحقق من دور معين
     */
    public static function role(string $role): bool {
        if (!self::auth()) {
            return false;
        }
        
        if (!Auth::hasRole($role)) {
            self::sendError('غير مصرح - تحتاج صلاحيات ' . $role, 403);
            return false;
        }
        return true;
    }
    
    /**
     * التحقق من تفعيل الموديول
     */
    public static function moduleEnabled(string $moduleSlug): bool {
        if (!self::auth()) {
            return false;
        }
        
        $user = Auth::user();
        $db = Database::getInstance();
        
        $module = $db->fetch(
            "SELECT cm.status FROM company_modules cm
             JOIN modules m ON cm.module_id = m.id
             WHERE cm.company_id = ? AND m.slug = ?",
            [$user['company_id'], $moduleSlug]
        );
        
        if (!$module || $module['status'] !== 'enabled') {
            self::sendError('هذا الموديول غير مفعل', 403);
            return false;
        }
        
        return true;
    }
    
    /**
     * Rate Limiting
     */
    public static function rateLimit(): bool {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'rate_limit_' . md5($ip);
        
        // استخدام ملف مؤقت للتتبع
        $file = sys_get_temp_dir() . '/' . $key . '.json';
        
        $data = ['count' => 0, 'reset_at' => time() + RATE_LIMIT_WINDOW];
        
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true) ?: $data;
            
            if ($data['reset_at'] < time()) {
                $data = ['count' => 0, 'reset_at' => time() + RATE_LIMIT_WINDOW];
            }
        }
        
        $data['count']++;
        @file_put_contents($file, json_encode($data));
        
        if ($data['count'] > RATE_LIMIT_REQUESTS) {
            self::sendError('تم تجاوز الحد المسموح من الطلبات', 429);
            return false;
        }
        
        return true;
    }
    
    /**
     * التحقق من CSRF Token
     */
    public static function csrfCheck(): bool {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return true;
        }
        
        $token = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $sessionToken = $_SESSION[CSRF_TOKEN_NAME] ?? '';
        
        if (empty($token) || !hash_equals($sessionToken, $token)) {
            self::sendError('خطأ في التحقق - يرجى إعادة المحاولة', 403);
            return false;
        }
        
        return true;
    }
    
    /**
     * توليد CSRF Token
     */
    public static function generateCsrfToken(): string {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
    
    /**
 * تنظيف المدخلات من XSS
 */
public static function sanitize($input) {
    if (is_array($input)) {
        return array_map([self::class, 'sanitize'], $input);
    }

    // تجنب التحذير عند null
    if ($input === null) {
        return '';
    }

    // تحويل الأرقام كما هي دون تشويه
    if (is_numeric($input)) {
        return $input;
    }

    // هنا نضمن أنه string قبل عمل trim
    $input = trim((string)$input);

    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

    
    /**
     * الحصول على JSON من الطلب
     */
    public static function getJsonInput(): array {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        return is_array($data) ? self::sanitize($data) : [];
    }
    
    /**
     * الحصول على معلمات GET المنظفة
     */
    public static function getQuery(): array {
        return self::sanitize($_GET);
    }
    
    /**
     * الحصول على معلمات POST المنظفة
     */
    public static function getPost(): array {
        return self::sanitize($_POST);
    }
    
    /**
     * إرسال استجابة JSON
     */
    public static function sendJson(array $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
    
    /**
     * إرسال خطأ JSON
     */
    public static function sendError(string $message, int $code = 400): void {
        self::sendJson([
            'success' => false,
            'message' => $message,
            'code' => $code
        ], $code);
    }
    
    /**
     * إرسال نجاح JSON
     */
    public static function sendSuccess($data = null, string $message = 'تمت العملية بنجاح'): void {
        self::sendJson([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
    
    /**
     * التحقق من الحقول المطلوبة
     */
    public static function validate(array $data, array $rules): array {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $rulesList = explode('|', $rule);
            
            foreach ($rulesList as $r) {
                $parts = explode(':', $r);
                $ruleName = $parts[0];
                $param = $parts[1] ?? null;
                
                switch ($ruleName) {
                    case 'required':
                        if (empty($value) && $value !== '0') {
                            $errors[$field] = "حقل {$field} مطلوب";
                        }
                        break;
                    case 'email':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = "البريد الإلكتروني غير صالح";
                        }
                        break;
                    case 'min':
                        if (!empty($value) && strlen($value) < (int)$param) {
                            $errors[$field] = "يجب أن يكون {$field} على الأقل {$param} أحرف";
                        }
                        break;
                    case 'max':
                        if (!empty($value) && strlen($value) > (int)$param) {
                            $errors[$field] = "يجب ألا يتجاوز {$field} عن {$param} أحرف";
                        }
                        break;
                    case 'numeric':
                        if (!empty($value) && !is_numeric($value)) {
                            $errors[$field] = "{$field} يجب أن يكون رقم";
                        }
                        break;
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * إعداد CORS Headers
     */
    public static function cors(): void {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
        header('Access-Control-Max-Age: 86400');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
