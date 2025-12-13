<?php
/**
 * Inventory API - Warehouses Endpoint
 * نقاط API للمستودعات
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
            $warehouse = $db->fetch("SELECT * FROM warehouses WHERE id = ? AND company_id = ?", [(int)$id, $companyId]);
            if ($warehouse) {
                // Add stock summary
                $warehouse['stock_summary'] = $db->fetchAll(
                    "SELECT p.name, ps.quantity FROM product_stock ps
                     JOIN products p ON ps.product_id = p.id
                     WHERE ps.warehouse_id = ? ORDER BY p.name",
                    [(int)$id]
                );
                Middleware::sendSuccess($warehouse);
            } else {
                Middleware::sendError('المستودع غير موجود', 404);
            }
        } else {
            $warehouses = $db->fetchAll(
                "SELECT w.*, 
                 (SELECT COUNT(DISTINCT product_id) FROM product_stock WHERE warehouse_id = w.id) as products_count,
                 (SELECT COALESCE(SUM(quantity), 0) FROM product_stock WHERE warehouse_id = w.id) as total_quantity
                 FROM warehouses w WHERE w.company_id = ? ORDER BY w.name",
                [$companyId]
            );
            Middleware::sendSuccess($warehouses);
        }
        break;
        
    case 'POST':
        Middleware::permission('inventory.create');
        $input = Middleware::getJsonInput();
        
        if (empty($input['name'])) Middleware::sendError('اسم المستودع مطلوب', 400);
        
        $id = $db->insert('warehouses', [
            'company_id' => $companyId,
            'name' => Middleware::sanitize($input['name']),
            'location' => $input['location'] ?? null,
            'is_default' => $input['is_default'] ?? 0
        ]);
        
        Middleware::sendSuccess($db->fetch("SELECT * FROM warehouses WHERE id = ?", [$id]), 'تم إضافة المستودع', 201);
        break;
        
    case 'PUT':
        Middleware::permission('inventory.edit');
        $id = $_GET['id'] ?? null;
        if (!$id) Middleware::sendError('معرف المستودع مطلوب', 400);
        
        $input = Middleware::getJsonInput();
        $updateData = [];
        if (isset($input['name'])) $updateData['name'] = Middleware::sanitize($input['name']);
        if (isset($input['location'])) $updateData['location'] = $input['location'];
        if (isset($input['is_default'])) $updateData['is_default'] = (int)$input['is_default'];
        
        if (!empty($updateData)) {
            $db->update('warehouses', $updateData, 'id = ? AND company_id = ?', [(int)$id, $companyId]);
        }
        
        Middleware::sendSuccess($db->fetch("SELECT * FROM warehouses WHERE id = ?", [(int)$id]), 'تم تحديث المستودع');
        break;
        
    case 'DELETE':
        Middleware::permission('inventory.delete');
        $id = $_GET['id'] ?? null;
        if (!$id) Middleware::sendError('معرف المستودع مطلوب', 400);
        
        $stockCount = $db->fetch("SELECT COUNT(*) as c FROM product_stock WHERE warehouse_id = ? AND quantity > 0", [(int)$id])['c'];
        if ($stockCount > 0) {
            Middleware::sendError('لا يمكن حذف المستودع لأنه يحتوي على مخزون', 400);
        }
        
        $db->delete('product_stock', 'warehouse_id = ?', [(int)$id]);
        $db->delete('warehouses', 'id = ? AND company_id = ?', [(int)$id, $companyId]);
        Middleware::sendSuccess(null, 'تم حذف المستودع');
        break;
        
    default:
        Middleware::sendError('طريقة غير مدعومة', 405);
}
