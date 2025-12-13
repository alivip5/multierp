<?php
/**
 * ملف المساعدة للقائمة الجانبية مع دعم الصلاحيات
 * Sidebar Helper with Permissions Support
 */

require_once __DIR__ . '/../api/config/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Security.php';

/**
 * جلب عناصر القائمة المتاحة للمستخدم حسب صلاحياته
 * Get sidebar items available for the user based on permissions
 */
function getSidebarItems($companyId, $userId): array {
    $db = Database::getInstance();
    
    // جلب بيانات المستخدم والدور
    $user = $db->fetch(
        "SELECT u.role_id, r.name as role_slug FROM users u 
         JOIN roles r ON u.role_id = r.id 
         WHERE u.id = ?",
        [$userId]
    );
    
    if (!$user) return [];
    
    $isSuperAdmin = ($user['role_slug'] === 'super_admin');
    
    // جلب الموديولات المفعلة للشركة
    $allModules = $db->fetchAll(
        "SELECT m.* FROM modules m 
         LEFT JOIN company_modules cm ON m.id = cm.module_id AND cm.company_id = ?
         WHERE cm.status = 'enabled' OR m.is_system = 1
         ORDER BY m.sort_order",
        [$companyId]
    );
    
    // super_admin يرى كل شيء
    if ($isSuperAdmin) {
        return $allModules;
    }
    
    // جلب صلاحيات المستخدم
    $userPermissions = $db->fetchAll(
        "SELECT p.slug FROM role_permissions rp
         JOIN permissions p ON rp.permission_id = p.id
         WHERE rp.role_id = ?",
        [$user['role_id']]
    );
    
    $permissionSlugs = array_column($userPermissions, 'slug');
    
    // فلترة الموديولات حسب الصلاحيات
    $filteredModules = [];
    
    foreach ($allModules as $module) {
        $requiredPermission = $module['required_permission'] ?? null;
        
        // إذا كان الموديول لا يحتاج صلاحية أو الصلاحية موجودة
        if (empty($requiredPermission) || in_array($requiredPermission, $permissionSlugs)) {
            $filteredModules[] = $module;
        }
    }
    
    return $filteredModules;
}

/**
 * التحقق من صلاحية الوصول لموديول معين
 * Check if user can access a specific module
 */
function canAccessModule(string $moduleSlug): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) return false;
    
    // التحقق من role_name من الجلسة
    $roleName = $_SESSION['role_name'] ?? '';
    
    // super_admin يمكنه الوصول لكل شيء
    if ($roleName === 'super_admin') {
        return true;
    }
    
    // الجميع يمكنهم الوصول للرئيسية
    if ($moduleSlug === 'dashboard') {
        return true;
    }
    
    // جلب الصلاحية المطلوبة من قاعدة البيانات
    require_once __DIR__ . '/Database.php';
    $db = Database::getInstance();
    
    // استخدام ذاكرة تخزين مؤقت بسيطة لتجنب تكرار الاستعلام في نفس الطلب
    static $modulePermsCache = [];
    
    if (isset($modulePermsCache[$moduleSlug])) {
        $requiredPermission = $modulePermsCache[$moduleSlug];
    } else {
        $module = $db->fetch("SELECT required_permission FROM modules WHERE slug = ?", [$moduleSlug]);
        $requiredPermission = $module['required_permission'] ?? null;
        $modulePermsCache[$moduleSlug] = $requiredPermission;
    }
    
    // إذا لم يكن هناك صلاحية مطلوبة، فالوصول متاح
    if (empty($requiredPermission)) {
        return true;
    }
    
    // التحقق من الصلاحية
    return can($requiredPermission);
}

/**
 * التحقق من الوصول للموديول مع إعادة التوجيه
 * Require module access or redirect
 */
function requireModuleAccess(string $moduleSlug, string $redirectUrl = '../../pages/dashboard.php'): void {
    if (!canAccessModule($moduleSlug)) {
        header('Location: ' . $redirectUrl . '?error=permission_denied');
        exit;
    }
}

/**
 * إخفاء/إظهار عنصر بناءً على الصلاحية
 * Returns display style based on permission
 */
function showIfCan(string $permission): string {
    return can($permission) ? '' : 'display: none;';
}

/**
 * عرض رابط إذا كان المستخدم يملك الصلاحية
 * Display link only if user has permission
 */
function linkIfCan(string $permission, string $url, string $text, string $class = 'btn btn-primary'): string {
    if (!can($permission)) {
        return '';
    }
    return "<a href=\"{$url}\" class=\"{$class}\">{$text}</a>";
}
