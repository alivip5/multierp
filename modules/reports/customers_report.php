<?php
/**
 * تقرير العملاء والذمم
 * Reports Module - Customers Report
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

$pageTitle = 'تقرير العملاء والذمم';

// فلترة
$customer_id = $_GET['customer_id'] ?? '';
$status = $_GET['status'] ?? '';

// إحصائيات إجمالية
$stats = $db->fetch(
    "SELECT 
        COUNT(*) as total_customers,
        COALESCE(SUM(balance), 0) as total_balance,
        SUM(CASE WHEN balance > 0 THEN 1 ELSE 0 END) as customers_with_balance
     FROM customers 
     WHERE company_id = ?",
    [$company_id]
);

// ملخص الذمم
$receivables = $db->fetch(
    "SELECT 
        COALESCE(SUM(total - paid_amount), 0) as total_due,
        COUNT(CASE WHEN payment_status = 'unpaid' THEN 1 END) as unpaid_count,
        COUNT(CASE WHEN payment_status = 'partial' THEN 1 END) as partial_count
     FROM sales_invoices 
     WHERE company_id = ? AND payment_status != 'paid'",
    [$company_id]
);

// قائمة العملاء مع الأرصدة
$where = "c.company_id = ?";
$params = [$company_id];

if ($customer_id) {
    $where .= " AND c.id = ?";
    $params[] = (int)$customer_id;
}

$customers = $db->fetchAll(
    "SELECT c.*,
            (SELECT COUNT(*) FROM sales_invoices WHERE customer_id = c.id) as invoice_count,
            (SELECT COALESCE(SUM(total), 0) FROM sales_invoices WHERE customer_id = c.id) as total_purchases,
            (SELECT COALESCE(SUM(total - paid_amount), 0) FROM sales_invoices WHERE customer_id = c.id AND payment_status != 'paid') as due_amount
     FROM customers c
     WHERE $where
     ORDER BY due_amount DESC",
    $params
);

// قائمة العملاء للفلتر
$allCustomers = $db->fetchAll("SELECT id, name FROM customers WHERE company_id = ? ORDER BY name", [$company_id]);
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
        }
        .due-amount { color: var(--danger); font-weight: bold; }
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
                    <h1><i class="fas fa-users"></i> <?= $pageTitle ?></h1>
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
                        <a href="customers_report.php" class="submenu-item active"><i class="fas fa-users"></i><span>العملاء</span></a>
                        <a href="customer_debt_report.php" class="submenu-item"><i class="fas fa-file-invoice-dollar"></i><span>المديونية</span></a>
                    </div>
                </div>

                <!-- إحصائيات -->
                <div class="row mb-3">
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon primary"><i class="fas fa-users"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= $stats['total_customers'] ?></div>
                                <div class="stat-label">إجمالي العملاء</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon warning"><i class="fas fa-file-invoice-dollar"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= $stats['customers_with_balance'] ?></div>
                                <div class="stat-label">عملاء بمديونية</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon danger"><i class="fas fa-hand-holding-usd"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= number_format($stats['total_balance'], 2) ?></div>
                                <div class="stat-label">إجمالي رصيد العملاء</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon info"><i class="fas fa-receipt"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= number_format($receivables['total_due'], 2) ?></div>
                                <div class="stat-label">مستحقات من الفواتير</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- فلتر -->
                <div class="card mb-3 no-print">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-2 align-center">
                            <select name="customer_id" class="form-control" style="max-width: 300px;">
                                <option value="">جميع العملاء</option>
                                <?php foreach ($allCustomers as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $customer_id == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> تصفية</button>
                            <?php if ($customer_id): ?>
                            <a href="customers_report.php" class="btn btn-outline">إلغاء</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- جدول العملاء -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-between align-center">
                            <h3 class="card-title">قائمة العملاء وأرصدتهم</h3>
                            <a href="customer_debt_report.php" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-exclamation-circle"></i> تقرير أعمار الديون
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>العميل</th>
                                        <th>رقم الهاتف</th>
                                        <th>المشتريات</th>
                                        <th>الفواتير</th>
                                        <th>المستحق</th>
                                        <th>الرصيد الكلي</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($customers)): ?>
                                    <tr><td colspan="7" class="text-center text-muted p-3">لا توجد بيانات</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($customers as $c): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($c['name']) ?></strong>
                                            <?php if ($c['credit_limit'] > 0 && $c['balance'] > $c['credit_limit']): ?>
                                            <span class="badget badge-danger" title="تجاوز الحد الائتماني"><i class="fas fa-exclamation"></i></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($c['phone'] ?? '-') ?></td>
                                        <td><?= number_format($c['total_purchases'], 2) ?></td>
                                        <td><?= $c['invoice_count'] ?></td>
                                        <td><?= number_format($c['due_amount'], 2) ?></td>
                                        <td class="<?= $c['balance'] > 0 ? 'due-amount' : '' ?>"><?= number_format($c['balance'], 2) ?></td>
                                        <td>
                                            <?php if ($c['balance'] > 0): ?>
                                            <span class="badge badge-warning">مدين</span>
                                            <?php elseif ($c['balance'] < 0): ?>
                                            <span class="badge badge-success">دائن</span>
                                            <?php else: ?>
                                            <span class="badge badge-light">خالص</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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
</html>                    <div class="nav-item"><a href="index.php" class="nav-link active"><i class="fas fa-chart-bar"></i><span>التقارير</span></a></div>
                </div>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h1><i class="fas fa-users"></i> <?= $pageTitle ?></h1>
                </div>
                <div class="header-actions no-print">
                    <button onclick="window.print()" class="btn btn-outline"><i class="fas fa-print"></i> طباعة</button>
                    <a href="index.php" class="btn btn-outline">عودة</a>
                </div>
            </header>

            <div class="page-content">
                <!-- فلتر -->
                <div class="card mb-3 no-print">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-2 align-center flex-wrap">
                            <select name="customer_id" class="form-control" style="max-width: 250px;">
                                <option value="">جميع العملاء</option>
                                <?php foreach ($allCustomers as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $customer_id == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> تطبيق</button>
                        </form>
                    </div>
                </div>

                <!-- إحصائيات -->
                <div class="row mb-3">
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon primary"><i class="fas fa-users"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= $stats['total_customers'] ?></div>
                                <div class="stat-label">إجمالي العملاء</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon danger"><i class="fas fa-money-bill-wave"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= number_format($receivables['total_due'], 2) ?></div>
                                <div class="stat-label">إجمالي الذمم</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon warning"><i class="fas fa-file-invoice"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= $receivables['unpaid_count'] ?></div>
                                <div class="stat-label">فواتير غير مدفوعة</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon info"><i class="fas fa-clock"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= $receivables['partial_count'] ?></div>
                                <div class="stat-label">فواتير جزئية</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- قائمة العملاء -->
                <div class="card">
                    <div class="card-header"><h3 class="card-title">تفاصيل العملاء والذمم</h3></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>العميل</th>
                                        <th>الهاتف</th>
                                        <th>عدد الفواتير</th>
                                        <th>إجمالي المشتريات</th>
                                        <th>المبلغ المستحق</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($customers)): ?>
                                    <tr><td colspan="5" class="text-center text-muted p-3">لا توجد بيانات</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($customers as $c): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($c['name']) ?></td>
                                        <td><?= htmlspecialchars($c['phone'] ?? '-') ?></td>
                                        <td><?= $c['invoice_count'] ?></td>
                                        <td><?= number_format($c['total_purchases'], 2) ?> <?= $company['currency_symbol'] ?></td>
                                        <td class="<?= $c['due_amount'] > 0 ? 'due-amount' : '' ?>"><?= number_format($c['due_amount'], 2) ?> <?= $company['currency_symbol'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="font-weight: bold; background: var(--bg-surface);">
                                        <td colspan="4">الإجمالي</td>
                                        <td class="due-amount"><?= number_format($receivables['total_due'], 2) ?> <?= $company['currency_symbol'] ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
