<?php
/**
 * Security Helper Functions
 * وظائف الأمان المساعدة
 */

require_once __DIR__ . '/Middleware.php';

/**
 * توليد حقل CSRF مخفي للنماذج
 * Generate hidden CSRF input field
 */
function csrf_field(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $token = Middleware::generateCsrfToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
}

/**
 * التحقق من CSRF token في طلبات POST
 * Verify CSRF token for POST requests
 * @return bool
 */
function verify_csrf(): bool {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    $sessionToken = $_SESSION[CSRF_TOKEN_NAME] ?? '';
    
    if (empty($token) || empty($sessionToken)) {
        return false;
    }
    
    return hash_equals($sessionToken, $token);
}

/**
 * التحقق من CSRF مع إعادة التوجيه عند الفشل
 * Verify CSRF and redirect on failure
 */
function require_csrf(string $redirectUrl = ''): void {
    if (!verify_csrf()) {
        if ($redirectUrl) {
            header('Location: ' . $redirectUrl . '?error=csrf_failed');
        } else {
            http_response_code(403);
            die('خطأ في التحقق من الأمان - يرجى إعادة المحاولة');
        }
        exit;
    }
}

/**
 * التحقق من تفعيل موديول للشركة
 * Check if module is enabled for company
 */
function is_module_enabled(int $companyId, string $moduleSlug): bool {
    require_once __DIR__ . '/Database.php';
    $db = Database::getInstance();
    
    $module = $db->fetch(
        "SELECT cm.status, m.is_system 
         FROM modules m 
         LEFT JOIN company_modules cm ON m.id = cm.module_id AND cm.company_id = ?
         WHERE m.slug = ?",
        [$companyId, $moduleSlug]
    );
    
    if (!$module) {
        return false;
    }
    
    // System modules are always enabled
    if ($module['is_system']) {
        return true;
    }
    
    return $module['status'] === 'enabled';
}

/**
 * التحقق من تفعيل الموديول مع إعادة التوجيه
 * Require module to be enabled or redirect
 */
function require_module(int $companyId, string $moduleSlug, string $redirectUrl = '../../pages/dashboard.php'): void {
    if (!is_module_enabled($companyId, $moduleSlug)) {
        header('Location: ' . $redirectUrl . '?error=module_disabled');
        exit;
    }
}

/**
 * تسجيل عملية في سجل المراجعة
 * Log action to audit trail
 */
function log_audit(
    ?int $companyId,
    ?int $userId,
    string $action,
    ?string $table = null,
    ?int $recordId = null,
    ?array $oldValues = null,
    ?array $newValues = null
): void {
    require_once __DIR__ . '/Database.php';
    
    try {
        $db = Database::getInstance();
        $db->insert('audit_logs', [
            'company_id' => $companyId,
            'user_id' => $userId,
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'old_values' => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
            'new_values' => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        // Silently fail - don't break the main operation
        error_log('Audit log error: ' . $e->getMessage());
    }
}

/**
 * التحقق من صلاحية معينة للمستخدم الحالي
 * Check if current user has a specific permission
 */
function can(string $permission): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id'])) {
        return false;
    }
    
    $roleId = $_SESSION['role_id'];
    $roleName = $_SESSION['role_name'] ?? '';
    
    // super_admin has all permissions
    if ($roleName === 'super_admin') {
        return true;
    }
    
    require_once __DIR__ . '/Database.php';
    $db = Database::getInstance();
    
    $result = $db->fetch(
        "SELECT COUNT(*) as count FROM role_permissions rp
         JOIN permissions p ON rp.permission_id = p.id
         WHERE rp.role_id = ? AND p.slug = ?",
        [$roleId, $permission]
    );
    
    return ($result['count'] ?? 0) > 0;
}

/**
 * التحقق من الصلاحية مع إعادة التوجيه عند الفشل
 * Require permission or redirect
 */
function require_permission(string $permission, string $redirectUrl = '../../pages/dashboard.php'): void {
    if (!can($permission)) {
        header('Location: ' . $redirectUrl . '?error=permission_denied');
        exit;
    }
}

/**
 * التحقق من أن المستخدم له دور معين
 * Check if user has a specific role
 */
function has_role(string $role): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return ($_SESSION['role_name'] ?? '') === $role;
}

/**
 * التحقق من أن المستخدم مدير أو سوبر أدمن
 * Check if user is admin or manager
 */
function is_admin(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $role = $_SESSION['role_name'] ?? '';
    return in_array($role, ['super_admin', 'manager']);
}

/**
 * جلب الصلاحيات المتاحة للمستخدم الحالي
 * Get all permissions for current user
 */
function get_user_permissions(): array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['role_id'])) {
        return [];
    }
    
    $roleName = $_SESSION['role_name'] ?? '';
    
    // super_admin has all permissions
    if ($roleName === 'super_admin') {
        require_once __DIR__ . '/Database.php';
        $db = Database::getInstance();
        $permissions = $db->fetchAll("SELECT slug FROM permissions");
        return array_column($permissions, 'slug');
    }
    
    require_once __DIR__ . '/Database.php';
    $db = Database::getInstance();
    
    $permissions = $db->fetchAll(
        "SELECT p.slug FROM role_permissions rp
         JOIN permissions p ON rp.permission_id = p.id
         WHERE rp.role_id = ?",
        [$_SESSION['role_id']]
    );
    
    return array_column($permissions, 'slug');
}

