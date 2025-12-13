<?php
/**
 * API Create Purchase Invoice
 * تم تحديثه: دعم المخزن المحدد وتسجيل الحركة المخزنية
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../includes/Database.php';
require_once __DIR__ . '/../../../includes/Auth.php';

// Authentication Check
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'غير مصرح']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'طريقة غير مسموحة']);
    exit;
}

$db = Database::getInstance();
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'بيانات غير صالحة']);
    exit;
}

if (empty($input['items']) || !is_array($input['items'])) {
    echo json_encode(['success' => false, 'error' => 'بنود الفاتورة مطلوبة']);
    exit;
}

// التحقق من المخزن
$warehouseId = isset($input['warehouse_id']) ? (int)$input['warehouse_id'] : null;
if (!$warehouseId) {
    echo json_encode(['success' => false, 'error' => 'يجب اختيار المخزن']);
    exit;
}

$conn = $db->getConnection();
$conn->beginTransaction();

try {
    $company_id = $_SESSION['company_id'] ?? 1;
    $user_id = $_SESSION['user_id'];
    
    // التحقق من وجود المخزن
    $warehouse = $db->fetch("SELECT * FROM warehouses WHERE id = ? AND company_id = ?", [$warehouseId, $company_id]);
    if (!$warehouse) {
        throw new Exception('المخزن غير موجود');
    }
    
    // Generate Invoice Number
    $lastInvoice = $db->fetch("SELECT invoice_number FROM purchase_invoices WHERE company_id = ? ORDER BY id DESC LIMIT 1", [$company_id]);
    $nextNum = 1;
    if ($lastInvoice) {
        preg_match('/(\d+)$/', $lastInvoice['invoice_number'], $matches);
        $nextNum = ((int)($matches[1] ?? 0)) + 1;
    }
    $invoice_number = 'PINV-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);

    // Calculate Totals
    $subtotal = 0;
    foreach ($input['items'] as $item) {
        $subtotal += (float)$item['quantity'] * (float)$item['price'];
    }
    
    // حساب الخصم
    $discountType = $input['discount_type'] ?? 'fixed';
    $discountValue = (float)($input['discount_value'] ?? 0);
    $discountAmount = 0;
    
    if ($discountType === 'percent') {
        $discountAmount = $subtotal * ($discountValue / 100);
    } else {
        $discountAmount = $discountValue;
    }
    
    $afterDiscount = $subtotal - $discountAmount;
    
    // حساب الضريبة
    $taxRate = (float)($input['tax_rate'] ?? 15);
    $taxAmount = $afterDiscount * ($taxRate / 100);
    
    $total = $afterDiscount + $taxAmount;
    
    // Insert Invoice
    $invoiceId = $db->insert('purchase_invoices', [
        'company_id' => $company_id,
        'supplier_id' => $input['supplier_id'] ?: null,
        'invoice_number' => $invoice_number,
        'invoice_date' => $input['invoice_date'] ?? date('Y-m-d'),
        'warehouse_id' => $warehouseId,
        'subtotal' => $subtotal,
        'discount_amount' => $discountAmount,
        'tax_amount' => $taxAmount,
        'total' => $total,
        'status' => 'received',
        'payment_status' => 'unpaid',
        'created_by' => $user_id
    ]);

    // Insert Items and Update Stock
    foreach ($input['items'] as $item) {
        $productId = (int)$item['product_id'];
        $quantity = (float)$item['quantity'];
        $unitPrice = (float)$item['price'];
        $itemTotal = $quantity * $unitPrice;
        
        // إضافة بند الفاتورة
        $db->insert('purchase_invoice_items', [
            'invoice_id' => $invoiceId,
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total' => $itemTotal
        ]);
        
        // جلب معلومات المنتج
        $product = $db->fetch("SELECT * FROM products WHERE id = ?", [$productId]);
        
        if ($product && $product['track_inventory']) {
            // الحصول على الرصيد الحالي
            $currentStock = $db->fetch(
                "SELECT * FROM product_stock WHERE product_id = ? AND warehouse_id = ?",
                [$productId, $warehouseId]
            );
            
            $balanceBefore = $currentStock ? (float)$currentStock['quantity'] : 0;
            $balanceAfter = $balanceBefore + $quantity;
            
            // تحديث أو إنشاء سجل المخزون
            if ($currentStock) {
                // حساب متوسط التكلفة
                $oldQty = (float)$currentStock['quantity'];
                $oldCost = (float)$currentStock['avg_cost'];
                $newAvgCost = ($oldQty > 0) 
                    ? (($oldQty * $oldCost) + ($quantity * $unitPrice)) / ($oldQty + $quantity)
                    : $unitPrice;
                
                $db->update('product_stock', 
                    [
                        'quantity' => $balanceAfter,
                        'avg_cost' => $newAvgCost
                    ],
                    'product_id = ? AND warehouse_id = ?',
                    [$productId, $warehouseId]
                );
            } else {
                $db->insert('product_stock', [
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => $quantity,
                    'avg_cost' => $unitPrice
                ]);
            }
            
            // تسجيل حركة مخزنية (IN)
            $db->insert('inventory_movements', [
                'company_id' => $company_id,
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'movement_type' => 'in',
                'reference_type' => 'purchase_invoice',
                'reference_id' => $invoiceId,
                'quantity' => $quantity,
                'unit_cost' => $unitPrice,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'notes' => 'فاتورة شراء رقم ' . $invoice_number,
                'created_by' => $user_id
            ]);
        }
    }
    
    // تحديث رصيد المورد
    if (!empty($input['supplier_id'])) {
        $db->query(
            "UPDATE suppliers SET balance = balance + ? WHERE id = ?",
            [$total, (int)$input['supplier_id']]
        );
    }

    $conn->commit();
    echo json_encode([
        'success' => true, 
        'id' => $invoiceId, 
        'invoice_number' => $invoice_number,
        'message' => 'تم إنشاء فاتورة الشراء بنجاح'
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
