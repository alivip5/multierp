<?php
/**
 * Production API - BOM (Bill of Materials) Endpoint
 * نقاط API لقوائم المواد
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
        $productId = $_GET['product_id'] ?? null;
        
        if ($id) {
            $bom = $db->fetch(
                "SELECT b.*, p.name as product_name 
                 FROM production_bom b
                 LEFT JOIN products p ON b.product_id = p.id
                 WHERE b.id = ? AND b.company_id = ?",
                [(int)$id, $companyId]
            );
            
            if ($bom) {
                $bom['items'] = $db->fetchAll(
                    "SELECT bi.*, p.name as material_name, p.code as material_code
                     FROM production_bom_items bi
                     LEFT JOIN products p ON bi.material_id = p.id
                     WHERE bi.bom_id = ?",
                    [(int)$id]
                );
                Middleware::sendSuccess($bom);
            } else {
                Middleware::sendError('قائمة المواد غير موجودة', 404);
            }
        } elseif ($productId) {
            // Get BOM for a specific product
            $bom = $db->fetch(
                "SELECT b.* FROM production_bom b WHERE b.product_id = ? AND b.company_id = ? AND b.is_active = 1",
                [(int)$productId, $companyId]
            );
            if ($bom) {
                $bom['items'] = $db->fetchAll(
                    "SELECT bi.*, p.name as material_name FROM production_bom_items bi
                     LEFT JOIN products p ON bi.material_id = p.id WHERE bi.bom_id = ?",
                    [$bom['id']]
                );
            }
            Middleware::sendSuccess($bom ?: null);
        } else {
            $boms = $db->fetchAll(
                "SELECT b.*, p.name as product_name,
                 (SELECT COUNT(*) FROM production_bom_items WHERE bom_id = b.id) as items_count
                 FROM production_bom b
                 LEFT JOIN products p ON b.product_id = p.id
                 WHERE b.company_id = ?
                 ORDER BY b.created_at DESC",
                [$companyId]
            );
            Middleware::sendSuccess($boms);
        }
        break;
        
    case 'POST':
        Middleware::permission('production.create');
        $input = Middleware::getJsonInput();
        
        if (empty($input['product_id']) || empty($input['items'])) {
            Middleware::sendError('المنتج وقائمة المواد مطلوبان', 400);
        }
        
        try {
            $bomId = $db->insert('production_bom', [
                'company_id' => $companyId,
                'product_id' => (int)$input['product_id'],
                'name' => $input['name'] ?? 'قائمة مواد افتراضية',
                'output_quantity' => $input['output_quantity'] ?? 1,
                'is_active' => 1,
                'created_by' => $_SESSION['user_id']
            ]);
            
            foreach ($input['items'] as $item) {
                $db->insert('production_bom_items', [
                    'bom_id' => $bomId,
                    'material_id' => (int)$item['material_id'],
                    'quantity' => (float)$item['quantity'],
                    'unit_cost' => $item['unit_cost'] ?? 0
                ]);
            }
            
            Middleware::sendSuccess(['id' => $bomId], 'تم إنشاء قائمة المواد', 201);
        } catch (Exception $e) {
            Middleware::sendError('خطأ في إنشاء قائمة المواد', 500);
        }
        break;
        
    case 'DELETE':
        Middleware::permission('production.delete');
        $id = $_GET['id'] ?? null;
        if (!$id) Middleware::sendError('معرف القائمة مطلوب', 400);
        
        $db->delete('production_bom_items', 'bom_id = ?', [(int)$id]);
        $db->delete('production_bom', 'id = ? AND company_id = ?', [(int)$id, $companyId]);
        Middleware::sendSuccess(null, 'تم حذف قائمة المواد');
        break;
        
    default:
        Middleware::sendError('طريقة غير مدعومة', 405);
}
