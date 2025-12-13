<?php
/**
 * تقرير الأرباح والخسائر
 * Reports Module - Profit & Loss Report
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/SidebarHelper.php';

$db = Database::getInstance();
$company_id = $_SESSION['company_id'] ?? 1;
// تحديث جلب بيانات المستخدم لجلب الدور
$user = $db->fetch("SELECT u.*, r.name_ar as role_name, r.name as role_slug FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?", [$_SESSION['user_id']]);
$company = $db->fetch("SELECT * FROM companies WHERE id = ?", [$company_id]);

// التأكد من وجود بيانات الدور في الجلسة
if (!isset($_SESSION['role_id']) && $user) {
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_slug'];
}

// الموديولات للقائمة الجانبية
$enabledModules = getSidebarItems($company['id'], $_SESSION['user_id']);

$pageTitle = 'تقرير الأرباح والخسائر';

// فترة التقرير
$date_from = $_GET['date_from'] ?? date('Y-01-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// الإيرادات (المبيعات)
$revenue = $db->fetch(
    "SELECT 
        COUNT(*) as invoice_count,
        COALESCE(SUM(total), 0) as gross_sales,
        COALESCE(SUM(discount_amount), 0) as discounts,
        COALESCE(SUM(tax_amount), 0) as sales_tax,
        COALESCE(SUM(total - discount_amount), 0) as net_sales
     FROM sales_invoices 
     WHERE company_id = ? AND invoice_date BETWEEN ? AND ? AND status != 'cancelled'",
    [$company_id, $date_from, $date_to]
);

// تكلفة البضاعة المباعة (المشتريات)
$cogs = $db->fetch(
    "SELECT 
        COUNT(*) as invoice_count,
        COALESCE(SUM(total), 0) as total_purchases,
        COALESCE(SUM(tax_amount), 0) as purchase_tax
     FROM purchase_invoices 
     WHERE company_id = ? AND invoice_date BETWEEN ? AND ? AND status != 'cancelled'",
    [$company_id, $date_from, $date_to]
);

// المصروفات من قيود اليومية
$expenses = $db->fetch(
    "SELECT COALESCE(SUM(total_debit), 0) as total_expenses
     FROM journal_entries 
     WHERE company_id = ? AND entry_date BETWEEN ? AND ? AND status = 'posted'",
    [$company_id, $date_from, $date_to]
);

// حساب الأرباح
$grossProfit = $revenue['net_sales'] - $cogs['total_purchases'];
$netProfit = $grossProfit - ($expenses['total_expenses'] ?? 0);

// المبيعات الشهرية
$monthlySales = $db->fetchAll(
    "SELECT DATE_FORMAT(invoice_date, '%Y-%m') as month, SUM(total) as total
     FROM sales_invoices 
     WHERE company_id = ? AND invoice_date BETWEEN ? AND ? AND status != 'cancelled'
     GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
     ORDER BY month",
    [$company_id, $date_from, $date_to]
);

$monthlyPurchases = $db->fetchAll(
    "SELECT DATE_FORMAT(invoice_date, '%Y-%m') as month, SUM(total) as total
     FROM purchase_invoices 
     WHERE company_id = ? AND invoice_date BETWEEN ? AND ? AND status != 'cancelled'
     GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
     ORDER BY month",
    [$company_id, $date_from, $date_to]
);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="<?= $user['theme'] ?? 'dark' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= htmlspecialchars($company['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        @media print { 
            .sidebar, .header-actions, .no-print, .module-submenu, .sidebar-toggle { display: none !important; } 
            .main-content { margin: 0 !important; width: 100% !important; padding: 20px !important; } 
        }
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
        .profit { color: var(--success); font-weight: bold; }
        .loss { color: var(--danger); font-weight: bold; }
        .section-title { font-size: 1.1rem; font-weight: 600; padding: 12px 16px; background: var(--bg-hover); }
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
                           class="nav-link <?= $module['slug'] === 'reports' ? 'active' : '' ?>">
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
            <header class="header">
                <div class="header-title">
                    <h1><i class="fas fa-balance-scale"></i> <?= $pageTitle ?></h1>
                    <p>من <?= $date_from ?> إلى <?= $date_to ?></p>
                </div>
                <div class="header-actions no-print">
                    <button onclick="window.print()" class="btn btn-outline"><i class="fas fa-print"></i> طباعة</button>
                    <a href="index.php" class="btn btn-outline">عودة</a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu no-print">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item"><i class="fas fa-chart-pie"></i><span>لوحة التقارير</span></a>
                        <a href="profit_loss_report.php" class="submenu-item active"><i class="fas fa-balance-scale"></i><span>الأرباح والخسائر</span></a>
                    </div>
                </div>

                <!-- فلتر -->
                <div class="card mb-3 no-print">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-2 align-center">
                            <label>من:</label>
                            <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>" style="max-width: 160px;">
                            <label>إلى:</label>
                            <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>" style="max-width: 160px;">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i></button>
                        </form>
                    </div>
                </div>

                <!-- ملخص سريع -->
                <div class="row mb-3">
                    <div class="col-4">
                        <div class="stat-card">
                            <div class="stat-icon success"><i class="fas fa-arrow-up"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= number_format($revenue['net_sales'], 2) ?></div>
                                <div class="stat-label">صافي المبيعات</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-card">
                            <div class="stat-icon danger"><i class="fas fa-arrow-down"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= number_format($cogs['total_purchases'], 2) ?></div>
                                <div class="stat-label">تكلفة المشتريات</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-card">
                            <div class="stat-icon <?= $netProfit >= 0 ? 'success' : 'danger' ?>"><i class="fas fa-coins"></i></div>
                            <div class="stat-details">
                                <div class="stat-value <?= $netProfit >= 0 ? 'profit' : 'loss' ?>"><?= number_format($netProfit, 2) ?></div>
                                <div class="stat-label"><?= $netProfit >= 0 ? 'صافي الربح' : 'صافي الخسارة' ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <!-- تقرير الأرباح والخسائر -->
                        <div class="card">
                            <div class="card-header"><h3 class="card-title">قائمة الدخل</h3></div>
                            <div class="card-body p-0">
                                <table class="table">
                                    <tbody>
                                        <tr class="section-title"><td colspan="2"><i class="fas fa-plus-circle text-success"></i> الإيرادات</td></tr>
                                        <tr><td>إجمالي المبيعات</td><td><?= number_format($revenue['gross_sales'], 2) ?></td></tr>
                                        <tr><td>(-) الخصومات</td><td>(<?= number_format($revenue['discounts'], 2) ?>)</td></tr>
                                        <tr style="font-weight: bold;"><td>صافي المبيعات</td><td><?= number_format($revenue['net_sales'], 2) ?></td></tr>
                                        
                                        <tr class="section-title"><td colspan="2"><i class="fas fa-minus-circle text-danger"></i> التكاليف</td></tr>
                                        <tr><td>تكلفة المشتريات</td><td>(<?= number_format($cogs['total_purchases'], 2) ?>)</td></tr>
                                        <tr style="font-weight: bold;"><td>مجمل الربح</td><td class="<?= $grossProfit >= 0 ? 'profit' : 'loss' ?>"><?= number_format($grossProfit, 2) ?></td></tr>
                                        
                                        <tr class="section-title"><td colspan="2"><i class="fas fa-file-invoice text-warning"></i> المصروفات</td></tr>
                                        <tr><td>إجمالي المصروفات</td><td>(<?= number_format($expenses['total_expenses'] ?? 0, 2) ?>)</td></tr>
                                        
                                        <tr style="font-weight: bold; font-size: 1.1rem; background: var(--bg-hover);">
                                            <td><?= $netProfit >= 0 ? 'صافي الربح' : 'صافي الخسارة' ?></td>
                                            <td class="<?= $netProfit >= 0 ? 'profit' : 'loss' ?>"><?= number_format(abs($netProfit), 2) ?> <?= $company['currency_symbol'] ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <!-- المبيعات والمشتريات الشهرية -->
                        <div class="card">
                            <div class="card-header"><h3 class="card-title">الحركة الشهرية</h3></div>
                            <div class="card-body p-0">
                                <table class="table">
                                    <thead><tr><th>الشهر</th><th>المبيعات</th><th>المشتريات</th><th>الفرق</th></tr></thead>
                                    <tbody>
                                        <?php 
                                        $salesByMonth = array_column($monthlySales, 'total', 'month');
                                        $purchasesByMonth = array_column($monthlyPurchases, 'total', 'month');
                                        $allMonths = array_unique(array_merge(array_keys($salesByMonth), array_keys($purchasesByMonth)));
                                        sort($allMonths);
                                        
                                        if (empty($allMonths)): ?>
                                        <tr><td colspan="4" class="text-center text-muted">لا توجد بيانات</td></tr>
                                        <?php else: ?>
                                        <?php foreach ($allMonths as $month): 
                                            $s = $salesByMonth[$month] ?? 0;
                                            $p = $purchasesByMonth[$month] ?? 0;
                                            $diff = $s - $p;
                                        ?>
                                        <tr>
                                            <td><?= $month ?></td>
                                            <td><?= number_format($s, 2) ?></td>
                                            <td><?= number_format($p, 2) ?></td>
                                            <td class="<?= $diff >= 0 ? 'profit' : 'loss' ?>"><?= number_format($diff, 2) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
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
