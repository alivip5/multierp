<?php
/**
 * Production API - Orders Endpoint
 * نقاط API لأوامر الإنتاج
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

if (!Middleware::moduleEnabled('production')) {
    Middleware::sendError('موديول الإنتاج غير مفعل', 403);
}

switch ($method) {
    case 'GET':
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $order = $db->fetch(
                "SELECT po.*, p.name as product_name, u.full_name as created_by_name
                 FROM production_orders po
                 LEFT JOIN products p ON po.product_id = p.id
                 LEFT JOIN users u ON po.created_by = u.id
                 WHERE po.id = ? AND po.company_id = ?",
                [(int)$id, $companyId]
            );
            
            if ($order) {
                // Get materials
                $order['materials'] = $db->fetchAll(
                    "SELECT pom.*, p.name as material_name, p.code as material_code
                     FROM production_order_materials pom
                     LEFT JOIN products p ON pom.material_id = p.id
                     WHERE pom.order_id = ?",
                    [(int)$id]
                );
                Middleware::sendSuccess($order);
            } else {
                Middleware::sendError('أمر الإنتاج غير موجود', 404);
            }
        } else {
            $status = $_GET['status'] ?? null;
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = 25;
            $offset = ($page - 1) * $limit;
            
            $where = "po.company_id = ?";
            $params = [$companyId];
            
            if ($status) {
                $where .= " AND po.status = ?";
                $params[] = $status;
            }
            
            $total = $db->fetch("SELECT COUNT(*) as c FROM production_orders po WHERE $where", $params)['c'];
            
            $orders = $db->fetchAll(
                "SELECT po.*, p.name as product_name
                 FROM production_orders po
                 LEFT JOIN products p ON po.product_id = p.id
                 WHERE $where
                 ORDER BY po.created_at DESC
                 LIMIT $limit OFFSET $offset",
                $params
            );
            
            Middleware::sendSuccess([
                'data' => $orders,
                'pagination' => ['page' => $page, 'limit' => $limit, 'total' => (int)$total]
            ]);
        }
        break;
        
    case 'POST':
        Middleware::permission('production.create');
        $input = Middleware::getJsonInput();
        
        if (empty($input['product_id']) || empty($input['quantity'])) {
            Middleware::sendError('المنتج والكمية مطلوبان', 400);
        }
        
        try {
            $orderNumber = 'PRO-' . date('Ymd') . '-' . rand(1000, 9999);
            
            $orderId = $db->insert('production_orders', [
                'company_id' => $companyId,
                'order_number' => $orderNumber,
                'product_id' => (int)$input['product_id'],
                'quantity' => (float)$input['quantity'],
                'planned_start' => $input['planned_start'] ?? null,
                'planned_end' => $input['planned_end'] ?? null,
                'status' => 'draft',
                'notes' => $input['notes'] ?? null,
                'created_by' => $_SESSION['user_id']
            ]);
            
            // Add materials if provided
            if (!empty($input['materials'])) {
                foreach ($input['materials'] as $mat) {
                    $db->insert('production_order_materials', [
                        'order_id' => $orderId,
                        'material_id' => (int)$mat['material_id'],
                        'required_quantity' => (float)$mat['quantity'],
                        'unit_cost' => $mat['unit_cost'] ?? 0
                    ]);
                }
            }
            
            Middleware::sendSuccess(['id' => $orderId, 'order_number' => $orderNumber], 'تم إنشاء أمر الإنتاج', 201);
        } catch (Exception $e) {
            Middleware::sendError('خطأ في إنشاء أمر الإنتاج: ' . $e->getMessage(), 500);
        }
        break;
        
    case 'PUT':
        Middleware::permission('production.edit');
        $id = $_GET['id'] ?? null;
        if (!$id) Middleware::sendError('معرف الأمر مطلوب', 400);
        
        $existing = $db->fetch("SELECT * FROM production_orders WHERE id = ? AND company_id = ?", [(int)$id, $companyId]);
        if (!$existing) Middleware::sendError('أمر الإنتاج غير موجود', 404);
        
        $input = Middleware::getJsonInput();
        $updateData = [];
        
        $allowedFields = ['quantity', 'planned_start', 'planned_end', 'actual_start', 'actual_end', 'status', 'notes'];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) $updateData[$field] = $input[$field];
        }
        
        if (!empty($updateData)) {
            $db->update('production_orders', $updateData, 'id = ?', [(int)$id]);
        }
        
        Middleware::sendSuccess($db->fetch("SELECT * FROM production_orders WHERE id = ?", [(int)$id]), 'تم تحديث أمر الإنتاج');
        break;
        
    case 'DELETE':
        Middleware::permission('production.delete');
        $id = $_GET['id'] ?? null;
        if (!$id) Middleware::sendError('معرف الأمر مطلوب', 400);
        
        $existing = $db->fetch("SELECT * FROM production_orders WHERE id = ? AND company_id = ?", [(int)$id, $companyId]);
        if (!$existing) Middleware::sendError('أمر الإنتاج غير موجود', 404);
        
        if ($existing['status'] !== 'draft') {
            Middleware::sendError('لا يمكن حذف أوامر الإنتاج التي بدأت', 400);
        }
        
        $db->delete('production_order_materials', 'order_id = ?', [(int)$id]);
        $db->delete('production_orders', 'id = ?', [(int)$id]);
        Middleware::sendSuccess(null, 'تم حذف أمر الإنتاج');
        break;
        
    default:
        Middleware::sendError('طريقة غير مدعومة', 405);
}
