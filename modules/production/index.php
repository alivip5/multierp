<?php
/**
 * الصفحة الرئيسية لوحدة الإنتاج
 * Production Module - Main Page
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

$pageTitle = 'الإنتاج';

// إحصائيات
$stats = [];
try {
    $stats['total_orders'] = $db->fetch("SELECT COUNT(*) as c FROM production_orders WHERE company_id = ?", [$company_id])['c'] ?? 0;
    $stats['pending'] = $db->fetch("SELECT COUNT(*) as c FROM production_orders WHERE company_id = ? AND status = 'pending'", [$company_id])['c'] ?? 0;
    $stats['in_progress'] = $db->fetch("SELECT COUNT(*) as c FROM production_orders WHERE company_id = ? AND status = 'in_progress'", [$company_id])['c'] ?? 0;
    $stats['completed'] = $db->fetch("SELECT COUNT(*) as c FROM production_orders WHERE company_id = ? AND status = 'completed'", [$company_id])['c'] ?? 0;
    $stats['bom_count'] = $db->fetch("SELECT COUNT(*) as c FROM production_bom WHERE company_id = ? AND is_active = 1", [$company_id])['c'] ?? 0;
} catch (Exception $e) {
    $stats = ['total_orders' => 0, 'pending' => 0, 'in_progress' => 0, 'completed' => 0, 'bom_count' => 0];
}

// آخر أوامر الإنتاج
$recentOrders = [];
try {
    $recentOrders = $db->fetchAll(
        "SELECT po.*, p.name as product_name 
         FROM production_orders po 
         LEFT JOIN products p ON po.product_id = p.id 
         WHERE po.company_id = ? 
         ORDER BY po.created_at DESC LIMIT 5",
        [$company_id]
    );
} catch (Exception $e) {}

$statusLabels = [
    'draft' => 'مسودة',
    'pending' => 'قيد الانتظار',
    'in_progress' => 'قيد التنفيذ',
    'completed' => 'مكتمل',
    'cancelled' => 'ملغي'
];

$statusColors = [
    'draft' => 'secondary',
    'pending' => 'warning',
    'in_progress' => 'info',
    'completed' => 'success',
    'cancelled' => 'danger'
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
                           class="nav-link <?= $module['slug'] === 'production' ? 'active' : '' ?>">
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
                    <h1><i class="fas fa-industry"></i> <?= $pageTitle ?></h1>
                    <p>إدارة عمليات الإنتاج والتصنيع</p>
                </div>
                <div class="header-actions">
                    <a href="add_order.php" class="btn btn-primary"><i class="fas fa-plus"></i> أمر إنتاج جديد</a>
                </div>
            </header>

            <div class="page-content">
                <!-- القائمة الفرعية -->
                <div class="module-submenu">
                    <div class="submenu-container">
                        <a href="index.php" class="submenu-item active"><i class="fas fa-home"></i><span>الرئيسية</span></a>
                        <a href="orders.php" class="submenu-item"><i class="fas fa-clipboard-list"></i><span>أوامر الإنتاج</span></a>
                        <a href="add_order.php" class="submenu-item"><i class="fas fa-plus"></i><span>أمر جديد</span></a>
                        <a href="bom.php" class="submenu-item"><i class="fas fa-sitemap"></i><span>قوائم المواد</span></a>
                    </div>
                </div>
                
                <!-- إحصائيات -->
                <div class="row mb-3">
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon primary"><i class="fas fa-clipboard-list"></i></div>
                            <div class="stat-content">
                                <div class="stat-value"><?= $stats['total_orders'] ?></div>
                                <div class="stat-label">إجمالي الأوامر</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
                            <div class="stat-content">
                                <div class="stat-value"><?= $stats['pending'] ?></div>
                                <div class="stat-label">قيد الانتظار</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon info"><i class="fas fa-cogs"></i></div>
                            <div class="stat-content">
                                <div class="stat-value"><?= $stats['in_progress'] ?></div>
                                <div class="stat-label">قيد التنفيذ</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stat-card">
                            <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                            <div class="stat-content">
                                <div class="stat-value"><?= $stats['completed'] ?></div>
                                <div class="stat-label">مكتمل</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- روابط سريعة -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header"><h3 class="card-title"><i class="fas fa-bolt text-warning"></i> إجراءات سريعة</h3></div>
                            <div class="card-body">
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="add_order.php" class="btn btn-primary"><i class="fas fa-plus"></i> أمر إنتاج جديد</a>
                                    <a href="bom.php" class="btn btn-outline"><i class="fas fa-sitemap"></i> إدارة قوائم المواد</a>
                                    <a href="orders.php?status=in_progress" class="btn btn-outline"><i class="fas fa-cogs"></i> الأوامر الجارية</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- آخر الأوامر -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-history"></i> آخر أوامر الإنتاج</h3>
                        <a href="orders.php" class="btn btn-sm btn-outline">عرض الكل</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>رقم الأمر</th>
                                        <th>المنتج</th>
                                        <th>الكمية</th>
                                        <th>تاريخ الاستحقاق</th>
                                        <th>الحالة</th>
                                        <th>إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentOrders)): ?>
                                    <tr><td colspan="6" class="text-center text-muted p-3">لا توجد أوامر إنتاج بعد</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><a href="view_order.php?id=<?= $order['id'] ?>"><?= htmlspecialchars($order['order_number']) ?></a></td>
                                        <td><?= htmlspecialchars($order['product_name'] ?? '-') ?></td>
                                        <td><?= number_format($order['quantity']) ?> / <?= number_format($order['produced_quantity']) ?></td>
                                        <td><?= $order['due_date'] ?? '-' ?></td>
                                        <td><span class="badge badge-<?= $statusColors[$order['status']] ?? 'secondary' ?>"><?= $statusLabels[$order['status']] ?? $order['status'] ?></span></td>
                                        <td>
                                            <a href="view_order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
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
</body>
</html>
