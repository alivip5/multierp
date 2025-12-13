<?php
/**
 * تقرير المشتريات
 * Reports Module - Purchases Report
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

$pageTitle = 'تقرير المشتريات';

// فترة التقرير
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$supplier_id = $_GET['supplier_id'] ?? '';

// إحصائيات المشتريات
$stats = $db->fetch(
    "SELECT 
        COUNT(*) as total_invoices,
        COALESCE(SUM(total), 0) as total_purchases,
        COALESCE(SUM(paid_amount), 0) as total_paid,
        COALESCE(SUM(total - paid_amount), 0) as total_due
     FROM purchase_invoices 
     WHERE company_id = ? AND invoice_date BETWEEN ? AND ?",
    [$company_id, $date_from, $date_to]
);

// المشتريات حسب المورد
$bySupplier = $db->fetchAll(
    "SELECT s.name as supplier_name, COUNT(pi.id) as invoice_count, 
            SUM(pi.total) as total, SUM(pi.total - pi.paid_amount) as due
     FROM purchase_invoices pi
     LEFT JOIN suppliers s ON pi.supplier_id = s.id
     WHERE pi.company_id = ? AND pi.invoice_date BETWEEN ? AND ?
     GROUP BY pi.supplier_id
     ORDER BY total DESC",
    [$company_id, $date_from, $date_to]
);

// أكثر المنتجات شراءً
$topProducts = $db->fetchAll(
    "SELECT p.name, SUM(pii.quantity) as qty, SUM(pii.total) as total
     FROM purchase_invoice_items pii
     JOIN purchase_invoices pi ON pii.invoice_id = pi.id
     LEFT JOIN products p ON pii.product_id = p.id
     WHERE pi.company_id = ? AND pi.invoice_date BETWEEN ? AND ?
     GROUP BY pii.product_id
     ORDER BY total DESC
     LIMIT 10",
    [$company_id, $date_from, $date_to]
);

// قائمة الموردين
$suppliers = $db->fetchAll("SELECT id, name FROM suppliers WHERE company_id = ? ORDER BY name", [$company_id]);
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
        @media print {
            .sidebar, .header-actions, .no-print, .module-submenu, .sidebar-toggle { display: none !important; }
            .main-content { margin: 0 !important; }
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
                    <h1><i class="fas fa-truck"></i> <?= $pageTitle ?></h1>
                    <p>من <?= $date_from ?> إلى <?= $date_to ?></p>
                </div>
                <div class="header-actions no-print">
                    <button onclick="window.print()" class="btn btn-outline"><i class="fas fa-print"></i></button>
                    <a href="index.php" class="btn btn-outline">عودة</a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu no-print">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item"><i class="fas fa-chart-pie"></i><span>لوحة التقارير</span></a>
                        <a href="sales_report.php" class="submenu-item"><i class="fas fa-chart-line"></i><span>المبيعات</span></a>
                        <a href="purchases_report.php" class="submenu-item active"><i class="fas fa-shopping-bag"></i><span>المشتريات</span></a>
                        <a href="inventory_report.php" class="submenu-item"><i class="fas fa-boxes"></i><span>المخزون</span></a>
                    </div>
                </div>

                <!-- فلتر -->
                <div class="card mb-3 no-print">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-2 align-center flex-wrap">
                            <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>" style="max-width: 160px;">
                            <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>" style="max-width: 160px;">
                            <select name="supplier_id" class="form-control" style="max-width: 200px;">
                                <option value="">جميع الموردين</option>
                                <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $supplier_id == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i></button>
                        </form>
                    </div>
                </div>

                <!-- إحصائيات -->
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
                            <div class="stat-icon info"><i class="fas fa-coins"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= number_format($stats['total_purchases'], 2) ?></div>
                                <div class="stat-label">إجمالي المشتريات</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon success"><i class="fas fa-check"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= number_format($stats['total_paid'], 2) ?></div>
                                <div class="stat-label">المدفوع</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon danger"><i class="fas fa-clock"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= number_format($stats['total_due'], 2) ?></div>
                                <div class="stat-label">المستحق</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <div class="card mb-3">
                            <div class="card-header"><h3 class="card-title">المشتريات حسب المورد</h3></div>
                            <div class="card-body p-0">
                                <table class="table">
                                    <thead><tr><th>المورد</th><th>الفواتير</th><th>الإجمالي</th><th>المستحق</th></tr></thead>
                                    <tbody>
                                        <?php if (empty($bySupplier)): ?>
                                        <tr><td colspan="4" class="text-center text-muted">لا توجد بيانات</td></tr>
                                        <?php else: ?>
                                        <?php foreach ($bySupplier as $s): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($s['supplier_name'] ?? 'غير محدد') ?></td>
                                            <td><?= $s['invoice_count'] ?></td>
                                            <td><?= number_format($s['total'], 2) ?></td>
                                            <td style="color: var(--danger);"><?= number_format($s['due'], 2) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card mb-3">
                            <div class="card-header"><h3 class="card-title">أكثر المنتجات شراءً</h3></div>
                            <div class="card-body p-0">
                                <table class="table">
                                    <thead><tr><th>المنتج</th><th>الكمية</th><th>الإجمالي</th></tr></thead>
                                    <tbody>
                                        <?php if (empty($topProducts)): ?>
                                        <tr><td colspan="3" class="text-center text-muted">لا توجد بيانات</td></tr>
                                        <?php else: ?>
                                        <?php foreach ($topProducts as $p): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($p['name'] ?? 'غير محدد') ?></td>
                                            <td><?= number_format($p['qty']) ?></td>
                                            <td><?= number_format($p['total'], 2) ?></td>
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
