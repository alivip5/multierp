<?php
/**
 * Notifications Helper Functions
 * وظائف الإشعارات المساعدة
 */

require_once __DIR__ . '/Database.php';

class NotificationHelper {
    
    private static $db = null;
    
    private static function getDb() {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
        return self::$db;
    }
    
    /**
     * أنواع الإشعارات
     */
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_ERROR = 'error';
    
    /**
     * إنشاء إشعار جديد
     */
    public static function create(
        int $companyId,
        int $userId,
        string $title,
        string $message,
        string $type = self::TYPE_INFO,
        ?string $link = null,
        ?string $icon = null
    ): int {
        $db = self::getDb();
        
        return $db->insert('notifications', [
            'company_id' => $companyId,
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link,
            'icon' => $icon,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * إرسال إشعار لمجموعة مستخدمين
     */
    public static function broadcast(
        int $companyId,
        array $userIds,
        string $title,
        string $message,
        string $type = self::TYPE_INFO,
        ?string $link = null
    ): void {
        foreach ($userIds as $userId) {
            self::create($companyId, (int)$userId, $title, $message, $type, $link);
        }
    }
    
    /**
     * إرسال إشعار لجميع مستخدمي الشركة
     */
    public static function notifyCompany(
        int $companyId,
        string $title,
        string $message,
        string $type = self::TYPE_INFO,
        ?string $link = null,
        ?array $excludeUsers = []
    ): void {
        $db = self::getDb();
        
        $users = $db->fetchAll(
            "SELECT user_id FROM user_companies WHERE company_id = ?",
            [$companyId]
        );
        
        foreach ($users as $user) {
            if (!in_array($user['user_id'], $excludeUsers)) {
                self::create($companyId, $user['user_id'], $title, $message, $type, $link);
            }
        }
    }
    
    /**
     * إرسال إشعار لمستخدمي دور معين
     */
    public static function notifyRole(
        int $companyId,
        string $roleName,
        string $title,
        string $message,
        string $type = self::TYPE_INFO,
        ?string $link = null
    ): void {
        $db = self::getDb();
        
        $users = $db->fetchAll(
            "SELECT u.id FROM users u 
             JOIN roles r ON u.role_id = r.id
             JOIN user_companies uc ON u.id = uc.user_id
             WHERE uc.company_id = ? AND r.name = ?",
            [$companyId, $roleName]
        );
        
        foreach ($users as $user) {
            self::create($companyId, $user['id'], $title, $message, $type, $link);
        }
    }
    
    /**
     * جلب إشعارات المستخدم
     */
    public static function getUserNotifications(
        int $userId,
        int $companyId,
        int $limit = 20,
        bool $unreadOnly = false
    ): array {
        $db = self::getDb();
        
        $where = "user_id = ? AND company_id = ?";
        $params = [$userId, $companyId];
        
        if ($unreadOnly) {
            $where .= " AND is_read = 0";
        }
        
        return $db->fetchAll(
            "SELECT * FROM notifications WHERE $where ORDER BY created_at DESC LIMIT ?",
            array_merge($params, [$limit])
        );
    }
    
    /**
     * عدد الإشعارات غير المقروءة
     */
    public static function getUnreadCount(int $userId, int $companyId): int {
        $db = self::getDb();
        
        $result = $db->fetch(
            "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND company_id = ? AND is_read = 0",
            [$userId, $companyId]
        );
        
        return (int)($result['count'] ?? 0);
    }
    
    /**
     * تعليم إشعار كمقروء
     */
    public static function markAsRead(int $notificationId, int $userId): void {
        $db = self::getDb();
        $db->update('notifications', ['is_read' => 1], 'id = ? AND user_id = ?', [$notificationId, $userId]);
    }
    
    /**
     * تعليم جميع الإشعارات كمقروءة
     */
    public static function markAllAsRead(int $userId, int $companyId): void {
        $db = self::getDb();
        $db->update('notifications', ['is_read' => 1], 'user_id = ? AND company_id = ?', [$userId, $companyId]);
    }
    
    /**
     * حذف إشعار
     */
    public static function delete(int $notificationId, int $userId): void {
        $db = self::getDb();
        $db->delete('notifications', 'id = ? AND user_id = ?', [$notificationId, $userId]);
    }
    
    // ============ إشعارات محددة مسبقاً ============
    
    /**
     * إشعار مخزون منخفض
     */
    public static function lowStockAlert(int $companyId, string $productName, int $currentStock, int $minStock): void {
        self::notifyRole(
            $companyId,
            'manager',
            'تنبيه مخزون منخفض',
            "المنتج \"{$productName}\" وصل لمستوى منخفض ({$currentStock} من {$minStock})",
            self::TYPE_WARNING,
            '../modules/inventory/low-stock.php'
        );
    }
    
    /**
     * إشعار فاتورة جديدة
     */
    public static function newInvoiceNotification(int $companyId, string $invoiceNumber, float $total, string $type = 'sales'): void {
        $title = $type === 'sales' ? 'فاتورة مبيعات جديدة' : 'فاتورة مشتريات جديدة';
        $link = $type === 'sales' ? '../modules/sales/index.php' : '../modules/purchases/index.php';
        
        self::notifyRole(
            $companyId,
            'accountant',
            $title,
            "فاتورة جديدة رقم {$invoiceNumber} بقيمة " . number_format($total, 2),
            self::TYPE_SUCCESS,
            $link
        );
    }
    
    /**
     * إشعار موظف جديد
     */
    public static function newEmployeeNotification(int $companyId, string $employeeName): void {
        self::notifyRole(
            $companyId,
            'manager',
            'موظف جديد',
            "تم إضافة الموظف: {$employeeName}",
            self::TYPE_INFO,
            '../modules/hr/employees.php'
        );
    }
}
