<?php
/**
 * تقرير المبيعات
 * Reports Module - Sales Report
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

$pageTitle = 'تقرير المبيعات';

// فترة التقرير
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// إحصائيات المبيعات
$stats = $db->fetch(
    "SELECT 
        COUNT(*) as total_invoices,
        COALESCE(SUM(total), 0) as total_sales,
        COALESCE(SUM(paid_amount), 0) as total_paid,
        COALESCE(SUM(total - paid_amount), 0) as total_due,
        COALESCE(SUM(tax_amount), 0) as total_tax
     FROM sales_invoices 
     WHERE company_id = ? AND invoice_date BETWEEN ? AND ?",
    [$company_id, $date_from, $date_to]
);

// المبيعات اليومية
$dailySales = $db->fetchAll(
    "SELECT DATE(invoice_date) as date, COUNT(*) as count, SUM(total) as total
     FROM sales_invoices 
     WHERE company_id = ? AND invoice_date BETWEEN ? AND ?
     GROUP BY DATE(invoice_date)
     ORDER BY date ASC",
    [$company_id, $date_from, $date_to]
);

// أفضل المنتجات مبيعاً
$topProducts = $db->fetchAll(
    "SELECT p.name, SUM(sii.quantity) as qty, SUM(sii.total) as total
     FROM sales_invoice_items sii
     JOIN sales_invoices si ON sii.invoice_id = si.id
     LEFT JOIN products p ON sii.product_id = p.id
     WHERE si.company_id = ? AND si.invoice_date BETWEEN ? AND ?
     GROUP BY sii.product_id
     ORDER BY total DESC
     LIMIT 10",
    [$company_id, $date_from, $date_to]
);

// أفضل العملاء
$topCustomers = $db->fetchAll(
    "SELECT c.name, COUNT(si.id) as invoices, SUM(si.total) as total
     FROM sales_invoices si
     LEFT JOIN customers c ON si.customer_id = c.id
     WHERE si.company_id = ? AND si.invoice_date BETWEEN ? AND ?
     GROUP BY si.customer_id
     ORDER BY total DESC
     LIMIT 10",
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
            .main-content { margin: 0 !important; padding: 20px !important; }
            .card { box-shadow: none !important; border: 1px solid #ddd !important; }
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
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
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
                    <h1><i class="fas fa-shopping-cart"></i> <?= $pageTitle ?></h1>
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
                        <a href="sales_report.php" class="submenu-item active"><i class="fas fa-chart-line"></i><span>المبيعات</span></a>
                        <a href="purchases_report.php" class="submenu-item"><i class="fas fa-shopping-bag"></i><span>المشتريات</span></a>
                        <a href="inventory_report.php" class="submenu-item"><i class="fas fa-boxes"></i><span>المخزون</span></a>
                    </div>
                </div>

                <!-- فلتر التاريخ -->
                <div class="card mb-3 no-print">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-2 align-center flex-wrap">
                            <label>من:</label>
                            <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>" style="max-width: 180px;">
                            <label>إلى:</label>
                            <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>" style="max-width: 180px;">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> تطبيق</button>
                        </form>
                    </div>
                </div>

                <!-- ملخص الإحصائيات -->
                <div class="row mb-3">
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon primary"><i class="fas fa-file-invoice"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= $stats['total_invoices'] ?></div>
                                <div class="stat-label">عدد الفواتير</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon success"><i class="fas fa-coins"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= number_format($stats['total_sales'], 2) ?></div>
                                <div class="stat-label">إجمالي المبيعات</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon info"><i class="fas fa-hand-holding-usd"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= number_format($stats['total_paid'], 2) ?></div>
                                <div class="stat-label">المحصّل</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= number_format($stats['total_due'], 2) ?></div>
                                <div class="stat-label">المستحق</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- أفضل المنتجات -->
                    <div class="col-6">
                        <div class="card mb-3">
                            <div class="card-header"><h3 class="card-title">أفضل المنتجات مبيعاً</h3></div>
                            <div class="card-body p-0">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>المنتج</th>
                                            <th>الكمية</th>
                                            <th>الإجمالي</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($topProducts)): ?>
                                        <tr><td colspan="3" class="text-center text-muted p-3">لا توجد بيانات</td></tr>
                                        <?php else: ?>
                                        <?php foreach ($topProducts as $p): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($p['name'] ?? 'غير محدد') ?></td>
                                            <td><?= number_format($p['qty']) ?></td>
                                            <td><?= number_format($p['total'], 2) ?> <?= $company['currency_symbol'] ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- أفضل العملاء -->
                    <div class="col-6">
                        <div class="card mb-3">
                            <div class="card-header"><h3 class="card-title">أفضل العملاء</h3></div>
                            <div class="card-body p-0">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>العميل</th>
                                            <th>الفواتير</th>
                                            <th>الإجمالي</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($topCustomers)): ?>
                                        <tr><td colspan="3" class="text-center text-muted p-3">لا توجد بيانات</td></tr>
                                        <?php else: ?>
                                        <?php foreach ($topCustomers as $c): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($c['name'] ?? 'عميل نقدي') ?></td>
                                            <td><?= $c['invoices'] ?></td>
                                            <td><?= number_format($c['total'], 2) ?> <?= $company['currency_symbol'] ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- المبيعات اليومية -->
                <div class="card">
                    <div class="card-header"><h3 class="card-title">المبيعات اليومية</h3></div>
                    <div class="card-body p-0">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>عدد الفواتير</th>
                                    <th>الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($dailySales)): ?>
                                <tr><td colspan="3" class="text-center text-muted p-3">لا توجد مبيعات في هذه الفترة</td></tr>
                                <?php else: ?>
                                <?php foreach ($dailySales as $day): ?>
                                <tr>
                                    <td><?= $day['date'] ?></td>
                                    <td><?= $day['count'] ?></td>
                                    <td><?= number_format($day['total'], 2) ?> <?= $company['currency_symbol'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
    </script>
</body>
</html>
