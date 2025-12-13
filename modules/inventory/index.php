<?php
/**
 * الصفحة الرئيسية للمخزون
 * Inventory Module - Main Page
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

$pageTitle = 'المخزون';

// إحصائيات المخزون
$stats = [
    'total_products' => $db->fetch("SELECT COUNT(*) as c FROM products WHERE company_id = ? AND is_active = 1", [$company_id])['c'] ?? 0,
    'low_stock' => $db->fetch("SELECT COUNT(*) as c FROM products p WHERE p.company_id = ? AND p.track_inventory = 1 AND (SELECT COALESCE(SUM(ps.quantity), 0) FROM product_stock ps WHERE ps.product_id = p.id) <= p.min_stock", [$company_id])['c'] ?? 0,
    'total_value' => $db->fetch("SELECT COALESCE(SUM(ps.quantity * p.purchase_price), 0) as v FROM product_stock ps JOIN products p ON ps.product_id = p.id WHERE p.company_id = ?", [$company_id])['v'] ?? 0,
    'warehouses' => $db->fetch("SELECT COUNT(*) as c FROM warehouses WHERE company_id = ? AND status = 'active'", [$company_id])['c'] ?? 0,
];

// آخر حركات المخزون
$recentMovements = [];
try {
    $recentMovements = $db->fetchAll(
        "SELECT im.*, p.name as product_name, w.name as warehouse_name 
         FROM inventory_movements im
         JOIN products p ON im.product_id = p.id
         JOIN warehouses w ON im.warehouse_id = w.id
         WHERE im.company_id = ?
         ORDER BY im.created_at DESC LIMIT 10",
        [$company_id]
    );
} catch (Exception $e) {}

$movementTypes = [
    'in' => ['label' => 'وارد', 'color' => 'success', 'icon' => 'arrow-down'],
    'out' => ['label' => 'صادر', 'color' => 'danger', 'icon' => 'arrow-up'],
    'transfer_out' => ['label' => 'تحويل صادر', 'color' => 'warning', 'icon' => 'exchange-alt'],
    'transfer_in' => ['label' => 'تحويل وارد', 'color' => 'info', 'icon' => 'exchange-alt'],
    'opening_balance' => ['label' => 'رصيد افتتاحي', 'color' => 'primary', 'icon' => 'clipboard-list'],
    'production_in' => ['label' => 'إنتاج وارد', 'color' => 'success', 'icon' => 'industry'],
    'production_out' => ['label' => 'إنتاج صادر', 'color' => 'danger', 'icon' => 'industry'],
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

        <main class="main-content">
            <header class="header">
                <div class="header-title">
                    <h1><i class="fas fa-warehouse"></i> <?= $pageTitle ?></h1>
                    <p>إدارة المخزون والمنتجات</p>
                </div>
                <div class="header-actions">
                    <a href="product_form.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> منتج جديد
                    </a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item active"><i class="fas fa-home"></i><span>الرئيسية</span></a>
                        <a href="products.php" class="submenu-item"><i class="fas fa-box"></i><span>المنتجات</span></a>
                        <a href="opening_stock.php" class="submenu-item"><i class="fas fa-clipboard-list"></i><span>أرصدة أول المدة</span></a>
                        <a href="stock_transfers.php" class="submenu-item"><i class="fas fa-exchange-alt"></i><span>التحويلات</span></a>
                        <a href="low-stock.php" class="submenu-item"><i class="fas fa-exclamation-triangle"></i><span>منخفض المخزون</span></a>
                    </div>
                </div>
                
                <!-- إحصائيات -->
                <div class="row mb-3">
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon primary"><i class="fas fa-box"></i></div>
                            <div class="stat-content">
                                <div class="stat-value"><?= number_format($stats['total_products']) ?></div>
                                <div class="stat-label">إجمالي المنتجات</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="stat-content">
                                <div class="stat-value"><?= number_format($stats['low_stock']) ?></div>
                                <div class="stat-label">منخفض المخزون</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon success"><i class="fas fa-coins"></i></div>
                            <div class="stat-content">
                                <div class="stat-value"><?= number_format($stats['total_value'], 2) ?></div>
                                <div class="stat-label">قيمة المخزون</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon info"><i class="fas fa-warehouse"></i></div>
                            <div class="stat-content">
                                <div class="stat-value"><?= number_format($stats['warehouses']) ?></div>
                                <div class="stat-label">المخازن</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- روابط سريعة -->
                <div class="card mb-3">
                    <div class="card-header"><h3 class="card-title"><i class="fas fa-bolt text-warning"></i> إجراءات سريعة</h3></div>
                    <div class="card-body">
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="product_form.php" class="btn btn-primary"><i class="fas fa-plus"></i> منتج جديد</a>
                            <a href="products.php" class="btn btn-outline"><i class="fas fa-box"></i> عرض المنتجات</a>
                            <a href="opening_stock.php" class="btn btn-outline"><i class="fas fa-clipboard-list"></i> أرصدة أول المدة</a>
                            <a href="stock_transfers.php" class="btn btn-outline"><i class="fas fa-exchange-alt"></i> تحويل مخزني</a>
                            <a href="low-stock.php" class="btn btn-outline"><i class="fas fa-exclamation-triangle"></i> منخفض المخزون</a>
                        </div>
                    </div>
                </div>
                
                <!-- آخر الحركات -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-history"></i> آخر حركات المخزون</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>المنتج</th>
                                        <th>المخزن</th>
                                        <th>النوع</th>
                                        <th>الكمية</th>
                                        <th>الرصيد بعد</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentMovements)): ?>
                                    <tr><td colspan="6" class="text-center text-muted p-3">لا توجد حركات مخزنية بعد</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($recentMovements as $m): ?>
                                    <?php $type = $movementTypes[$m['movement_type']] ?? ['label' => $m['movement_type'], 'color' => 'secondary', 'icon' => 'circle']; ?>
                                    <tr>
                                        <td><?= date('Y/m/d H:i', strtotime($m['created_at'])) ?></td>
                                        <td><?= htmlspecialchars($m['product_name']) ?></td>
                                        <td><?= htmlspecialchars($m['warehouse_name']) ?></td>
                                        <td>
                                            <span class="badge badge-<?= $type['color'] ?>">
                                                <i class="fas fa-<?= $type['icon'] ?>"></i> <?= $type['label'] ?>
                                            </span>
                                        </td>
                                        <td><?= number_format($m['quantity'], 2) ?></td>
                                        <td><?= number_format($m['balance_after'], 2) ?></td>
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
</html>
