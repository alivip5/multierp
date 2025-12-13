<?php
/**
 * تقرير مديونية العملاء
 * Customer Debt Report with Aging
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

$pageTitle = 'تقرير مديونية العملاء';

// جلب بيانات العملاء مع المديونية
$customers = $db->fetchAll("SELECT * FROM customers WHERE company_id = ? ORDER BY balance DESC", [$company_id]);

// تجميع التقرير
$report = [];
$totalDebt = 0;
$totalOverCredit = 0;

foreach ($customers as $customer) {
    // جلب آخر فاتورة غير مسددة
    $lastUnpaidInvoice = $db->fetch(
        "SELECT * FROM sales_invoices 
         WHERE customer_id = ? 
           AND payment_status IN ('unpaid', 'partial')
         ORDER BY invoice_date ASC 
         LIMIT 1",
        [$customer['id']]
    );
    
    // جلب عدد الفواتير غير المسددة
    $unpaidCount = $db->fetch(
        "SELECT COUNT(*) as count, SUM(total - paid_amount) as total_due 
         FROM sales_invoices 
         WHERE customer_id = ? 
           AND payment_status IN ('unpaid', 'partial')",
        [$customer['id']]
    );
    
    // حساب عمر الدين
    $debtAge = 0;
    $oldestInvoiceDate = null;
    if ($lastUnpaidInvoice) {
        $oldestInvoiceDate = $lastUnpaidInvoice['invoice_date'];
        $invoiceDate = new DateTime($oldestInvoiceDate);
        $today = new DateTime();
        $debtAge = $today->diff($invoiceDate)->days;
    }
    
    // تحديد فئة عمر الدين
    $ageCategory = 'current';
    $ageCategoryLabel = 'حالي';
    if ($debtAge > 90) {
        $ageCategory = 'critical';
        $ageCategoryLabel = 'متأخر جداً';
    } elseif ($debtAge > 60) {
        $ageCategory = 'delayed';
        $ageCategoryLabel = 'متأخر';
    } elseif ($debtAge > 30) {
        $ageCategory = 'warning';
        $ageCategoryLabel = 'منتبه';
    }
    
    // التحقق من تجاوز الحد الائتماني
    $overCreditLimit = false;
    if ($customer['credit_limit'] > 0 && $customer['balance'] > $customer['credit_limit']) {
        $overCreditLimit = true;
        $totalOverCredit++;
    }
    
    $balance = (float)$customer['balance'];
    $totalDebt += $balance;
    
    $report[] = [
        'customer' => $customer,
        'unpaid_count' => $unpaidCount['count'] ?? 0,
        'total_due' => $unpaidCount['total_due'] ?? 0,
        'last_unpaid_date' => $oldestInvoiceDate,
        'debt_age' => $debtAge,
        'age_category' => $ageCategory,
        'age_category_label' => $ageCategoryLabel,
        'over_credit_limit' => $overCreditLimit
    ];
}

// الإحصائيات
$stats = [
    'total_customers' => count($customers),
    'customers_with_debt' => count(array_filter($report, fn($r) => $r['customer']['balance'] > 0)),
    'total_debt' => $totalDebt,
    'over_credit_count' => $totalOverCredit,
    'critical_count' => count(array_filter($report, fn($r) => $r['age_category'] === 'critical')),
    'delayed_count' => count(array_filter($report, fn($r) => $r['age_category'] === 'delayed'))
];
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
        .age-current { color: var(--success); }
        .age-warning { color: var(--warning); }
        .age-delayed { color: #ff9800; }
        .age-critical { color: var(--danger); font-weight: bold; }
        .over-credit { background: rgba(var(--danger-rgb), 0.1); }
        @media print {
            .sidebar, .header-actions, .no-print, .module-submenu, .sidebar-toggle { display: none !important; }
            .main-content { margin: 0 !important; width: 100% !important; padding: 20px !important; }
            .card { box-shadow: none; border: 1px solid #ddd; }
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
                    <h1><i class="fas fa-file-invoice-dollar"></i> <?= $pageTitle ?></h1>
                    <p>تاريخ التقرير: <?= date('Y-m-d H:i') ?></p>
                </div>
                <div class="header-actions no-print">
                    <button onclick="window.print()" class="btn btn-outline"><i class="fas fa-print"></i> طباعة</button>
                    <a href="customers_report.php" class="btn btn-outline">عودة</a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu no-print">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item"><i class="fas fa-chart-pie"></i><span>لوحة التقارير</span></a>
                        <a href="customers_report.php" class="submenu-item"><i class="fas fa-users"></i><span>العملاء</span></a>
                        <a href="customer_debt_report.php" class="submenu-item active"><i class="fas fa-file-invoice-dollar"></i><span>المديونية</span></a>
                    </div>
                </div>

                <!-- الإحصائيات -->
                <div class="row mb-3">
                    <div class="col-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <div style="font-size: 2em; color: var(--primary);"><?= $stats['total_customers'] ?></div>
                                <div>إجمالي العملاء</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <div style="font-size: 2em; color: var(--warning);"><?= $stats['customers_with_debt'] ?></div>
                                <div>عملاء بمديونية</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <div style="font-size: 2em; color: var(--danger);"><?= number_format($stats['total_debt'], 2) ?></div>
                                <div>إجمالي المديونية</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <div style="font-size: 2em; color: var(--danger);"><?= $stats['over_credit_count'] ?></div>
                                <div>متجاوزين الحد الائتماني</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- تصنيف عمر الدين -->
                <div class="row mb-3">
                    <div class="col-4">
                        <div class="card">
                            <div class="card-body d-flex justify-between align-center">
                                <span><i class="fas fa-clock age-warning"></i> متأخر (30-60 يوم)</span>
                                <strong><?= count(array_filter($report, fn($r) => $r['age_category'] === 'warning')) ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card">
                            <div class="card-body d-flex justify-between align-center">
                                <span><i class="fas fa-exclamation-triangle age-delayed"></i> متأخر (60-90 يوم)</span>
                                <strong><?= $stats['delayed_count'] ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card">
                            <div class="card-body d-flex justify-between align-center">
                                <span><i class="fas fa-times-circle age-critical"></i> متأخر جداً (+90 يوم)</span>
                                <strong><?= $stats['critical_count'] ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- الجدول الرئيسي -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list"></i> تفاصيل المديونية</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>العميل</th>
                                    <th>الهاتف</th>
                                    <th>الرصيد الحالي</th>
                                    <th>الحد الائتماني</th>
                                    <th>فواتير غير مسددة</th>
                                    <th>آخر فاتورة غير مسددة</th>
                                    <th>عمر الدين (يوم)</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report as $row): 
                                    if ($row['customer']['balance'] <= 0) continue;
                                ?>
                                <tr class="<?= $row['over_credit_limit'] ? 'over-credit' : '' ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($row['customer']['name']) ?></strong>
                                        <?php if ($row['over_credit_limit']): ?>
                                        <span class="badge badge-danger">متجاوز</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['customer']['phone'] ?? '-') ?></td>
                                    <td><strong style="color: var(--danger);"><?= number_format($row['customer']['balance'], 2) ?></strong></td>
                                    <td>
                                        <?php if ($row['customer']['credit_limit'] > 0): ?>
                                        <?= number_format($row['customer']['credit_limit'], 2) ?>
                                        <?php else: ?>
                                        <span class="text-muted">غير محدد</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $row['unpaid_count'] ?></td>
                                    <td><?= $row['last_unpaid_date'] ?? '-' ?></td>
                                    <td class="age-<?= $row['age_category'] ?>">
                                        <strong><?= $row['debt_age'] ?></strong> يوم
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= 
                                            $row['age_category'] === 'critical' ? 'danger' : 
                                            ($row['age_category'] === 'delayed' ? 'warning' : 
                                            ($row['age_category'] === 'warning' ? 'info' : 'success')) 
                                        ?>">
                                            <?= $row['age_category_label'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
