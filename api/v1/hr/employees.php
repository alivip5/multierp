<?php
/**
 * HR API - Employees Endpoint
 * نقاط API للموظفين
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../includes/Database.php';
require_once __DIR__ . '/../../../includes/Middleware.php';

// CORS Headers
Middleware::cors();

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getInstance();

// التحقق من المصادقة
Middleware::auth();
$companyId = $_SESSION['company_id'] ?? null;

if (!$companyId) {
    Middleware::sendError('شركة غير محددة', 400);
}

// التحقق من تفعيل الموديول
if (!Middleware::moduleEnabled('hr')) {
    Middleware::sendError('موديول الموارد البشرية غير مفعل', 403);
}

switch ($method) {
    case 'GET':
        // جلب الموظفين
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            // موظف واحد
            $employee = $db->fetch(
                "SELECT e.*, d.name as department_name, p.name as position_name 
                 FROM employees e
                 LEFT JOIN departments d ON e.department_id = d.id
                 LEFT JOIN positions p ON e.position_id = p.id
                 WHERE e.id = ? AND e.company_id = ?",
                [(int)$id, $companyId]
            );
            
            if ($employee) {
                Middleware::sendSuccess($employee);
            } else {
                Middleware::sendError('الموظف غير موجود', 404);
            }
        } else {
            // قائمة الموظفين
            $status = $_GET['status'] ?? null;
            $department = $_GET['department'] ?? null;
            $search = $_GET['search'] ?? null;
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(100, max(10, (int)($_GET['limit'] ?? 25)));
            $offset = ($page - 1) * $limit;
            
            $where = "e.company_id = ?";
            $params = [$companyId];
            
            if ($status) {
                $where .= " AND e.status = ?";
                $params[] = $status;
            }
            if ($department) {
                $where .= " AND e.department_id = ?";
                $params[] = (int)$department;
            }
            if ($search) {
                $where .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_number LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $total = $db->fetch("SELECT COUNT(*) as count FROM employees e WHERE $where", $params)['count'];
            
            $employees = $db->fetchAll(
                "SELECT e.*, d.name as department_name, p.name as position_name 
                 FROM employees e
                 LEFT JOIN departments d ON e.department_id = d.id
                 LEFT JOIN positions p ON e.position_id = p.id
                 WHERE $where
                 ORDER BY e.first_name ASC
                 LIMIT $limit OFFSET $offset",
                $params
            );
            
            Middleware::sendSuccess([
                'data' => $employees,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        }
        break;
        
    case 'POST':
        // إضافة موظف جديد
        Middleware::permission('hr.create');
        $input = Middleware::getJsonInput();
        
        $required = ['first_name', 'last_name'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                Middleware::sendError("الحقل $field مطلوب", 400);
            }
        }
        
        $data = [
            'company_id' => $companyId,
            'first_name' => Middleware::sanitize($input['first_name']),
            'last_name' => Middleware::sanitize($input['last_name']),
            'employee_number' => $input['employee_number'] ?? null,
            'email' => $input['email'] ?? null,
            'phone' => $input['phone'] ?? null,
            'mobile' => $input['mobile'] ?? null,
            'national_id' => $input['national_id'] ?? null,
            'date_of_birth' => $input['date_of_birth'] ?? null,
            'gender' => $input['gender'] ?? null,
            'nationality' => $input['nationality'] ?? null,
            'address' => $input['address'] ?? null,
            'department_id' => $input['department_id'] ?? null,
            'position_id' => $input['position_id'] ?? null,
            'hire_date' => $input['hire_date'] ?? null,
            'contract_type' => $input['contract_type'] ?? 'permanent',
            'salary' => $input['salary'] ?? 0,
            'status' => $input['status'] ?? 'active',
            'created_by' => $_SESSION['user_id']
        ];
        
        try {
            $id = $db->insert('employees', $data);
            $employee = $db->fetch("SELECT * FROM employees WHERE id = ?", [$id]);
            Middleware::sendSuccess($employee, 'تم إضافة الموظف بنجاح', 201);
        } catch (Exception $e) {
            Middleware::sendError('خطأ في إضافة الموظف: ' . $e->getMessage(), 500);
        }
        break;
        
    case 'PUT':
        // تعديل موظف
        Middleware::permission('hr.edit');
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            Middleware::sendError('معرف الموظف مطلوب', 400);
        }
        
        $existing = $db->fetch("SELECT * FROM employees WHERE id = ? AND company_id = ?", [(int)$id, $companyId]);
        if (!$existing) {
            Middleware::sendError('الموظف غير موجود', 404);
        }
        
        $input = Middleware::getJsonInput();
        $updateData = [];
        
        $allowedFields = ['first_name', 'last_name', 'employee_number', 'email', 'phone', 'mobile',
            'national_id', 'date_of_birth', 'gender', 'nationality', 'address',
            'department_id', 'position_id', 'hire_date', 'contract_type', 'salary', 'status'];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = $input[$field];
            }
        }
        
        if (empty($updateData)) {
            Middleware::sendError('لا توجد بيانات للتحديث', 400);
        }
        
        try {
            $db->update('employees', $updateData, 'id = ? AND company_id = ?', [(int)$id, $companyId]);
            $employee = $db->fetch("SELECT * FROM employees WHERE id = ?", [(int)$id]);
            Middleware::sendSuccess($employee, 'تم تحديث بيانات الموظف');
        } catch (Exception $e) {
            Middleware::sendError('خطأ في تحديث الموظف', 500);
        }
        break;
        
    case 'DELETE':
        // حذف موظف
        Middleware::permission('hr.delete');
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            Middleware::sendError('معرف الموظف مطلوب', 400);
        }
        
        $existing = $db->fetch("SELECT * FROM employees WHERE id = ? AND company_id = ?", [(int)$id, $companyId]);
        if (!$existing) {
            Middleware::sendError('الموظف غير موجود', 404);
        }
        
        try {
            $db->delete('employees', 'id = ? AND company_id = ?', [(int)$id, $companyId]);
            Middleware::sendSuccess(null, 'تم حذف الموظف بنجاح');
        } catch (Exception $e) {
            Middleware::sendError('خطأ في حذف الموظف', 500);
        }
        break;
        
    default:
        Middleware::sendError('طريقة غير مدعومة', 405);
}
