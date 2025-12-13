<?php
/**
 * تقرير مبيعات مندوب التعاقد
 * Sales Agent Report
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

$pageTitle = 'تقرير مبيعات مندوبي التعاقد';

// الفلاتر
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$selectedAgent = $_GET['agent_id'] ?? '';

// جلب مندوبي التعاقد
$agents = [];
try {
    $agents = $db->fetchAll("SELECT * FROM sales_agents WHERE company_id = ? ORDER BY name", [$company_id]);
} catch (Exception $e) {}

// بناء الاستعلام
$where = "si.company_id = ? AND si.invoice_date BETWEEN ? AND ?";
$params = [$company_id, $dateFrom, $dateTo];

if ($selectedAgent) {
    $where .= " AND si.sales_agent_id = ?";
    $params[] = (int)$selectedAgent;
}

// جلب البيانات
$report = [];
$totalSales = 0;
$totalInvoices = 0;

try {
    // ملخص حسب المندوب
    $agentSummary = $db->fetchAll(
        "SELECT 
            sa.id, sa.name, sa.commission_rate,
            COUNT(si.id) as invoice_count,
            SUM(si.total) as total_sales,
            SUM(si.paid_amount) as total_collected,
            SUM(si.total - si.paid_amount) as total_due
         FROM sales_agents sa
         LEFT JOIN sales_invoices si ON si.sales_agent_id = sa.id 
            AND si.invoice_date BETWEEN ? AND ?
            AND si.company_id = ?
         WHERE sa.company_id = ?
         GROUP BY sa.id
         ORDER BY total_sales DESC",
        [$dateFrom, $dateTo, $company_id, $company_id]
    );
    
    // تفاصيل الفواتير
    $invoices = $db->fetchAll(
        "SELECT si.*, c.name as customer_name, sa.name as agent_name
         FROM sales_invoices si
         LEFT JOIN customers c ON si.customer_id = c.id
         LEFT JOIN sales_agents sa ON si.sales_agent_id = sa.id
         WHERE $where AND si.sales_agent_id IS NOT NULL
         ORDER BY si.invoice_date DESC",
        $params
    );
    
    foreach ($invoices as $inv) {
        $totalSales += (float)$inv['total'];
        $totalInvoices++;
    }
    
} catch (Exception $e) {
    $agentSummary = [];
    $invoices = [];
}
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
                    <h1><i class="fas fa-user-tie"></i> <?= $pageTitle ?></h1>
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
                        <a href="sales_report.php" class="submenu-item"><i class="fas fa-chart-line"></i><span>المبيعات</span></a>
                        <a href="sales_agent_report.php" class="submenu-item active"><i class="fas fa-user-tie"></i><span>المندوبين</span></a>
                    </div>
                </div>

                <!-- الفلاتر -->
                <div class="card mb-3 no-print">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-2 align-center flex-wrap">
                            <div class="form-group">
                                <label>من تاريخ</label>
                                <input type="date" name="date_from" class="form-control" value="<?= $dateFrom ?>">
                            </div>
                            <div class="form-group">
                                <label>إلى تاريخ</label>
                                <input type="date" name="date_to" class="form-control" value="<?= $dateTo ?>">
                            </div>
                            <div class="form-group">
                                <label>المندوب</label>
                                <select name="agent_id" class="form-control">
                                    <option value="">الكل</option>
                                    <?php foreach ($agents as $agent): ?>
                                    <option value="<?= $agent['id'] ?>" <?= $selectedAgent == $agent['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($agent['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" style="margin-top: 22px;">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> بحث</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- معلومات الفترة -->
                <div class="card mb-3">
                    <div class="card-body text-center">
                        <h4>الفترة: <?= $dateFrom ?> إلى <?= $dateTo ?></h4>
                    </div>
                </div>
                
                <!-- الإحصائيات -->
                <div class="row mb-3">
                    <div class="col-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <div style="font-size: 2em; color: var(--primary);"><?= count($agents) ?></div>
                                <div>عدد المندوبين</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <div style="font-size: 2em; color: var(--success);"><?= $totalInvoices ?></div>
                                <div>عدد الفواتير</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <div style="font-size: 2em; color: var(--warning);"><?= number_format($totalSales, 2) ?></div>
                                <div>إجمالي المبيعات</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ملخص المندوبين -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-bar"></i> ملخص حسب المندوب</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>المندوب</th>
                                    <th>عدد الفواتير</th>
                                    <th>إجمالي المبيعات</th>
                                    <th>المحصل</th>
                                    <th>المتبقي</th>
                                    <th>نسبة العمولة</th>
                                    <th>العمولة المستحقة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($agentSummary)): ?>
                                <tr><td colspan="7" class="text-center text-muted p-3">لا توجد بيانات</td></tr>
                                <?php else: ?>
                                <?php foreach ($agentSummary as $row): 
                                    $commission = ($row['total_sales'] ?? 0) * ($row['commission_rate'] ?? 0) / 100;
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                                    <td><?= $row['invoice_count'] ?? 0 ?></td>
                                    <td><?= number_format($row['total_sales'] ?? 0, 2) ?></td>
                                    <td style="color: var(--success);"><?= number_format($row['total_collected'] ?? 0, 2) ?></td>
                                    <td style="color: var(--danger);"><?= number_format($row['total_due'] ?? 0, 2) ?></td>
                                    <td><?= $row['commission_rate'] ?>%</td>
                                    <td><strong><?= number_format($commission, 2) ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- تفاصيل الفواتير -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list"></i> تفاصيل الفواتير</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>رقم الفاتورة</th>
                                    <th>التاريخ</th>
                                    <th>العميل</th>
                                    <th>المندوب</th>
                                    <th>الإجمالي</th>
                                    <th>المدفوع</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($invoices)): ?>
                                <tr><td colspan="7" class="text-center text-muted p-3">لا توجد فواتير</td></tr>
                                <?php else: ?>
                                <?php foreach ($invoices as $inv): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($inv['invoice_number']) ?></strong></td>
                                    <td><?= $inv['invoice_date'] ?></td>
                                    <td><?= htmlspecialchars($inv['customer_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($inv['agent_name'] ?? '-') ?></td>
                                    <td><?= number_format($inv['total'], 2) ?></td>
                                    <td><?= number_format($inv['paid_amount'], 2) ?></td>
                                    <td>
                                        <?php
                                        $statusLabels = ['unpaid' => 'غير مدفوعة', 'partial' => 'جزئي', 'paid' => 'مدفوعة'];
                                        $statusColors = ['unpaid' => 'danger', 'partial' => 'warning', 'paid' => 'success'];
                                        $status = $inv['payment_status'] ?? 'unpaid';
                                        ?>
                                        <span class="badge badge-<?= $statusColors[$status] ?? 'secondary' ?>">
                                            <?= $statusLabels[$status] ?? $status ?>
                                        </span>
                                    </td>
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
