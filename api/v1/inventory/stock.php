<?php
/**
 * Inventory API - Stock Transfers Endpoint
 * نقاط API لتحويلات المخزون
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
        // Get stock for a product or warehouse
        $productId = $_GET['product_id'] ?? null;
        $warehouseId = $_GET['warehouse_id'] ?? null;
        
        if ($productId) {
            $stock = $db->fetchAll(
                "SELECT ps.*, w.name as warehouse_name 
                 FROM product_stock ps
                 JOIN warehouses w ON ps.warehouse_id = w.id
                 WHERE ps.product_id = ? AND w.company_id = ?",
                [(int)$productId, $companyId]
            );
            Middleware::sendSuccess($stock);
        } elseif ($warehouseId) {
            $stock = $db->fetchAll(
                "SELECT ps.*, p.name as product_name, p.code as product_code
                 FROM product_stock ps
                 JOIN products p ON ps.product_id = p.id
                 WHERE ps.warehouse_id = ?",
                [(int)$warehouseId]
            );
            Middleware::sendSuccess($stock);
        } else {
            Middleware::sendError('product_id أو warehouse_id مطلوب', 400);
        }
        break;
        
    case 'POST':
        // Transfer stock between warehouses or adjust stock
        Middleware::permission('inventory.edit');
        $input = Middleware::getJsonInput();
        
        $action = $input['action'] ?? 'transfer'; // transfer, adjust
        
        if ($action === 'transfer') {
            if (empty($input['product_id']) || empty($input['from_warehouse']) || empty($input['to_warehouse']) || empty($input['quantity'])) {
                Middleware::sendError('جميع حقول التحويل مطلوبة', 400);
            }
            
            $productId = (int)$input['product_id'];
            $fromWarehouse = (int)$input['from_warehouse'];
            $toWarehouse = (int)$input['to_warehouse'];
            $quantity = (float)$input['quantity'];
            
            // Check available stock
            $currentStock = $db->fetch(
                "SELECT quantity FROM product_stock WHERE product_id = ? AND warehouse_id = ?",
                [$productId, $fromWarehouse]
            );
            
            if (!$currentStock || $currentStock['quantity'] < $quantity) {
                Middleware::sendError('الكمية المتاحة غير كافية', 400);
            }
            
            // Decrease from source
            $db->update('product_stock', 
                ['quantity' => $currentStock['quantity'] - $quantity],
                'product_id = ? AND warehouse_id = ?',
                [$productId, $fromWarehouse]
            );
            
            // Increase in destination
            $destStock = $db->fetch(
                "SELECT * FROM product_stock WHERE product_id = ? AND warehouse_id = ?",
                [$productId, $toWarehouse]
            );
            
            if ($destStock) {
                $db->update('product_stock',
                    ['quantity' => $destStock['quantity'] + $quantity],
                    'product_id = ? AND warehouse_id = ?',
                    [$productId, $toWarehouse]
                );
            } else {
                $db->insert('product_stock', [
                    'product_id' => $productId,
                    'warehouse_id' => $toWarehouse,
                    'quantity' => $quantity
                ]);
            }
            
            Middleware::sendSuccess(null, 'تم تحويل المخزون بنجاح');
            
        } elseif ($action === 'adjust') {
            if (empty($input['product_id']) || empty($input['warehouse_id']) || !isset($input['quantity'])) {
                Middleware::sendError('حقول التعديل مطلوبة', 400);
            }
            
            $productId = (int)$input['product_id'];
            $warehouseId = (int)$input['warehouse_id'];
            $quantity = (float)$input['quantity'];
            
            $existing = $db->fetch(
                "SELECT * FROM product_stock WHERE product_id = ? AND warehouse_id = ?",
                [$productId, $warehouseId]
            );
            
            if ($existing) {
                $db->update('product_stock',
                    ['quantity' => $quantity],
                    'product_id = ? AND warehouse_id = ?',
                    [$productId, $warehouseId]
                );
            } else {
                $db->insert('product_stock', [
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => $quantity
                ]);
            }
            
            Middleware::sendSuccess(null, 'تم تحديث المخزون');
        } else {
            Middleware::sendError('إجراء غير صالح', 400);
        }
        break;
        
    default:
        Middleware::sendError('طريقة غير مدعومة', 405);
}
