<?php
/**
 * API المستخدم الحالي
 * Current User API Endpoint
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../includes/Database.php';
require_once __DIR__ . '/../../../includes/JWT.php';
require_once __DIR__ . '/../../../includes/Auth.php';
require_once __DIR__ . '/../../../includes/Middleware.php';

Middleware::cors();

if (!Middleware::auth()) {
    exit;
}

$user = Auth::user();
$company = Auth::company();

// الحصول على الموديولات المفعلة
$db = Database::getInstance();
$modules = $db->fetchAll(
    "SELECT m.id, m.name, m.name_ar, m.slug, m.icon, cm.status
     FROM modules m
     LEFT JOIN company_modules cm ON m.id = cm.module_id AND cm.company_id = ?
     WHERE cm.status = 'enabled' OR m.is_system = 1
     ORDER BY m.sort_order",
    [$user['company_id']]
);

// الحصول على صلاحيات المستخدم
$permissions = [];
if ($user['role_name'] !== 'super_admin') {
    $perms = $db->fetchAll(
        "SELECT p.slug FROM role_permissions rp
         JOIN permissions p ON rp.permission_id = p.id
         WHERE rp.role_id = ?",
        [$user['role_id']]
    );
    $permissions = array_column($perms, 'slug');
} else {
    $permissions = ['*']; // جميع الصلاحيات
}

Middleware::sendSuccess([
    'user' => $user,
    'company' => $company,
    'modules' => $modules,
    'permissions' => $permissions
]);
