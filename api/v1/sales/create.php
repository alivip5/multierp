<?php
/**
 * API Create Invoice
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../../includes/Database.php';
require_once __DIR__ . '/../../../includes/Auth.php';

// Authentication Check
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$db = Database::getInstance();
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Basic Validation
if (empty($input['customer_id']) || empty($input['items']) || !is_array($input['items'])) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$conn = $db->getConnection();
$conn->beginTransaction();

try {
    $company_id = $_SESSION['company_id'] ?? 1;
    
    // Generate Invoice Number (Simple Auto-increment logic or standard format)
    $lastInvoice = $db->fetch("SELECT invoice_number FROM sales_invoices WHERE company_id = ? ORDER BY id DESC LIMIT 1", [$company_id]);
    $nextNum = 1;
    if ($lastInvoice) {
        $lastNum = (int) filter_var($lastInvoice['invoice_number'], FILTER_SANITIZE_NUMBER_INT);
        $nextNum = $lastNum + 1;
    }
    $invoice_number = 'INV-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);

    // Calculate Totals
    $subtotal = 0;
    foreach ($input['items'] as $item) {
        $subtotal += $item['quantity'] * $item['price'];
    }
    $tax_rate = 15; // default tax
    $tax_amount = $subtotal * ($tax_rate / 100);
    $total = $subtotal + $tax_amount;
    
    // Payment Status
    $paid = (float)($input['paid_amount'] ?? 0);
    $status = 'unpaid';
    if ($paid >= $total) $status = 'paid';
    elseif ($paid > 0) $status = 'partial';

    // Insert Invoice
    $stmt = $conn->prepare("INSERT INTO sales_invoices 
        (company_id, customer_id, invoice_number, invoice_date, subtotal, tax_amount, tax_rate, total, paid_amount, payment_status, notes, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $company_id,
        $input['customer_id'],
        $invoice_number,
        $input['invoice_date'],
        $subtotal,
        $tax_amount,
        $tax_rate,
        $total,
        $paid,
        $status,
        $input['notes'] ?? '',
        $_SESSION['user_id']
    ]);
    
    $invoice_id = $conn->lastInsertId();

    // Insert Items and Update Stock
    $stmtItem = $conn->prepare("INSERT INTO sales_invoice_items 
        (invoice_id, product_id, quantity, unit_price, total) 
        VALUES (?, ?, ?, ?, ?)");

    $stmtStock = $conn->prepare("INSERT INTO product_stock 
        (product_id, quantity, company_id, type, reference_id, created_at) 
        VALUES (?, ?, ?, 'out', ?, NOW())");

    foreach ($input['items'] as $item) {
        // Insert item
        $itemTotal = $item['quantity'] * $item['price'];
        $stmtItem->execute([
            $invoice_id,
            $item['product_id'],
            $item['quantity'],
            $item['price'],
            $itemTotal
        ]);

        // Deduct from stock (negative quantity for 'out' if schema expects quantity to be signed sum, or just log 'out')
        // Assuming schema: product_stock(product_id, quantity, ...) where quantity is change amount
        // If type is 'out', we should insert negative quantity usually, or logic depends on implementation.
        // Let's check `product_stock` schema or usage in dashboard.
        // Dashboard uses: SUM(ps.quantity) ...
        // So 'out' implies negative quantity
        $stmtStock->execute([
            $item['product_id'],
            -$item['quantity'],
            $company_id,
            $invoice_id
        ]);
        
        // Also update customer balance if unpaid? Assuming customer.balance tracks debt.
        // Simplified for now.
    }

    $conn->commit();
    echo json_encode(['success' => true, 'id' => $invoice_id, 'message' => 'Invoice created']);

} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
