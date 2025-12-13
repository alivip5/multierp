<?php
/**
 * صفحة إدارة المنتجات
 * Inventory Module - Products List
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/login.php');
    exit;
}

require_once __DIR__ . '/../../api/config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/Security.php';
require_once __DIR__ . '/../../includes/SidebarHelper.php';

$db = Database::getInstance();
$company_id = $_SESSION['company_id'] ?? 1;
$user = $db->fetch("SELECT u.*, r.name as role_slug, r.name_ar as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?", [$_SESSION['user_id']]);
$company = $db->fetch("SELECT * FROM companies WHERE id = ?", [$company_id]);

// التأكد من وجود بيانات الدور في الجلسة
if (!isset($_SESSION['role_id']) && $user) {
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_slug'];
}

// الموديولات للقائمة الجانبية
$enabledModules = getSidebarItems($company_id, $_SESSION['user_id']);

// التحقق من تفعيل الموديول
require_module($company_id, 'inventory');

$pageTitle = 'المنتجات';

// البحث والفلترة
$search = $_GET['search'] ?? '';
$where = "company_id = ?";
$params = [$company_id];

if ($search) {
    $where .= " AND (name LIKE ? OR code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$products = $db->fetchAll(
    "SELECT p.*, 
     COALESCE((SELECT SUM(quantity) FROM product_stock ps WHERE ps.product_id = p.id), 0) as current_stock
     FROM products p 
     WHERE p.$where 
     ORDER BY p.name ASC", 
    $params
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
        .low-stock { color: var(--danger); font-weight: bold; }
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
                           class="nav-link <?= $module['slug'] === 'inventory' ? 'active' : '' ?>">
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

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h1><i class="fas fa-box-open"></i> <?= $pageTitle ?></h1>
                    <p>إدارة المنتجات والأصناف</p>
                </div>
                <div class="header-actions">
                    <button class="menu-toggle-btn" onclick="toggleSidebar()" title="القائمة">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a href="product_form.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> منتج جديد
                    </a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item"><i class="fas fa-home"></i><span>الرئيسية</span></a>
                        <a href="products.php" class="submenu-item active"><i class="fas fa-box"></i><span>المنتجات</span></a>
                        <a href="opening_stock.php" class="submenu-item"><i class="fas fa-clipboard-list"></i><span>أرصدة أول المدة</span></a>
                        <a href="stock_transfers.php" class="submenu-item"><i class="fas fa-exchange-alt"></i><span>التحويلات</span></a>
                        <a href="low-stock.php" class="submenu-item"><i class="fas fa-exclamation-triangle"></i><span>منخفض المخزون</span></a>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control" placeholder="بحث عن منتج..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-outline"><i class="fas fa-search"></i></button>
                            <?php if ($search): ?>
                            <a href="products.php" class="btn btn-outline"><i class="fas fa-times"></i></a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>الكود</th>
                                        <th>الاسم</th>
                                        <th>سعر البيع</th>
                                        <th>سعر الشراء</th>
                                        <th>المخزون</th>
                                        <th>الحالة</th>
                                        <th>إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center p-3 text-muted">لا توجد منتجات</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($products as $p): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($p['code'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($p['name']) ?></td>
                                        <td><?= number_format($p['selling_price'], 2) ?></td>
                                        <td><?= number_format($p['purchase_price'], 2) ?></td>
                                        <td class="<?= ($p['track_inventory'] && $p['current_stock'] <= $p['min_stock']) ? 'low-stock' : '' ?>">
                                            <?= $p['track_inventory'] ? number_format($p['current_stock'], 2) : '-' ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= $p['is_active'] ? 'success' : 'danger' ?>">
                                                <?= $p['is_active'] ? 'نشط' : 'غير نشط' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="product_form.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
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
