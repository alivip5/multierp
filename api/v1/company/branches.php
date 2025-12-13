<?php
/**
 * Branches API - Company Branches Endpoint
 * نقاط API للفروع
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../includes/Database.php';
require_once __DIR__ . '/../../../includes/Middleware.php';

Middleware::cors();
$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getInstance();

Middleware::auth();
$companyId = $_SESSION['company_id'] ?? null;
if (!$companyId) Middleware::sendError('شركة غير محددة', 400);

switch ($method) {
    case 'GET':
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $branch = $db->fetch(
                "SELECT * FROM branches WHERE id = ? AND company_id = ?",
                [(int)$id, $companyId]
            );
            if ($branch) {
                Middleware::sendSuccess($branch);
            } else {
                Middleware::sendError('الفرع غير موجود', 404);
            }
        } else {
            $branches = $db->fetchAll(
                "SELECT b.*, 
                 (SELECT COUNT(*) FROM warehouses w WHERE w.branch_id = b.id) as warehouses_count,
                 (SELECT COUNT(*) FROM employees e WHERE e.branch_id = b.id) as employees_count
                 FROM branches b WHERE b.company_id = ? ORDER BY b.is_main DESC, b.name ASC",
                [$companyId]
            );
            Middleware::sendSuccess($branches);
        }
        break;
        
    case 'POST':
        Middleware::permission('settings.create');
        $input = Middleware::getJsonInput();
        
        if (empty($input['name'])) {
            Middleware::sendError('اسم الفرع مطلوب', 400);
        }
        
        // Generate branch code
        $count = $db->fetch("SELECT COUNT(*) as c FROM branches WHERE company_id = ?", [$companyId])['c'];
        $code = 'BR-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        
        try {
            $id = $db->insert('branches', [
                'company_id' => $companyId,
                'code' => $code,
                'name' => Middleware::sanitize($input['name']),
                'name_en' => $input['name_en'] ?? null,
                'address' => $input['address'] ?? null,
                'city' => $input['city'] ?? null,
                'phone' => $input['phone'] ?? null,
                'email' => $input['email'] ?? null,
                'manager_id' => $input['manager_id'] ?? null,
                'is_main' => $input['is_main'] ?? 0,
                'is_active' => 1,
                'created_by' => $_SESSION['user_id']
            ]);
            
            // If this is main branch, unset others
            if (!empty($input['is_main'])) {
                $db->update('branches', ['is_main' => 0], 'company_id = ? AND id != ?', [$companyId, $id]);
            }
            
            Middleware::sendSuccess($db->fetch("SELECT * FROM branches WHERE id = ?", [$id]), 'تم إضافة الفرع', 201);
        } catch (Exception $e) {
            Middleware::sendError('خطأ في إضافة الفرع: ' . $e->getMessage(), 500);
        }
        break;
        
    case 'PUT':
        Middleware::permission('settings.edit');
        $id = $_GET['id'] ?? null;
        if (!$id) Middleware::sendError('معرف الفرع مطلوب', 400);
        
        $existing = $db->fetch("SELECT * FROM branches WHERE id = ? AND company_id = ?", [(int)$id, $companyId]);
        if (!$existing) Middleware::sendError('الفرع غير موجود', 404);
        
        $input = Middleware::getJsonInput();
        $updateData = [];
        
        $allowedFields = ['name', 'name_en', 'address', 'city', 'phone', 'email', 'manager_id', 'is_main', 'is_active'];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = $input[$field];
            }
        }
        
        if (!empty($updateData)) {
            $db->update('branches', $updateData, 'id = ?', [(int)$id]);
            
            // If setting as main, unset others
            if (!empty($input['is_main'])) {
                $db->update('branches', ['is_main' => 0], 'company_id = ? AND id != ?', [$companyId, (int)$id]);
            }
        }
        
        Middleware::sendSuccess($db->fetch("SELECT * FROM branches WHERE id = ?", [(int)$id]), 'تم تحديث الفرع');
        break;
        
    case 'DELETE':
        Middleware::permission('settings.delete');
        $id = $_GET['id'] ?? null;
        if (!$id) Middleware::sendError('معرف الفرع مطلوب', 400);
        
        $existing = $db->fetch("SELECT * FROM branches WHERE id = ? AND company_id = ?", [(int)$id, $companyId]);
        if (!$existing) Middleware::sendError('الفرع غير موجود', 404);
        
        if ($existing['is_main']) {
            Middleware::sendError('لا يمكن حذف الفرع الرئيسي', 400);
        }
        
        // Check for related data
        $warehouseCount = $db->fetch("SELECT COUNT(*) as c FROM warehouses WHERE branch_id = ?", [(int)$id])['c'];
        if ($warehouseCount > 0) {
            Middleware::sendError('لا يمكن حذف الفرع لأنه يحتوي على مخازن', 400);
        }
        
        $db->delete('branches', 'id = ?', [(int)$id]);
        Middleware::sendSuccess(null, 'تم حذف الفرع');
        break;
        
    default:
        Middleware::sendError('طريقة غير مدعومة', 405);
}
