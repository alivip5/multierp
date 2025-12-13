<?php
/**
 * تقرير المخزون
 * Reports Module - Inventory Report
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

$pageTitle = 'تقرير المخزون';

// إحصائيات المخزون
$stats = $db->fetch(
    "SELECT 
        COUNT(*) as total_products,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_products,
        COALESCE(SUM(ps.quantity), 0) as total_stock
     FROM products p
     LEFT JOIN product_stock ps ON p.id = ps.product_id
     WHERE p.company_id = ?",
    [$company_id]
);

// قيمة المخزون
$stockValue = $db->fetch(
    "SELECT COALESCE(SUM(ps.quantity * p.purchase_price), 0) as cost_value,
            COALESCE(SUM(ps.quantity * p.selling_price), 0) as sell_value
     FROM products p
     JOIN product_stock ps ON p.id = ps.product_id
     WHERE p.company_id = ?",
    [$company_id]
);

// المنتجات منخفضة المخزون
$lowStock = $db->fetchAll(
    "SELECT p.*, c.name as category_name,
            COALESCE((SELECT SUM(quantity) FROM product_stock WHERE product_id = p.id), 0) as current_stock
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     WHERE p.company_id = ? AND p.track_inventory = 1 AND p.is_active = 1
     HAVING current_stock <= p.min_stock
     ORDER BY current_stock ASC
     LIMIT 20",
    [$company_id]
);

// جميع المنتجات مع المخزون
$filter = $_GET['filter'] ?? '';
$where = "p.company_id = ?";
$params = [$company_id];

if ($filter === 'low') {
    // سيتم الفلترة بعد الجلب
}

$allProducts = $db->fetchAll(
    "SELECT p.*, c.name as category_name,
            COALESCE((SELECT SUM(quantity) FROM product_stock WHERE product_id = p.id), 0) as current_stock
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     WHERE $where AND p.is_active = 1
     ORDER BY p.name ASC",
    $params
);

if ($filter === 'low') {
    $allProducts = array_filter($allProducts, fn($p) => $p['track_inventory'] && $p['current_stock'] <= $p['min_stock']);
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
        .low-stock { color: var(--danger); font-weight: bold; }
        @media print {
            .sidebar, .header-actions, .no-print, .module-submenu, .sidebar-toggle { display: none !important; }
            .main-content { margin: 0 !important; padding: 20px !important; }
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
                    <h1><i class="fas fa-boxes"></i> <?= $pageTitle ?></h1>
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
                        <a href="purchases_report.php" class="submenu-item"><i class="fas fa-shopping-bag"></i><span>المشتريات</span></a>
                        <a href="inventory_report.php" class="submenu-item active"><i class="fas fa-boxes"></i><span>المخزون</span></a>
                    </div>
                </div>

                <!-- ملخص الإحصائيات -->
                <div class="row mb-3">
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon primary"><i class="fas fa-box"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= $stats['total_products'] ?></div>
                                <div class="stat-label">إجمالي المنتجات</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= $stats['active_products'] ?></div>
                                <div class="stat-label">منتجات نشطة</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon info"><i class="fas fa-cubes"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= number_format($stats['total_stock']) ?></div>
                                <div class="stat-label">إجمالي الكميات</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon warning"><i class="fas fa-coins"></i></div>
                            <div class="stat-details">
                                <div class="stat-value"><?= number_format($stockValue['cost_value'], 0) ?></div>
                                <div class="stat-label">قيمة المخزون (تكلفة)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- فلتر -->
                <div class="card mb-3 no-print">
                    <div class="card-body">
                        <div class="d-flex gap-2">
                            <a href="inventory_report.php" class="btn <?= !$filter ? 'btn-primary' : 'btn-outline' ?>">جميع المنتجات</a>
                            <a href="inventory_report.php?filter=low" class="btn <?= $filter === 'low' ? 'btn-primary' : 'btn-outline' ?>"><i class="fas fa-exclamation-triangle"></i> منخفضة المخزون</a>
                        </div>
                    </div>
                </div>

                <!-- المنتجات منخفضة المخزون -->
                <?php if (!$filter && !empty($lowStock)): ?>
                <div class="card mb-3">
                    <div class="card-header" style="background: rgba(239, 68, 68, 0.1);">
                        <h3 class="card-title" style="color: var(--danger);"><i class="fas fa-exclamation-triangle"></i> تنبيه: منتجات تحتاج إعادة طلب</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>المنتج</th>
                                    <th>الحد الأدنى</th>
                                    <th>المخزون الحالي</th>
                                    <th>العجز</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStock as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['name']) ?></td>
                                    <td><?= number_format($p['min_stock']) ?></td>
                                    <td class="low-stock"><?= number_format($p['current_stock']) ?></td>
                                    <td class="low-stock"><?= number_format($p['min_stock'] - $p['current_stock']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- قائمة المنتجات -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?= $filter === 'low' ? 'المنتجات منخفضة المخزون' : 'جميع المنتجات' ?></h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>الكود</th>
                                        <th>المنتج</th>
                                        <th>الفئة</th>
                                        <th>سعر الشراء</th>
                                        <th>سعر البيع</th>
                                        <th>المخزون</th>
                                        <th>القيمة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($allProducts)): ?>
                                    <tr><td colspan="7" class="text-center text-muted p-3">لا توجد منتجات</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($allProducts as $p): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($p['code'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($p['name']) ?></td>
                                        <td><?= htmlspecialchars($p['category_name'] ?? '-') ?></td>
                                        <td><?= number_format($p['purchase_price'], 2) ?></td>
                                        <td><?= number_format($p['selling_price'], 2) ?></td>
                                        <td class="<?= ($p['track_inventory'] && $p['current_stock'] <= $p['min_stock']) ? 'low-stock' : '' ?>">
                                            <?= $p['track_inventory'] ? number_format($p['current_stock']) : '-' ?>
                                        </td>
                                        <td><?= number_format($p['current_stock'] * $p['purchase_price'], 2) ?></td>
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
