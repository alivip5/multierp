<?php
/**
 * فئة Auth للتحقق من المستخدمين
 * Authentication Class
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/JWT.php';

class Auth {
    private static $currentUser = null;
    private static $currentCompany = null;
    
    /**
     * تسجيل الدخول
     */
    public static function login(string $username, string $password, ?int $companyId = null): array {
        $db = Database::getInstance();
        
        // البحث عن المستخدم
        $user = $db->fetch(
            "SELECT u.*, r.name as role_name, r.name_ar as role_name_ar 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE (u.username = ? OR u.email = ?) AND u.is_active = 1",
            [$username, $username]
        );
        
        if (!$user) {
            return ['success' => false, 'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة'];
        }
        
        // التحقق من كلمة المرور
        if (!password_verify($password, $user['password'])) {
            self::logAudit($user['id'], 'login_failed', 'users', $user['id']);
            return ['success' => false, 'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة'];
        }
        
        // الحصول على شركات المستخدم
        $companies = $db->fetchAll(
            "SELECT c.*, uc.is_default 
             FROM companies c 
             JOIN user_companies uc ON c.id = uc.company_id 
             WHERE uc.user_id = ? AND c.status = 'active'
             ORDER BY uc.is_default DESC",
            [$user['id']]
        );
        
        if (empty($companies)) {
            return ['success' => false, 'message' => 'لا توجد شركات مرتبطة بهذا المستخدم'];
        }
        
        // اختيار الشركة
        $selectedCompany = null;
        if ($companyId) {
            foreach ($companies as $company) {
                if ($company['id'] == $companyId) {
                    $selectedCompany = $company;
                    break;
                }
            }
            if (!$selectedCompany) {
                return ['success' => false, 'message' => 'لا يمكنك الوصول لهذه الشركة'];
            }
        } else {
            $selectedCompany = $companies[0];
        }
        
        // توليد التوكن
        $token = JWT::generate([
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role_name'],
            'company_id' => $selectedCompany['id']
        ]);
        
        $refreshToken = JWT::generateRefreshToken($user['id']);
        
        // تحديث آخر تسجيل دخول
        $db->update('users', [
            'last_login' => date('Y-m-d H:i:s'),
            'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ], 'id = ?', [$user['id']]);
        
        // تسجيل في سجل المراجعة
        self::logAudit($user['id'], 'login', 'users', $user['id'], null, null, $selectedCompany['id']);
        
        // إزالة كلمة المرور من البيانات المرجعة
        unset($user['password']);
        
        return [
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'data' => [
                'user' => $user,
                'company' => $selectedCompany,
                'companies' => $companies,
                'token' => $token,
                'refresh_token' => $refreshToken,
                'expires_in' => JWT_EXPIRY
            ]
        ];
    }
    
    /**
     * تسجيل الخروج
     */
    public static function logout(): array {
        $user = JWT::getCurrentUser();
        
        if ($user) {
            $db = Database::getInstance();
            $db->delete('api_tokens', 'user_id = ?', [$user['user_id']]);
            self::logAudit($user['user_id'], 'logout', 'users', $user['user_id']);
        }
        
        session_destroy();
        
        return ['success' => true, 'message' => 'تم تسجيل الخروج بنجاح'];
    }
    
    /**
     * الحصول على المستخدم الحالي
     */
    public static function user(): ?array {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }
        
        $db = Database::getInstance();
        
        // Try JWT first
        $payload = JWT::getCurrentUser();
        
        if ($payload) {
            self::$currentUser = $db->fetch(
                "SELECT u.*, r.name as role_name, r.name_ar as role_name_ar 
                 FROM users u 
                 JOIN roles r ON u.role_id = r.id 
                 WHERE u.id = ? AND u.is_active = 1",
                [$payload['user_id']]
            );
            
            if (self::$currentUser) {
                unset(self::$currentUser['password']);
                self::$currentUser['company_id'] = $payload['company_id'];
            }
            
            return self::$currentUser;
        }
        
        // Fallback to session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!empty($_SESSION['user_id']) && !empty($_SESSION['company_id'])) {
            self::$currentUser = $db->fetch(
                "SELECT u.*, r.name as role_name, r.name_ar as role_name_ar 
                 FROM users u 
                 JOIN roles r ON u.role_id = r.id 
                 WHERE u.id = ? AND u.is_active = 1",
                [$_SESSION['user_id']]
            );
            
            if (self::$currentUser) {
                unset(self::$currentUser['password']);
                self::$currentUser['company_id'] = $_SESSION['company_id'];
            }
            
            return self::$currentUser;
        }
        
        return null;
    }
    
    /**
     * الحصول على الشركة الحالية
     */
    public static function company(): ?array {
        if (self::$currentCompany !== null) {
            return self::$currentCompany;
        }
        
        $user = self::user();
        
        if (!$user || !isset($user['company_id'])) {
            return null;
        }
        
        $db = Database::getInstance();
        self::$currentCompany = $db->fetch(
            "SELECT * FROM companies WHERE id = ? AND status = 'active'",
            [$user['company_id']]
        );
        
        return self::$currentCompany;
    }
    
    /**
     * التحقق من تسجيل الدخول
     */
    public static function check(): bool {
        return self::user() !== null;
    }
    
    /**
     * التحقق من صلاحية معينة
     */
    public static function can(string $permission): bool {
        $user = self::user();
        
        if (!$user) {
            return false;
        }
        
        // super_admin لديه جميع الصلاحيات
        if ($user['role_name'] === 'super_admin') {
            return true;
        }
        
        $db = Database::getInstance();
        $result = $db->fetch(
            "SELECT COUNT(*) as count FROM role_permissions rp
             JOIN permissions p ON rp.permission_id = p.id
             WHERE rp.role_id = ? AND p.slug = ?",
            [$user['role_id'], $permission]
        );
        
        return ($result['count'] ?? 0) > 0;
    }
    
    /**
     * التحقق من دور معين
     */
    public static function hasRole(string $role): bool {
        $user = self::user();
        return $user && $user['role_name'] === $role;
    }
    
    /**
     * تشفير كلمة المرور
     */
    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    }
    
    /**
     * تسجيل في سجل المراجعة
     */
    public static function logAudit(
        ?int $userId, 
        string $action, 
        ?string $table = null, 
        ?int $recordId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $companyId = null
    ): void {
        try {
            $db = Database::getInstance();
            $db->insert('audit_logs', [
                'company_id' => $companyId,
                'user_id' => $userId,
                'action' => $action,
                'table_name' => $table,
                'record_id' => $recordId,
                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            // تجاهل أخطاء التسجيل
        }
    }
}
