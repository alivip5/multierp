<?php
/**
 * API المنتجات - المخازن
 * Products API Endpoint
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../includes/Database.php';
require_once __DIR__ . '/../../../includes/JWT.php';
require_once __DIR__ . '/../../../includes/Auth.php';
require_once __DIR__ . '/../../../includes/Middleware.php';

Middleware::cors();

// التحقق من تفعيل الموديول
if (!Middleware::moduleEnabled('inventory')) {
    exit;
}

$user = Auth::user();
$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

// الحصول على معرف المنتج من URL إن وجد
$productId = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        if ($productId) {
            // عرض منتج واحد
            $product = $db->fetch(
                "SELECT p.*, c.name as category_name, u.name as unit_name,
                        COALESCE(SUM(ps.quantity), 0) as total_stock
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 LEFT JOIN units u ON p.unit_id = u.id
                 LEFT JOIN product_stock ps ON p.id = ps.product_id
                 WHERE p.id = ? AND p.company_id = ?
                 GROUP BY p.id",
                [$productId, $user['company_id']]
            );
            
            if (!$product) {
                Middleware::sendError('المنتج غير موجود', 404);
            }
            
            Middleware::sendSuccess($product);
        } else {
            // قائمة المنتجات مع الفلترة والصفحات
            $query = Middleware::getQuery();
            $page = max(1, (int)($query['page'] ?? 1));
            $limit = min(100, max(1, (int)($query['limit'] ?? ITEMS_PER_PAGE)));
            $offset = ($page - 1) * $limit;
            
            $where = "p.company_id = ?";
            $params = [$user['company_id']];
            
            // البحث
            if (!empty($query['search'])) {
                $where .= " AND (p.name LIKE ? OR p.code LIKE ? OR p.barcode LIKE ?)";
                $search = "%{$query['search']}%";
                $params = array_merge($params, [$search, $search, $search]);
            }
            
            // فلتر الفئة
            if (!empty($query['category_id'])) {
                $where .= " AND p.category_id = ?";
                $params[] = $query['category_id'];
            }
            
            // فلتر الحالة
            if (isset($query['is_active'])) {
                $where .= " AND p.is_active = ?";
                $params[] = $query['is_active'];
            }
            
            // الحصول على العدد الكلي
            $total = $db->fetch("SELECT COUNT(*) as count FROM products p WHERE $where", $params)['count'];
            
            // الحصول على المنتجات
            $products = $db->fetchAll(
                "SELECT p.*, c.name as category_name, u.name as unit_name,
                        COALESCE(SUM(ps.quantity), 0) as total_stock
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id
                 LEFT JOIN units u ON p.unit_id = u.id
                 LEFT JOIN product_stock ps ON p.id = ps.product_id
                 WHERE $where
                 GROUP BY p.id
                 ORDER BY p.created_at DESC
                 LIMIT $limit OFFSET $offset",
                $params
            );
            
            Middleware::sendSuccess([
                'items' => $products,
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
        if (!Auth::can('products.manage')) {
            Middleware::sendError('غير مصرح', 403);
        }
        
        $data = Middleware::getJsonInput();
        
        $errors = Middleware::validate($data, [
            'name' => 'required|max:255',
            'selling_price' => 'required|numeric'
        ]);
        
        if (!empty($errors)) {
            Middleware::sendJson(['success' => false, 'errors' => $errors], 422);
        }
        
        // التحقق من عدم تكرار الكود
        if (!empty($data['code'])) {
            $exists = $db->fetch(
                "SELECT id FROM products WHERE code = ? AND company_id = ?",
                [$data['code'], $user['company_id']]
            );
            if ($exists) {
                Middleware::sendError('كود المنتج مستخدم مسبقاً', 422);
            }
        }
        
        $productData = [
            'company_id' => $user['company_id'],
            'name' => $data['name'],
            'name_en' => $data['name_en'] ?? null,
            'code' => $data['code'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'unit_id' => $data['unit_id'] ?? null,
            'purchase_price' => $data['purchase_price'] ?? 0,
            'selling_price' => $data['selling_price'],
            'min_selling_price' => $data['min_selling_price'] ?? 0,
            'wholesale_price' => $data['wholesale_price'] ?? 0,
            'tax_rate' => $data['tax_rate'] ?? 0,
            'is_taxable' => $data['is_taxable'] ?? 1,
            'min_stock' => $data['min_stock'] ?? 0,
            'reorder_level' => $data['reorder_level'] ?? 0,
            'is_active' => $data['is_active'] ?? 1,
            'is_service' => $data['is_service'] ?? 0,
            'track_inventory' => $data['track_inventory'] ?? 1,
            'created_by' => $user['id']
        ];
        
        $id = $db->insert('products', $productData);
        
        Auth::logAudit($user['id'], 'create', 'products', $id, null, $productData, $user['company_id']);
        
        Middleware::sendSuccess(['id' => $id], 'تم إضافة المنتج بنجاح');
        break;
        
    case 'PUT':
        if (!$productId) {
            Middleware::sendError('معرف المنتج مطلوب', 400);
        }
        
        if (!Auth::can('products.manage')) {
            Middleware::sendError('غير مصرح', 403);
        }
        
        $existing = $db->fetch(
            "SELECT * FROM products WHERE id = ? AND company_id = ?",
            [$productId, $user['company_id']]
        );
        
        if (!$existing) {
            Middleware::sendError('المنتج غير موجود', 404);
        }
        
        $data = Middleware::getJsonInput();
        
        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'name_en' => $data['name_en'] ?? null,
            'code' => $data['code'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'unit_id' => $data['unit_id'] ?? null,
            'purchase_price' => $data['purchase_price'] ?? null,
            'selling_price' => $data['selling_price'] ?? null,
            'min_selling_price' => $data['min_selling_price'] ?? null,
            'wholesale_price' => $data['wholesale_price'] ?? null,
            'tax_rate' => $data['tax_rate'] ?? null,
            'is_taxable' => isset($data['is_taxable']) ? $data['is_taxable'] : null,
            'min_stock' => $data['min_stock'] ?? null,
            'reorder_level' => $data['reorder_level'] ?? null,
            'is_active' => isset($data['is_active']) ? $data['is_active'] : null
        ], fn($v) => $v !== null);
        
        if (!empty($updateData)) {
            $db->update('products', $updateData, 'id = ?', [$productId]);
            Auth::logAudit($user['id'], 'update', 'products', $productId, $existing, $updateData, $user['company_id']);
        }
        
        Middleware::sendSuccess(null, 'تم تحديث المنتج بنجاح');
        break;
        
    case 'DELETE':
        if (!$productId) {
            Middleware::sendError('معرف المنتج مطلوب', 400);
        }
        
        if (!Auth::can('products.manage')) {
            Middleware::sendError('غير مصرح', 403);
        }
        
        $existing = $db->fetch(
            "SELECT * FROM products WHERE id = ? AND company_id = ?",
            [$productId, $user['company_id']]
        );
        
        if (!$existing) {
            Middleware::sendError('المنتج غير موجود', 404);
        }
        
        $db->delete('products', 'id = ?', [$productId]);
        
        Auth::logAudit($user['id'], 'delete', 'products', $productId, $existing, null, $user['company_id']);
        
        Middleware::sendSuccess(null, 'تم حذف المنتج بنجاح');
        break;
        
    default:
        Middleware::sendError('طريقة الطلب غير مسموحة', 405);
}
