<?php
/**
 * صفحة عرض الفاتورة
 * Sales Module - View Invoice
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/SidebarHelper.php';

$db = Database::getInstance();
$invoice_id = $_GET['id'] ?? 0;

$user = $db->fetch("SELECT u.*, r.name as role_slug, r.name_ar as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?", [$_SESSION['user_id']]);
$company = $db->fetch("SELECT * FROM companies WHERE id = ?", [$_SESSION['company_id'] ?? 1]);

// التأكد من وجود بيانات الدور في الجلسة
if (!isset($_SESSION['role_id']) && $user) {
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_slug'];
}

// الموديولات للقائمة الجانبية
$enabledModules = getSidebarItems($company['id'], $_SESSION['user_id']);

$invoice = $db->fetch(
    "SELECT si.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address, 
            u.full_name as created_by_name
     FROM sales_invoices si 
     LEFT JOIN customers c ON si.customer_id = c.id 
     LEFT JOIN users u ON si.created_by = u.id
     WHERE si.id = ? AND si.company_id = ?",
    [$invoice_id, $_SESSION['company_id'] ?? 1]
);

if (!$invoice) {
    die("الفاتورة غير موجودة");
}

$items = $db->fetchAll(
    "SELECT sii.*, p.name_ar as product_name 
     FROM sales_invoice_items sii 
     LEFT JOIN products p ON sii.product_id = p.id 
     WHERE sii.invoice_id = ?", 
    [$invoice_id]
);

$pageTitle = 'فاتورة #' . $invoice['invoice_number'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="<?= $user['theme'] ?? 'dark' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .invoice-box {
            background: var(--surface);
            padding: 30px;
            border-radius: var(--radius-lg);
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        .invoice-header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .invoice-title { font-size: 24px; font-weight: bold; color: var(--primary); }
        .invoice-meta { text-align: left; }
        
        .module-submenu {
            background: var(--bg-surface);
            border-bottom: 1px solid var(--border);
            padding: 0 20px;
            margin: -20px -20px 20px -20px;
        }
        .submenu-container {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding: 12px 0;
        }
        .submenu-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: var(--radius-md);
            text-decoration: none;
            color: var(--text-muted);
            white-space: nowrap;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        .submenu-item:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }
        .submenu-item.active {
            background: var(--primary);
            color: white;
        }
        @media print {
            .sidebar, .module-submenu, .header-actions, .print-hide { display: none !important; }
            .main-content { margin: 0 !important; padding: 0 !important; }
            .invoice-box { box-shadow: none; border: none; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo"><i class="fas fa-building"></i></div>
                <span class="sidebar-brand"><?= htmlspecialchars($company['name']) ?></span>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">القائمة الرئيسية</div>
                    <?php foreach ($enabledModules as $module): ?>
                    <div class="nav-item">
                        <a href="<?= $module['slug'] === 'dashboard' ? '../../pages/dashboard.php' : ($module['slug'] === 'settings' ? '../settings/branches.php' : '../' . $module['slug'] . '/index.php') ?>" 
                           class="nav-link <?= $module['slug'] === 'sales' ? 'active' : '' ?>">
                            <i class="<?= $module['icon'] ?>"></i>
                            <span><?= htmlspecialchars($module['name_ar']) ?></span>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </nav>
            <div class="sidebar-footer">
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-chevron-right"></i>
                    <span>طي القائمة</span>
                </button>
            </div>
        </aside>

        <main class="main-content">
            <header class="header print-hide">
                <div class="header-title">
                    <h1><i class="fas fa-file-invoice"></i> <?= $pageTitle ?></h1>
                </div>
                <div class="header-actions">
                    <button class="menu-toggle-btn" onclick="toggleSidebar()" title="القائمة">
                        <i class="fas fa-bars"></i>
                    </button>
                    <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> طباعة</button>
                    <a href="index.php" class="btn btn-outline">عودة</a>
                </div>
            </header>
            
            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu print-hide">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item active"><i class="fas fa-list"></i><span>الفواتير</span></a>
                        <a href="add.php" class="submenu-item"><i class="fas fa-plus"></i><span>فاتورة جديدة</span></a>
                        <a href="customers.php" class="submenu-item"><i class="fas fa-users"></i><span>العملاء</span></a>
                    </div>
                </div>

                <div class="invoice-box">
                    <div class="invoice-header">
                        <div>
                            <div class="invoice-title"><?= htmlspecialchars($company['name']) ?></div>
                            <div><?= htmlspecialchars($company['address'] ?? '') ?></div>
                            <div><?= htmlspecialchars($company['phone'] ?? '') ?></div>
                        </div>
                        <div class="invoice-meta">
                            <h3>فاتورة مبيعات</h3>
                            <div>رقم: <b><?= htmlspecialchars($invoice['invoice_number']) ?></b></div>
                            <div>التاريخ: <?= date('Y/m/d', strtotime($invoice['invoice_date'])) ?></div>
                            <div>الحالة: <?= $invoice['payment_status'] ?></div>
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-4">
                        <div class="col-6">
                            <h5>فوترة إلى:</h5>
                            <strong><?= htmlspecialchars($invoice['customer_name'] ?? 'عميل نقدي') ?></strong><br>
                            <?= htmlspecialchars($invoice['customer_phone'] ?? '') ?><br>
                            <?= htmlspecialchars($invoice['customer_address'] ?? '') ?>
                        </div>
                    </div>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>المنتج</th>
                                <th>الكمية</th>
                                <th>السعر</th>
                                <th>الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $i => $item): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($item['product_name']) ?></td>
                                <td><?= $item['quantity'] ?></td>
                                <td><?= number_format($item['unit_price'], 2) ?></td>
                                <td><?= number_format($item['total'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-left font-bold">المجموع</td>
                                <td><?= number_format($invoice['subtotal'], 2) ?></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-left font-bold">الضريبة (<?= $invoice['tax_rate'] ?>%)</td>
                                <td><?= number_format($invoice['tax_amount'], 2) ?></td>
                            </tr>
                            <tr style="background: var(--background);">
                                <td colspan="4" class="text-left font-bold">الإجمالي الكلي</td>
                                <td class="font-bold"><?= number_format($invoice['total'], 2) ?></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-left font-bold">المدفوع</td>
                                <td><?= number_format($invoice['paid_amount'], 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>

                    <?php if ($invoice['notes']): ?>
                    <div class="mt-4">
                        <h5>ملاحظات:</h5>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($invoice['notes'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            const overlay = document.getElementById("sidebarOverlay");
            
            if (window.innerWidth < 992) {
                sidebar.classList.toggle("show");
                if (overlay) overlay.classList.toggle("show");
            } else {
                sidebar.classList.toggle("collapsed");
                localStorage.setItem("sidebarCollapsed", 
                    sidebar.classList.contains("collapsed"));
            }
        }
    </script>
</body>
</html>
