<?php
/**
 * API العملاء
 * Customers API Endpoint
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../includes/Database.php';
require_once __DIR__ . '/../../../includes/JWT.php';
require_once __DIR__ . '/../../../includes/Auth.php';
require_once __DIR__ . '/../../../includes/Middleware.php';

Middleware::cors();

if (!Middleware::moduleEnabled('sales')) {
    exit;
}

$user = Auth::user();
$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$customerId = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        if ($customerId) {
            $customer = $db->fetch(
                "SELECT * FROM customers WHERE id = ? AND company_id = ?",
                [$customerId, $user['company_id']]
            );
            
            if (!$customer) {
                Middleware::sendError('العميل غير موجود', 404);
            }
            
            Middleware::sendSuccess($customer);
        } else {
            $query = Middleware::getQuery();
            $page = max(1, (int)($query['page'] ?? 1));
            $limit = min(100, (int)($query['limit'] ?? ITEMS_PER_PAGE));
            $offset = ($page - 1) * $limit;
            
            $where = "company_id = ?";
            $params = [$user['company_id']];
            
            if (!empty($query['search'])) {
                $where .= " AND (name LIKE ? OR phone LIKE ? OR code LIKE ?)";
                $search = "%{$query['search']}%";
                $params = array_merge($params, [$search, $search, $search]);
            }
            
            if (isset($query['status'])) {
                $where .= " AND status = ?";
                $params[] = $query['status'];
            }
            
            $total = $db->fetch("SELECT COUNT(*) as count FROM customers WHERE $where", $params)['count'];
            $customers = $db->fetchAll(
                "SELECT * FROM customers WHERE $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset",
                $params
            );
            
            Middleware::sendSuccess([
                'items' => $customers,
                'pagination' => [
                    'total' => (int)$total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        }
        break;
        
    case 'POST':
        if (!Auth::can('customers.manage') && !Auth::can('sales.create')) {
            Middleware::sendError('غير مصرح', 403);
        }
        
        $data = Middleware::getJsonInput();
        
        $errors = Middleware::validate($data, [
            'name' => 'required|max:255'
        ]);
        
        if (!empty($errors)) {
            Middleware::sendJson(['success' => false, 'errors' => $errors], 422);
        }
        
        $id = $db->insert('customers', [
            'company_id' => $user['company_id'],
            'code' => $data['code'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'tax_number' => $data['tax_number'] ?? null,
            'credit_limit' => $data['credit_limit'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'created_by' => $user['id']
        ]);
        
        Auth::logAudit($user['id'], 'create', 'customers', $id, null, $data, $user['company_id']);
        
        Middleware::sendSuccess(['id' => $id], 'تم إضافة العميل بنجاح');
        break;
        
    case 'PUT':
        if (!$customerId) {
            Middleware::sendError('معرف العميل مطلوب', 400);
        }
        
        if (!Auth::can('customers.manage')) {
            Middleware::sendError('غير مصرح', 403);
        }
        
        $existing = $db->fetch("SELECT * FROM customers WHERE id = ? AND company_id = ?", [$customerId, $user['company_id']]);
        
        if (!$existing) {
            Middleware::sendError('العميل غير موجود', 404);
        }
        
        $data = Middleware::getJsonInput();
        
        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'code' => $data['code'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'tax_number' => $data['tax_number'] ?? null,
            'credit_limit' => $data['credit_limit'] ?? null,
            'status' => $data['status'] ?? null,
            'notes' => $data['notes'] ?? null
        ], fn($v) => $v !== null);
        
        if (!empty($updateData)) {
            $db->update('customers', $updateData, 'id = ?', [$customerId]);
            Auth::logAudit($user['id'], 'update', 'customers', $customerId, $existing, $updateData, $user['company_id']);
        }
        
        Middleware::sendSuccess(null, 'تم تحديث العميل بنجاح');
        break;
        
    case 'DELETE':
        if (!$customerId) {
            Middleware::sendError('معرف العميل مطلوب', 400);
        }
        
        if (!Auth::can('customers.manage')) {
            Middleware::sendError('غير مصرح', 403);
        }
        
        $existing = $db->fetch("SELECT * FROM customers WHERE id = ? AND company_id = ?", [$customerId, $user['company_id']]);
        
        if (!$existing) {
            Middleware::sendError('العميل غير موجود', 404);
        }
        
        $db->delete('customers', 'id = ?', [$customerId]);
        Auth::logAudit($user['id'], 'delete', 'customers', $customerId, $existing, null, $user['company_id']);
        
        Middleware::sendSuccess(null, 'تم حذف العميل بنجاح');
        break;
        
    default:
        Middleware::sendError('طريقة الطلب غير مسموحة', 405);
}
