<?php
/**
 * HR API - Positions Endpoint
 * نقاط API للمناصب الوظيفية
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../includes/Database.php';
require_once __DIR__ . '/../../../includes/Middleware.php';

Middleware::cors();

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getInstance();

Middleware::auth();
$companyId = $_SESSION['company_id'] ?? null;

if (!$companyId) {
    Middleware::sendError('شركة غير محددة', 400);
}

switch ($method) {
    case 'GET':
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $pos = $db->fetch("SELECT * FROM positions WHERE id = ? AND company_id = ?", [(int)$id, $companyId]);
            if ($pos) {
                Middleware::sendSuccess($pos);
            } else {
                Middleware::sendError('المنصب غير موجود', 404);
            }
        } else {
            $positions = $db->fetchAll(
                "SELECT p.*, (SELECT COUNT(*) FROM employees e WHERE e.position_id = p.id) as employees_count 
                 FROM positions p WHERE p.company_id = ? ORDER BY p.name", 
                [$companyId]
            );
            Middleware::sendSuccess($positions);
        }
        break;
        
    case 'POST':
        Middleware::permission('hr.create');
        $input = Middleware::getJsonInput();
        
        if (empty($input['name'])) {
            Middleware::sendError('اسم المنصب مطلوب', 400);
        }
        
        try {
            $id = $db->insert('positions', [
                'company_id' => $companyId,
                'name' => Middleware::sanitize($input['name']),
                'description' => $input['description'] ?? null,
                'min_salary' => $input['min_salary'] ?? null,
                'max_salary' => $input['max_salary'] ?? null
            ]);
            $pos = $db->fetch("SELECT * FROM positions WHERE id = ?", [$id]);
            Middleware::sendSuccess($pos, 'تم إضافة المنصب', 201);
        } catch (Exception $e) {
            Middleware::sendError('خطأ في إضافة المنصب', 500);
        }
        break;
        
    case 'PUT':
        Middleware::permission('hr.edit');
        $id = $_GET['id'] ?? null;
        if (!$id) Middleware::sendError('معرف المنصب مطلوب', 400);
        
        $input = Middleware::getJsonInput();
        $updateData = [];
        if (isset($input['name'])) $updateData['name'] = Middleware::sanitize($input['name']);
        if (isset($input['description'])) $updateData['description'] = $input['description'];
        if (isset($input['min_salary'])) $updateData['min_salary'] = $input['min_salary'];
        if (isset($input['max_salary'])) $updateData['max_salary'] = $input['max_salary'];
        
        if (empty($updateData)) Middleware::sendError('لا توجد بيانات للتحديث', 400);
        
        $db->update('positions', $updateData, 'id = ? AND company_id = ?', [(int)$id, $companyId]);
        Middleware::sendSuccess($db->fetch("SELECT * FROM positions WHERE id = ?", [(int)$id]), 'تم تحديث المنصب');
        break;
        
    case 'DELETE':
        Middleware::permission('hr.delete');
        $id = $_GET['id'] ?? null;
        if (!$id) Middleware::sendError('معرف المنصب مطلوب', 400);
        
        $empCount = $db->fetch("SELECT COUNT(*) as c FROM employees WHERE position_id = ?", [(int)$id])['c'];
        if ($empCount > 0) {
            Middleware::sendError('لا يمكن حذف المنصب لأنه مرتبط بموظفين', 400);
        }
        
        $db->delete('positions', 'id = ? AND company_id = ?', [(int)$id, $companyId]);
        Middleware::sendSuccess(null, 'تم حذف المنصب');
        break;
        
    default:
        Middleware::sendError('طريقة غير مدعومة', 405);
}
