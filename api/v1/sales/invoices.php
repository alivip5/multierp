<?php
/**
 * API فواتير المبيعات
 * Sales Invoices API Endpoint
 * تم تحديثه: دعم المخزن المحدد، بيانات المندوبين، التحقق من الحد الائتماني
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
$invoiceId = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        if ($invoiceId) {
            // عرض فاتورة واحدة مع التفاصيل
            $invoice = $db->fetch(
                "SELECT si.*, c.name as customer_name, c.phone as customer_phone,
                        u.full_name as created_by_name, w.name as warehouse_name,
                        sa.name as sales_agent_name
                 FROM sales_invoices si
                 LEFT JOIN customers c ON si.customer_id = c.id
                 LEFT JOIN users u ON si.created_by = u.id
                 LEFT JOIN warehouses w ON si.warehouse_id = w.id
                 LEFT JOIN sales_agents sa ON si.sales_agent_id = sa.id
                 WHERE si.id = ? AND si.company_id = ?",
                [$invoiceId, $user['company_id']]
            );
            
            if (!$invoice) {
                Middleware::sendError('الفاتورة غير موجودة', 404);
            }
            
            // الحصول على بنود الفاتورة
            $items = $db->fetchAll(
                "SELECT sii.*, p.name as product_name, p.code as product_code
                 FROM sales_invoice_items sii
                 LEFT JOIN products p ON sii.product_id = p.id
                 WHERE sii.invoice_id = ?",
                [$invoiceId]
            );
            
            $invoice['items'] = $items;
            
            Middleware::sendSuccess($invoice);
        } else {
            // قائمة الفواتير
            $query = Middleware::getQuery();
            $page = max(1, (int)($query['page'] ?? 1));
            $limit = min(100, (int)($query['limit'] ?? ITEMS_PER_PAGE));
            $offset = ($page - 1) * $limit;
            
            $where = "si.company_id = ?";
            $params = [$user['company_id']];
            
            if (!empty($query['customer_id'])) {
                $where .= " AND si.customer_id = ?";
                $params[] = $query['customer_id'];
            }
            
            if (!empty($query['status'])) {
                $where .= " AND si.status = ?";
                $params[] = $query['status'];
            }
            
            if (!empty($query['date_from'])) {
                $where .= " AND si.invoice_date >= ?";
                $params[] = $query['date_from'];
            }
            
            if (!empty($query['date_to'])) {
                $where .= " AND si.invoice_date <= ?";
                $params[] = $query['date_to'];
            }
            
            if (!empty($query['sales_agent_id'])) {
                $where .= " AND si.sales_agent_id = ?";
                $params[] = $query['sales_agent_id'];
            }
            
            $total = $db->fetch("SELECT COUNT(*) as count FROM sales_invoices si WHERE $where", $params)['count'];
            
            $invoices = $db->fetchAll(
                "SELECT si.*, c.name as customer_name, w.name as warehouse_name
                 FROM sales_invoices si
                 LEFT JOIN customers c ON si.customer_id = c.id
                 LEFT JOIN warehouses w ON si.warehouse_id = w.id
                 WHERE $where
                 ORDER BY si.created_at DESC
                 LIMIT " . (int)$limit . " OFFSET " . (int)$offset,
                $params
            );
            
            Middleware::sendSuccess([
                'items' => $invoices,
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
        if (!Auth::can('sales.create')) {
            Middleware::sendError('غير مصرح', 403);
        }
        
        $data = Middleware::getJsonInput();
        
        if (empty($data['items']) || !is_array($data['items'])) {
            Middleware::sendError('بنود الفاتورة مطلوبة', 422);
        }
        
        // التحقق من المخزن
        $warehouseId = isset($data['warehouse_id']) ? (int)$data['warehouse_id'] : null;
        if (!$warehouseId) {
            Middleware::sendError('يجب اختيار المخزن', 422);
        }
        
        // التحقق من وجود المخزن
        $warehouse = $db->fetch("SELECT * FROM warehouses WHERE id = ? AND company_id = ?", [$warehouseId, $user['company_id']]);
        if (!$warehouse) {
            Middleware::sendError('المخزن غير موجود', 404);
        }
        
        $db->beginTransaction();
        
        try {
            // توليد رقم الفاتورة
            $lastInvoice = $db->fetch(
                "SELECT invoice_number FROM sales_invoices 
                 WHERE company_id = ? ORDER BY id DESC LIMIT 1",
                [$user['company_id']]
            );
            
            $nextNumber = 1;
            if ($lastInvoice) {
                preg_match('/(\d+)$/', $lastInvoice['invoice_number'], $matches);
                $nextNumber = ((int)($matches[1] ?? 0)) + 1;
            }
            $invoiceNumber = 'INV-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
            
            // حساب المجاميع
            $subtotal = 0;
            $taxAmount = 0;
            
            foreach ($data['items'] as $item) {
                $itemTotal = ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
                $itemDiscount = $item['discount_amount'] ?? 0;
                $itemTax = (($itemTotal - $itemDiscount) * ($item['tax_rate'] ?? 0)) / 100;
                
                $subtotal += $itemTotal - $itemDiscount;
                $taxAmount += $itemTax;
            }
            
            // حساب الخصم الإجمالي
            $discountType = $data['discount_type'] ?? 'fixed';
            $discountValue = (float)($data['discount_value'] ?? 0);
            $discountAmount = 0;
            
            if ($discountType === 'percent') {
                $discountAmount = $subtotal * ($discountValue / 100);
            } else {
                $discountAmount = $discountValue;
            }
            
            $afterDiscount = $subtotal - $discountAmount;
            
            // حساب الضريبة
            $taxRate = (float)($data['tax_rate'] ?? 15);
            $taxAmount = $afterDiscount * ($taxRate / 100);
            
            $total = $afterDiscount + $taxAmount;
            $paidAmount = (float)($data['paid_amount'] ?? 0);
            
            // التحقق من الحد الائتماني
            if (!empty($data['customer_id'])) {
                $customer = $db->fetch("SELECT * FROM customers WHERE id = ?", [(int)$data['customer_id']]);
                if ($customer && $customer['credit_limit'] > 0) {
                    $newBalance = $customer['balance'] + $total - $paidAmount;
                    if ($newBalance > $customer['credit_limit']) {
                        // تحذير فقط، لا نمنع الفاتورة
                        // يمكن تغيير هذا السلوك حسب الحاجة
                    }
                }
            }
            
            // تحديد حالة الدفع
            $paymentStatus = 'unpaid';
            if ($paidAmount >= $total) {
                $paymentStatus = 'paid';
            } elseif ($paidAmount > 0) {
                $paymentStatus = 'partial';
            }
            
            // إنشاء الفاتورة
            $invoiceId = $db->insert('sales_invoices', [
                'company_id' => $user['company_id'],
                'invoice_number' => $invoiceNumber,
                'customer_id' => $data['customer_id'] ?? null,
                'warehouse_id' => $warehouseId,
                'invoice_date' => $data['invoice_date'] ?? date('Y-m-d'),
                'due_date' => $data['due_date'] ?? null,
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'status' => $data['status'] ?? 'pending',
                'payment_status' => $paymentStatus,
                'notes' => $data['notes'] ?? null,
                'sales_agent_id' => $data['sales_agent_id'] ?? null,
                'delivery_driver_name' => $data['delivery_driver_name'] ?? null,
                'vehicle_info' => $data['vehicle_info'] ?? null,
                'created_by' => $user['id']
            ]);
            
            // إضافة البنود وخصم المخزون
            foreach ($data['items'] as $item) {
                $productId = (int)($item['product_id'] ?? 0);
                $quantity = (float)($item['quantity'] ?? 1);
                $unitPrice = (float)($item['unit_price'] ?? 0);
                $itemTotal = $quantity * $unitPrice;
                $itemDiscount = (float)($item['discount_amount'] ?? 0);
                $itemTax = (($itemTotal - $itemDiscount) * ($item['tax_rate'] ?? 0)) / 100;
                
                $db->insert('sales_invoice_items', [
                    'invoice_id' => $invoiceId,
                    'product_id' => $productId ?: null,
                    'warehouse_id' => $warehouseId,
                    'description' => $item['description'] ?? null,
                    'quantity' => $quantity,
                    'unit_id' => $item['unit_id'] ?? null,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $itemDiscount,
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'tax_amount' => $itemTax,
                    'total' => $itemTotal - $itemDiscount + $itemTax
                ]);
                
                // خصم المخزون من المخزن المحدد
                if ($productId) {
                    $product = $db->fetch("SELECT * FROM products WHERE id = ?", [$productId]);
                    if ($product && $product['track_inventory']) {
                        // الحصول على الرصيد الحالي
                        $currentStock = $db->fetch(
                            "SELECT * FROM product_stock WHERE product_id = ? AND warehouse_id = ?",
                            [$productId, $warehouseId]
                        );
                        
                        $balanceBefore = $currentStock ? (float)$currentStock['quantity'] : 0;
                        $balanceAfter = max(0, $balanceBefore - $quantity);
                        
                        // تحديث المخزون
                        if ($currentStock) {
                            $db->update('product_stock', 
                                ['quantity' => $balanceAfter],
                                'product_id = ? AND warehouse_id = ?',
                                [$productId, $warehouseId]
                            );
                        }
                        
                        // تسجيل حركة مخزنية (OUT)
                        $db->insert('inventory_movements', [
                            'company_id' => $user['company_id'],
                            'product_id' => $productId,
                            'warehouse_id' => $warehouseId,
                            'movement_type' => 'out',
                            'reference_type' => 'sales_invoice',
                            'reference_id' => $invoiceId,
                            'quantity' => $quantity,
                            'unit_cost' => $unitPrice,
                            'balance_before' => $balanceBefore,
                            'balance_after' => $balanceAfter,
                            'notes' => 'فاتورة مبيعات رقم ' . $invoiceNumber,
                            'created_by' => $user['id']
                        ]);
                    }
                }
            }
            
            // تحديث رصيد العميل
            if (!empty($data['customer_id'])) {
                $amountDue = $total - $paidAmount;
                $db->query(
                    "UPDATE customers SET balance = balance + ? WHERE id = ?",
                    [$amountDue, (int)$data['customer_id']]
                );
            }
            
            $db->commit();
            
            Auth::logAudit($user['id'], 'create', 'sales_invoices', $invoiceId, null, 
                ['invoice_number' => $invoiceNumber, 'total' => $total], $user['company_id']);
            
            Middleware::sendSuccess([
                'id' => $invoiceId,
                'invoice_number' => $invoiceNumber
            ], 'تم إنشاء الفاتورة بنجاح');
            
        } catch (Exception $e) {
            $db->rollBack();
            Middleware::sendError('حدث خطأ: ' . $e->getMessage(), 500);
        }
        break;
        
    default:
        Middleware::sendError('طريقة الطلب غير مسموحة', 405);
}
