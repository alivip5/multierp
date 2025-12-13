<?php
/**
 * Helper للجلسة وتسجيل الدخول
 * Session Handler
 */

session_start();

require_once __DIR__ . '/../api/config/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

// التحقق من وجود token في الجلسة
if (isset($_POST['token']) || isset($_GET['token'])) {
    $token = $_POST['token'] ?? $_GET['token'];
    $payload = JWT::verify($token);
    
    if ($payload) {
        $_SESSION['user_id'] = $payload['user_id'];
        $_SESSION['company_id'] = $payload['company_id'];
        $_SESSION['token'] = $token;
    }
}

/**
 * التحقق من تسجيل الدخول
 */
function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        if (isApiRequest()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'غير مصرح']);
            exit;
        }
        header('Location: /multierp/pages/login.php');
        exit;
    }
}

/**
 * الحصول على المستخدم الحالي
 */
function getCurrentUser(): ?array {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $db = Database::getInstance();
    return $db->fetch(
        "SELECT u.*, r.name as role_slug, r.name_ar as role_name 
         FROM users u JOIN roles r ON u.role_id = r.id 
         WHERE u.id = ?",
        [$_SESSION['user_id']]
    );
}

/**
 * الحصول على الشركة الحالية
 */
function getCurrentCompany(): ?array {
    if (!isset($_SESSION['company_id'])) {
        return null;
    }
    
    $db = Database::getInstance();
    return $db->fetch("SELECT * FROM companies WHERE id = ?", [$_SESSION['company_id']]);
}

/**
 * التحقق من هل الطلب API
 */
function isApiRequest(): bool {
    return strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false ||
           (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}

/**
 * التحقق من تفعيل موديول للشركة الحالية
 */
function isModuleEnabled(string $slug): bool {
    if (!isset($_SESSION['company_id'])) {
        return false;
    }
    
    $db = Database::getInstance();
    $result = $db->fetch(
        "SELECT cm.status FROM company_modules cm 
         JOIN modules m ON cm.module_id = m.id 
         WHERE cm.company_id = ? AND m.slug = ?",
        [$_SESSION['company_id'], $slug]
    );
    
    return $result && $result['status'] === 'enabled';
}

/**
 * التحقق من صلاحية معينة
 */
function hasPermission(string $permission): bool {
    $user = getCurrentUser();
    
    if (!$user) {
        return false;
    }
    
    // super_admin لديه كل الصلاحيات
    if ($user['role_slug'] === 'super_admin') {
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
