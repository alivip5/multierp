<?php
/**
 * Reports API - General Reports Endpoint
 * نقاط API للتقارير
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

if ($method !== 'GET') {
    Middleware::sendError('طريقة غير مدعومة', 405);
}

$reportType = $_GET['type'] ?? '';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

switch ($reportType) {
    case 'sales_summary':
        $data = $db->fetch(
            "SELECT 
                COUNT(*) as total_invoices,
                COALESCE(SUM(total), 0) as total_sales,
                COALESCE(SUM(tax_amount), 0) as total_tax,
                COALESCE(SUM(paid_amount), 0) as total_paid,
                COALESCE(SUM(total) - SUM(paid_amount), 0) as total_due
             FROM sales_invoices 
             WHERE company_id = ? AND invoice_date BETWEEN ? AND ?",
            [$companyId, $startDate, $endDate]
        );
        Middleware::sendSuccess($data);
        break;
        
    case 'purchases_summary':
        $data = $db->fetch(
            "SELECT 
                COUNT(*) as total_invoices,
                COALESCE(SUM(total), 0) as total_purchases,
                COALESCE(SUM(tax_amount), 0) as total_tax
             FROM purchase_invoices 
             WHERE company_id = ? AND invoice_date BETWEEN ? AND ?",
            [$companyId, $startDate, $endDate]
        );
        Middleware::sendSuccess($data);
        break;
        
    case 'inventory':
        $data = $db->fetchAll(
            "SELECT p.id, p.name, p.code, p.min_stock,
                COALESCE(SUM(ps.quantity), 0) as current_stock,
                p.selling_price, p.purchase_price
             FROM products p
             LEFT JOIN product_stock ps ON p.id = ps.product_id
             WHERE p.company_id = ?
             GROUP BY p.id
             ORDER BY p.name",
            [$companyId]
        );
        Middleware::sendSuccess($data);
        break;
        
    case 'low_stock':
        $data = $db->fetchAll(
            "SELECT p.id, p.name, p.code, p.min_stock,
                COALESCE(SUM(ps.quantity), 0) as current_stock
             FROM products p
             LEFT JOIN product_stock ps ON p.id = ps.product_id
             WHERE p.company_id = ? AND p.track_inventory = 1
             GROUP BY p.id
             HAVING current_stock <= p.min_stock
             ORDER BY current_stock ASC",
            [$companyId]
        );
        Middleware::sendSuccess($data);
        break;
        
    case 'top_products':
        $limit = min(20, (int)($_GET['limit'] ?? 10));
        $data = $db->fetchAll(
            "SELECT p.id, p.name, p.code,
                SUM(si.quantity) as total_sold,
                SUM(si.total) as total_revenue
             FROM sales_invoice_items si
             JOIN sales_invoices s ON si.invoice_id = s.id
             JOIN products p ON si.product_id = p.id
             WHERE s.company_id = ? AND s.invoice_date BETWEEN ? AND ?
             GROUP BY p.id
             ORDER BY total_sold DESC
             LIMIT $limit",
            [$companyId, $startDate, $endDate]
        );
        Middleware::sendSuccess($data);
        break;
        
    case 'employees':
        $data = [
            'total' => $db->fetch("SELECT COUNT(*) as c FROM employees WHERE company_id = ?", [$companyId])['c'],
            'active' => $db->fetch("SELECT COUNT(*) as c FROM employees WHERE company_id = ? AND status = 'active'", [$companyId])['c'],
            'by_department' => $db->fetchAll(
                "SELECT d.name, COUNT(e.id) as count 
                 FROM departments d LEFT JOIN employees e ON d.id = e.department_id 
                 WHERE d.company_id = ? GROUP BY d.id",
                [$companyId]
            )
        ];
        Middleware::sendSuccess($data);
        break;
        
    case 'profit_loss':
        $sales = $db->fetch(
            "SELECT COALESCE(SUM(total), 0) as amount FROM sales_invoices WHERE company_id = ? AND invoice_date BETWEEN ? AND ?",
            [$companyId, $startDate, $endDate]
        )['amount'];
        
        $purchases = $db->fetch(
            "SELECT COALESCE(SUM(total), 0) as amount FROM purchase_invoices WHERE company_id = ? AND invoice_date BETWEEN ? AND ?",
            [$companyId, $startDate, $endDate]
        )['amount'];
        
        Middleware::sendSuccess([
            'revenue' => (float)$sales,
            'costs' => (float)$purchases,
            'gross_profit' => (float)$sales - (float)$purchases,
            'period' => ['start' => $startDate, 'end' => $endDate]
        ]);
        break;
        
    default:
        Middleware::sendError('نوع التقرير غير صالح. الأنواع المتاحة: sales_summary, purchases_summary, inventory, low_stock, top_products, employees, profit_loss', 400);
}
