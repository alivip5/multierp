<?php
/**
 * Notifications API Endpoint
 * نقاط API للإشعارات
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../includes/Database.php';
require_once __DIR__ . '/../../../includes/Middleware.php';
require_once __DIR__ . '/../../../includes/NotificationHelper.php';

Middleware::cors();
$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getInstance();

Middleware::auth();
$userId = $_SESSION['user_id'] ?? null;
$companyId = $_SESSION['company_id'] ?? null;

if (!$userId || !$companyId) {
    Middleware::sendError('غير مصرح', 401);
}

switch ($method) {
    case 'GET':
        $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === '1';
        $limit = min(50, max(10, (int)($_GET['limit'] ?? 20)));
        
        $notifications = NotificationHelper::getUserNotifications($userId, $companyId, $limit, $unreadOnly);
        $unreadCount = NotificationHelper::getUnreadCount($userId, $companyId);
        
        Middleware::sendSuccess([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
        break;
        
    case 'POST':
        $input = Middleware::getJsonInput();
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'mark_read':
                $id = (int)($input['id'] ?? 0);
                if ($id) {
                    NotificationHelper::markAsRead($id, $userId);
                    Middleware::sendSuccess(null, 'تم تعليم الإشعار كمقروء');
                } else {
                    Middleware::sendError('معرف الإشعار مطلوب', 400);
                }
                break;
                
            case 'mark_all_read':
                NotificationHelper::markAllAsRead($userId, $companyId);
                Middleware::sendSuccess(null, 'تم تعليم جميع الإشعارات كمقروءة');
                break;
                
            default:
                Middleware::sendError('إجراء غير صالح', 400);
        }
        break;
        
    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if ($id) {
            NotificationHelper::delete((int)$id, $userId);
            Middleware::sendSuccess(null, 'تم حذف الإشعار');
        } else {
            Middleware::sendError('معرف الإشعار مطلوب', 400);
        }
        break;
        
    default:
        Middleware::sendError('طريقة غير مدعومة', 405);
}
