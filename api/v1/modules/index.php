<?php
/**
 * API الموديولات
 * Modules API Endpoint
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
$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // قائمة الموديولات للشركة الحالية
        $modules = $db->fetchAll(
            "SELECT m.*, COALESCE(cm.status, 'disabled') as status
             FROM modules m
             LEFT JOIN company_modules cm ON m.id = cm.module_id AND cm.company_id = ?
             ORDER BY m.sort_order",
            [$user['company_id']]
        );
        
        Middleware::sendSuccess($modules);
        break;
        
    case 'PUT':
        // تفعيل/تعطيل موديول (يتطلب صلاحية super_admin أو manager)
        if (!Auth::hasRole('super_admin') && !Auth::hasRole('manager')) {
            Middleware::sendError('غير مصرح', 403);
        }
        
        $data = Middleware::getJsonInput();
        
        if (empty($data['module_id']) || !isset($data['status'])) {
            Middleware::sendError('بيانات غير صالحة', 422);
        }
        
        // التحقق أن الموديول ليس من موديولات النظام
        $module = $db->fetch("SELECT * FROM modules WHERE id = ?", [$data['module_id']]);
        
        if (!$module) {
            Middleware::sendError('الموديول غير موجود', 404);
        }
        
        if ($module['is_system']) {
            Middleware::sendError('لا يمكن تعطيل موديولات النظام', 400);
        }
        
        $status = $data['status'] ? 'enabled' : 'disabled';
        
        // تحديث أو إدراج
        $existing = $db->fetch(
            "SELECT * FROM company_modules WHERE company_id = ? AND module_id = ?",
            [$user['company_id'], $data['module_id']]
        );
        
        if ($existing) {
            $db->update('company_modules', 
                ['status' => $status], 
                'company_id = ? AND module_id = ?', 
                [$user['company_id'], $data['module_id']]
            );
        } else {
            $db->insert('company_modules', [
                'company_id' => $user['company_id'],
                'module_id' => $data['module_id'],
                'status' => $status
            ]);
        }
        
        Auth::logAudit(
            $user['id'], 
            $status === 'enabled' ? 'module_enabled' : 'module_disabled',
            'company_modules',
            $data['module_id'],
            null,
            ['status' => $status],
            $user['company_id']
        );
        
        Middleware::sendSuccess(null, 'تم تحديث حالة الموديول بنجاح');
        break;
        
    default:
        Middleware::sendError('طريقة الطلب غير مسموحة', 405);
}
