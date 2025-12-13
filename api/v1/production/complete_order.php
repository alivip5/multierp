<?php
/**
 * API Production Orders - Complete Order
 * تنفيذ/إكمال أمر الإنتاج مع خصم المواد الخام وإضافة المنتج النهائي
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../includes/Database.php';
require_once __DIR__ . '/../../../includes/Auth.php';
require_once __DIR__ . '/../../../includes/Middleware.php';

Middleware::cors();
Middleware::auth();

$db = Database::getInstance();
$company_id = $_SESSION['company_id'] ?? 1;
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'طريقة غير مسموحة']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$orderId = (int)($input['order_id'] ?? 0);
$action = $input['action'] ?? 'complete'; // complete, cancel
$outputWarehouseId = (int)($input['output_warehouse_id'] ?? 0);
$rawMaterialWarehouseId = (int)($input['raw_material_warehouse_id'] ?? 0);

if (!$orderId) {
    echo json_encode(['success' => false, 'error' => 'رقم أمر الإنتاج مطلوب']);
    exit;
}

// جلب أمر الإنتاج
$order = $db->fetch(
    "SELECT po.*, p.name as product_name, pb.id as bom_id
     FROM production_orders po
     LEFT JOIN products p ON po.product_id = p.id
     LEFT JOIN production_bom pb ON po.bom_id = pb.id
     WHERE po.id = ? AND po.company_id = ?",
    [$orderId, $company_id]
);

if (!$order) {
    echo json_encode(['success' => false, 'error' => 'أمر الإنتاج غير موجود']);
    exit;
}

if ($order['status'] === 'completed') {
    echo json_encode(['success' => false, 'error' => 'أمر الإنتاج مكتمل بالفعل']);
    exit;
}

if ($order['status'] === 'cancelled') {
    echo json_encode(['success' => false, 'error' => 'أمر الإنتاج ملغي']);
    exit;
}

$conn = $db->getConnection();
$conn->beginTransaction();

try {
    if ($action === 'complete') {
        // التحقق من وجود مخزن للاستلام
        if (!$outputWarehouseId) {
            // استخدام المخزن الافتراضي
            $defaultWarehouse = $db->fetch(
                "SELECT id FROM warehouses WHERE company_id = ? AND is_default = 1 LIMIT 1",
                [$company_id]
            );
            $outputWarehouseId = $defaultWarehouse['id'] ?? 0;
        }
        
        if (!$outputWarehouseId) {
            throw new Exception('يجب تحديد مخزن استلام المنتج النهائي');
        }
        
        // إذا لم يحدد مخزن صرف المواد، نستخدم نفس مخزن الاستلام أو المخزن الافتراضي
        if (!$rawMaterialWarehouseId) {
            $rawMaterialWarehouseId = $outputWarehouseId;
        }
        
        $producedQuantity = (float)$order['quantity'];
        
        // إذا كان هناك BOM، نخصم المواد الخام
        if ($order['bom_id']) {
            // جلب بنود BOM
            $bomItems = $db->fetchAll(
                "SELECT bi.*, p.name as material_name, p.track_inventory
                 FROM production_bom_items bi
                 JOIN products p ON bi.material_id = p.id
                 WHERE bi.bom_id = ?",
                [$order['bom_id']]
            );
            
            foreach ($bomItems as $item) {
                if (!$item['track_inventory']) continue;
                
                $materialId = (int)$item['material_id'];
                $requiredQty = (float)$item['quantity'] * $producedQuantity;
                
                // التحقق من توفر المواد
                $currentStock = $db->fetch(
                    "SELECT * FROM product_stock WHERE product_id = ? AND warehouse_id = ?",
                    [$materialId, $rawMaterialWarehouseId]
                );
                
                if (!$currentStock || $currentStock['quantity'] < $requiredQty) {
                    throw new Exception("الكمية غير كافية من المادة: {$item['material_name']}");
                }
                
                $balanceBefore = (float)$currentStock['quantity'];
                $balanceAfter = $balanceBefore - $requiredQty;
                
                // خصم الكمية
                $db->update('product_stock',
                    ['quantity' => $balanceAfter],
                    'product_id = ? AND warehouse_id = ?',
                    [$materialId, $rawMaterialWarehouseId]
                );
                
                // تسجيل حركة OUT
                $db->insert('inventory_movements', [
                    'company_id' => $company_id,
                    'product_id' => $materialId,
                    'warehouse_id' => $rawMaterialWarehouseId,
                    'movement_type' => 'production_out',
                    'reference_type' => 'production_order',
                    'reference_id' => $orderId,
                    'quantity' => $requiredQty,
                    'unit_cost' => $item['unit_cost'] ?? 0,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'notes' => 'صرف مواد لأمر الإنتاج رقم ' . $order['order_number'],
                    'created_by' => $user_id
                ]);
                
                // تحديث الكمية المستهلكة في مواد الأمر
                $db->query(
                    "UPDATE production_order_materials 
                     SET consumed_quantity = ? 
                     WHERE order_id = ? AND material_id = ?",
                    [$requiredQty, $orderId, $materialId]
                );
            }
        }
        
        // إضافة المنتج النهائي للمخزن
        $productId = (int)$order['product_id'];
        
        $productStock = $db->fetch(
            "SELECT * FROM product_stock WHERE product_id = ? AND warehouse_id = ?",
            [$productId, $outputWarehouseId]
        );
        
        $productBalanceBefore = $productStock ? (float)$productStock['quantity'] : 0;
        $productBalanceAfter = $productBalanceBefore + $producedQuantity;
        
        if ($productStock) {
            $db->update('product_stock',
                ['quantity' => $productBalanceAfter],
                'product_id = ? AND warehouse_id = ?',
                [$productId, $outputWarehouseId]
            );
        } else {
            $db->insert('product_stock', [
                'product_id' => $productId,
                'warehouse_id' => $outputWarehouseId,
                'quantity' => $producedQuantity,
                'avg_cost' => $order['total_cost'] / $producedQuantity ?? 0
            ]);
        }
        
        // تسجيل حركة IN للمنتج النهائي
        $db->insert('inventory_movements', [
            'company_id' => $company_id,
            'product_id' => $productId,
            'warehouse_id' => $outputWarehouseId,
            'movement_type' => 'production_in',
            'reference_type' => 'production_order',
            'reference_id' => $orderId,
            'quantity' => $producedQuantity,
            'unit_cost' => $order['total_cost'] / $producedQuantity ?? 0,
            'balance_before' => $productBalanceBefore,
            'balance_after' => $productBalanceAfter,
            'notes' => 'استلام منتج من أمر الإنتاج رقم ' . $order['order_number'],
            'created_by' => $user_id
        ]);
        
        // تحديث أمر الإنتاج
        $db->update('production_orders', [
            'status' => 'completed',
            'produced_quantity' => $producedQuantity,
            'output_warehouse_id' => $outputWarehouseId,
            'raw_material_warehouse_id' => $rawMaterialWarehouseId,
            'completion_date' => date('Y-m-d H:i:s')
        ], 'id = ?', [$orderId]);
        
        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'تم إكمال أمر الإنتاج بنجاح وتحديث المخازن'
        ]);
        
    } elseif ($action === 'cancel') {
        $db->update('production_orders', [
            'status' => 'cancelled'
        ], 'id = ?', [$orderId]);
        
        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'تم إلغاء أمر الإنتاج'
        ]);
        
    } else {
        throw new Exception('إجراء غير صالح');
    }
    
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
