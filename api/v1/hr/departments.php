<?php
/**
 * HR API - Departments Endpoint
 * نقاط API للأقسام
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
            $dept = $db->fetch("SELECT * FROM departments WHERE id = ? AND company_id = ?", [(int)$id, $companyId]);
            if ($dept) {
                Middleware::sendSuccess($dept);
            } else {
                Middleware::sendError('القسم غير موجود', 404);
            }
        } else {
            $departments = $db->fetchAll(
                "SELECT d.*, (SELECT COUNT(*) FROM employees e WHERE e.department_id = d.id) as employees_count 
                 FROM departments d WHERE d.company_id = ? ORDER BY d.name", 
                [$companyId]
            );
            Middleware::sendSuccess($departments);
        }
        break;
        
    case 'POST':
        Middleware::permission('hr.create');
        $input = Middleware::getJsonInput();
        
        if (empty($input['name'])) {
            Middleware::sendError('اسم القسم مطلوب', 400);
        }
        
        try {
            $id = $db->insert('departments', [
                'company_id' => $companyId,
                'name' => Middleware::sanitize($input['name']),
                'description' => $input['description'] ?? null,
                'manager_id' => $input['manager_id'] ?? null
            ]);
            $dept = $db->fetch("SELECT * FROM departments WHERE id = ?", [$id]);
            Middleware::sendSuccess($dept, 'تم إضافة القسم', 201);
        } catch (Exception $e) {
            Middleware::sendError('خطأ في إضافة القسم', 500);
        }
        break;
        
    case 'PUT':
        Middleware::permission('hr.edit');
        $id = $_GET['id'] ?? null;
        if (!$id) Middleware::sendError('معرف القسم مطلوب', 400);
        
        $input = Middleware::getJsonInput();
        $updateData = [];
        if (isset($input['name'])) $updateData['name'] = Middleware::sanitize($input['name']);
        if (isset($input['description'])) $updateData['description'] = $input['description'];
        if (isset($input['manager_id'])) $updateData['manager_id'] = $input['manager_id'];
        
        if (empty($updateData)) Middleware::sendError('لا توجد بيانات للتحديث', 400);
        
        $db->update('departments', $updateData, 'id = ? AND company_id = ?', [(int)$id, $companyId]);
        Middleware::sendSuccess($db->fetch("SELECT * FROM departments WHERE id = ?", [(int)$id]), 'تم تحديث القسم');
        break;
        
    case 'DELETE':
        Middleware::permission('hr.delete');
        $id = $_GET['id'] ?? null;
        if (!$id) Middleware::sendError('معرف القسم مطلوب', 400);
        
        // Check if department has employees
        $empCount = $db->fetch("SELECT COUNT(*) as c FROM employees WHERE department_id = ?", [(int)$id])['c'];
        if ($empCount > 0) {
            Middleware::sendError('لا يمكن حذف القسم لأنه يحتوي على موظفين', 400);
        }
        
        $db->delete('departments', 'id = ? AND company_id = ?', [(int)$id, $companyId]);
        Middleware::sendSuccess(null, 'تم حذف القسم');
        break;
        
    default:
        Middleware::sendError('طريقة غير مدعومة', 405);
}
